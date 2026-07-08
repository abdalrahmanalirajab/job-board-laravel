<?php

namespace App\Http\Controllers\Api\Employer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CandidateController extends Controller
{
    public function search(Request $request)
    {
        try {
            $skill = $request->input('skill', '');

            $query = User::where('role', 'candidate')
                ->whereHas('candidateProfile');

            if (!empty($skill)) {
                $query->where(function ($q) use ($skill) {
                    $q->where('name', 'like', "%{$skill}%")
                      ->orWhere('email', 'like', "%{$skill}%")
                      ->orWhereHas('candidateProfile', function ($q) use ($skill) {
                          $q->where('skills', 'like', "%{$skill}%")
                            ->orWhere('bio', 'like', "%{$skill}%")
                            ->orWhere('linkedin_url', 'like', "%{$skill}%");
                      });
                });
            }

            $candidates = $query->with('candidateProfile')->get();

            $results = $candidates->map(function ($user) {
                $profile = $user->candidateProfile;
                return [
                    'id'       => $user->id,
                    'name'     => $user->name,
                    'email'    => $user->email,
                    'phone'    => null,
                    'linkedin' => $profile ? $profile->linkedin_url : null,
                    'skills'   => $profile && $profile->skills ? $profile->skills : [],
                    'bio'      => $profile ? $profile->bio : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Candidates retrieved successfully.',
                'data'    => $results,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search candidates: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }
}
