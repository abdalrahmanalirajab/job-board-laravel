<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
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

        return response()->json(['user' => $user]);
    }

    /**
     * Update the authenticated user's core data and sub-profile properties.
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar); // Delete old avatar
            }
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($request->only(['name', 'avatar']));

        // 2. Update Employer Profiles 
        if ($user->isEmployer()) {
            $employerData = $request->only(['company_name', 'website', 'description']);

            if ($request->hasFile('logo')) {
                if ($user->employerProfile->logo) {
                    Storage::disk('public')->delete($user->employerProfile->logo);
                }
                $employerData['logo'] = $request->file('logo')->store('logos', 'public');
            }

            $user->employerProfile()->update($employerData);
            $user->load('employerProfile');
        }

        // 3. Update Candidate  Profiles 
        if ($user->isCandidate()) {
            $candidateData = $request->only(['linkedin_url', 'bio']);

            if ($request->has('skills')) {
                // Explodes "Laravel, Vue.js" into an array and trims whitespace from each item
                $candidateData['skills'] = array_map('trim', explode(',', $request->input('skills')));
            }
            if ($request->hasFile('resume')) {
                if ($user->candidateProfile->resume_path) {
                    Storage::disk('public')->delete($user->candidateProfile->resume_path);
                }
                $candidateData['resume_path'] = $request->file('resume')->store('resumes', 'public');
            }

            $user->candidateProfile()->update($candidateData);
            $user->load('candidateProfile');
        }

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user
        ]);
    }
}