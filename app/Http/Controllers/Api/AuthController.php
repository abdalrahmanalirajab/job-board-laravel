<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

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
                'company_name' => $validated['company_name']
            ]);
            $user->load('employerProfile');
        } else { 
            $user->candidateProfile()->create();
            $user->load('candidateProfile');
        }

        // generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'user'=> $user
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
    
    //get all users with profiles 
    public function getAllUsers()
    {
        return response()->json(User::all()->load('employerProfile', 'candidateProfile'));
    }
}