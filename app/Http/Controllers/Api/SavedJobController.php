<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SavedJobResource;
use App\Models\JobListing;
use App\Models\SavedJob;
use Illuminate\Http\Request;

class SavedJobController extends Controller
{
    public function toggle(Request $request, $jobId)
    {
        $job = JobListing::find($jobId);
        if (!$job || $job->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Job listing not found.',
                'data' => null,
            ], 404);
        }

        $userId = $request->user()->id;
        $existing = SavedJob::where('job_listing_id', $jobId)
            ->where('candidate_id', $userId)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'success' => true,
                'message' => 'Job removed from saved list.',
                'data' => ['saved' => false],
            ]);
        }

        SavedJob::create([
            'job_listing_id' => $jobId,
            'candidate_id' => $userId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job saved successfully.',
            'data' => ['saved' => true],
        ], 201);
    }

    public function index(Request $request)
    {
        $savedJobs = SavedJob::where('candidate_id', $request->user()->id)
            ->with(['jobListing' => function ($q) {
                $q->with(['category', 'employer.employerProfile', 'technologies']);
            }])
            ->latest()
            ->paginate(12);

        return SavedJobResource::collection($savedJobs)->additional([
            'success' => true,
            'message' => 'Saved jobs retrieved successfully.',
        ]);
    }

    public function destroy(Request $request, $jobId)
    {
        $deleted = SavedJob::where('job_listing_id', $jobId)
            ->where('candidate_id', $request->user()->id)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Saved job not found.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Job removed from saved list.',
            'data' => null,
        ]);
    }

    public function savedIds(Request $request)
    {
        $ids = SavedJob::where('candidate_id', $request->user()->id)
            ->pluck('job_listing_id')
            ->toArray();

        return response()->json([
            'success' => true,
            'message' => 'Saved job IDs retrieved.',
            'data' => $ids,
        ]);
    }
}
