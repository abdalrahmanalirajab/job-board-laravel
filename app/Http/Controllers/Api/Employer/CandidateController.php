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
                $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $skill);
                $pattern = "%{$escaped}%";
                $query->where(function ($q) use ($pattern) {
                    $q->whereRaw('name LIKE ? ESCAPE \'\\\'', [$pattern])
                      ->orWhereRaw('email LIKE ? ESCAPE \'\\\'', [$pattern])
                      ->orWhereHas('candidateProfile', function ($q) use ($pattern) {
                          $q->whereRaw('skills LIKE ? ESCAPE \'\\\'', [$pattern])
                            ->orWhereRaw('bio LIKE ? ESCAPE \'\\\'', [$pattern])
                            ->orWhereRaw('linkedin_url LIKE ? ESCAPE \'\\\'', [$pattern]);
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
                    'phone'    => $user->phone ?? ($profile && $profile->phone ? $profile->phone : null),
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
                'message' => 'Failed to search candidates.',
                'data'    => null,
            ], 500);
        }
    }
}
