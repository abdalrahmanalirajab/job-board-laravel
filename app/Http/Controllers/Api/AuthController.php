<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Domain\Events\UserRegistered;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        // save user info
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'], 
        ]);

        // initialize profiles based on user role 
        if ($user->isEmployer()) {
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('logos', 'public');
            }

            $user->employerProfile()->create([
                'company_name' => $validated['company_name'],
                'website' => $validated['website'] ?? null,
                'description' => $validated['description'] ?? null,
                'logo' => $logoPath,
            ]);
            $user->load('employerProfile');
        } else {
            $resumePath = null;
            if ($request->hasFile('resume')) {
                $resumePath = $request->file('resume')->store('resumes', 'public');
            }

            $skills = null;
            if ($request->has('skills') && !empty($request->input('skills'))) {
                $skills = array_map('trim', explode(',', $request->input('skills')));
            }

            $user->candidateProfile()->create([
                'resume_path' => $resumePath,
                'linkedin_url' => $validated['linkedin_url'] ?? null,
                'bio' => $validated['bio'] ?? null,
                'skills' => $skills,
            ]);
            $user->load('candidateProfile');
        }

        event(new UserRegistered($user->id, $user->role, $user->name));

        // generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully.',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ]
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email or password is not correct.'
            ], 401);
        }

        if ($user->isEmployer()) {
            $user->load('employerProfile');
        } elseif ($user->isCandidate()) {
            $user->load('candidateProfile');
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        
        if ($user->isEmployer()) {
            $user->load('employerProfile');
        } elseif ($user->isCandidate()) {
            $user->load('candidateProfile');
        }

        return response()->json([
            'success' => true,
            'message' => 'Authenticated user retrieved successfully.',
            'data' => [
                'user' => new UserResource($user),
            ]
        ]);
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        // password broker will create a token and send to email 
        $status = Password::broker()->sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json([
                'success' => true,
                'message' => 'Reset token generated and sent to email successfully.'
            ])
            : response()->json([
                'success' => false,
                'message' => 'Unable to send password reset link.'
            ], 400);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed',
        ]);

        // database check against the token matching the email
        $status = Password::broker()->reset(
            $request->only('token', 'email', 'password'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json([
                'success' => true,
                'message' => 'Your password has been reset successfully.'
            ])
            : response()->json([
                'success' => false,
                'message' => 'Invalid token or email matching error.'
            ], 400);
    }
    
    // get all users with profiles 
    public function getAllUsers()
    {
        $users = User::with(['employerProfile', 'candidateProfile'])->get();
        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully.',
            'data' => UserResource::collection($users),
        ]);
    }
}