<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;


class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        //  save user info
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'], 
        ]);

        // initialize  profiles based on userrole 
        if ($user->isEmployer()) { 
            $user->employerProfile()->create([
                'company_name' => $validated['company_name'],
                'website' => $validated['website'] ?? null,
                'description' => $validated['description'] ?? null,
                'logo' => $validated['logo'] ?? null
            ]);
            $user->load('employerProfile');
        } else {
            if ($request->has('skills')) {
                $validated['skills'] = array_map('trim', explode(',', $request->input('skills')));
            }
            
            $user->candidateProfile()->create([
                'linkedin_url' => $validated['linkedin_url'] ?? null,
                'bio' => $validated['bio'] ?? null,
                'skills' => $validated['skills'] ?? null
            ]);
            $user->load('candidateProfile');
        }

        // generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Email or password is not correct.'], 401);
        }

        // 
        $profile = $user->role . "Profile"; 
        if (method_exists($user, $profile)) {
            $user->load($profile);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'access_token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }   

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        // passwordbroker will create a token and send to email 
        $status = Password::broker()->sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Reset token generated and sent to email successfully.'])
            : response()->json(['message' => 'Unable to send password reset link.'], 400);
    }


    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed',
        ]);

        //  database  check against the token matching the email
        $status = Password::broker()->reset(
            $request->only('token', 'email', 'password'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Your password has been reset successfully.'])
            : response()->json(['message' => 'Invalid token or email matching error.'], 400);
    }
    
    //get all users with profiles 
    public function getAllUsers()
    {
        return response()->json(User::all()->load('employerProfile', 'candidateProfile'));
    }
}