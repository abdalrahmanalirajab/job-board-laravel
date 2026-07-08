<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Display the authenticated user's profile information.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // Eager-load the correct dependent profile row
        if ($user->isEmployer()) {
            $user->load('employerProfile');
        } elseif ($user->isCandidate()) {
            $user->load('candidateProfile');
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile retrieved successfully.',
            'data' => [
                'user' => new UserResource($user),
            ]
        ]);
    }

    /**
     * Update the authenticated user's core data and sub-profile properties.
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        // 1. Update core User data (name, avatar)
        $userData = [];
        if ($request->has('name')) {
            $userData['name'] = $request->input('name');
        }
        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar); // Delete old avatar
            }
            $userData['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }
        if (!empty($userData)) {
            $user->update($userData);
        }

        // 2. Update Employer Profile
        if ($user->isEmployer()) {
            $employerData = [];
            if ($request->has('company_name')) {
                $employerData['company_name'] = $request->input('company_name');
            }
            if ($request->has('website')) {
                $employerData['website'] = $request->input('website');
            }
            if ($request->has('description')) {
                $employerData['description'] = $request->input('description');
            }
            if ($request->hasFile('logo')) {
                if ($user->employerProfile && $user->employerProfile->logo) {
                    Storage::disk('public')->delete($user->employerProfile->logo);
                }
                $employerData['logo'] = $request->file('logo')->store('logos', 'public');
            }

            if (!empty($employerData)) {
                $user->employerProfile()->update($employerData);
            }
            $user->load('employerProfile');
        }

        // 3. Update Candidate Profile
        if ($user->isCandidate()) {
            $candidateData = [];
            if ($request->has('linkedin_url')) {
                $candidateData['linkedin_url'] = $request->input('linkedin_url');
            }
            if ($request->has('bio')) {
                $candidateData['bio'] = $request->input('bio');
            }
            if ($request->has('phone')) {
                $candidateData['phone'] = $request->input('phone');
            }
            if ($request->has('skills')) {
                $candidateData['skills'] = $request->input('skills');
            }
            if ($request->hasFile('resume')) {
                if ($user->candidateProfile && $user->candidateProfile->resume_path) {
                    Storage::disk('public')->delete($user->candidateProfile->resume_path);
                }
                $candidateData['resume_path'] = $request->file('resume')->store('resumes', 'public');
            }

            if (!empty($candidateData)) {
                $user->candidateProfile()->update($candidateData);
            }
            $user->load('candidateProfile');
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => [
                'user' => new UserResource($user),
            ]
        ]);
    }
}