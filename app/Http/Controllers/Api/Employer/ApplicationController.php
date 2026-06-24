<?php

namespace App\Http\Controllers\Api\Employer;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    /**
     * List applications for jobs owned by the employer
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Application::whereHas('jobListing', function ($q) use ($user) {
            $q->where('employer_id', $user->id);
        });

        if ($request->has('job_id')) {
            $query->where('job_listing_id', $request->job_id);
        }

        if ($request->has('status') && in_array($request->status, ['pending', 'accepted', 'rejected'])) {
            $query->where('status', $request->status);
        }

        $applications = $query->with([
            'jobListing',
            'candidate.candidateProfile'
        ])->latest()->paginate(10);

        return ApplicationResource::collection($applications)->additional([
            'success' => true,
            'message' => 'Applications retrieved successfully.'
        ]);
    }

    /**
     * Accept a candidate application
     */
    public function accept($id)
    {
        $application = Application::find($id);
        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found.',
                'data' => null
            ], 404);
        }

        if ($application->jobListing->employer_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to manage this application.',
                'data' => null
            ], 403);
        }

        if ($application->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This application has already been reviewed',
                'data' => null
            ], 422);
        }

        $application->update(['status' => 'accepted']);

        return (new ApplicationResource($application->load(['jobListing', 'candidate.candidateProfile'])))
            ->additional([
                'success' => true,
                'message' => 'Application accepted successfully.'
            ]);
    }

    /**
     * Reject a candidate application
     */
    public function reject($id)
    {
        $application = Application::find($id);
        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found.',
                'data' => null
            ], 404);
        }

        if ($application->jobListing->employer_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to manage this application.',
                'data' => null
            ], 403);
        }

        if ($application->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This application has already been reviewed',
                'data' => null
            ], 422);
        }

        $application->update(['status' => 'rejected']);

        return (new ApplicationResource($application->load(['jobListing', 'candidate.candidateProfile'])))
            ->additional([
                'success' => true,
                'message' => 'Application rejected successfully.'
            ]);
    }
}
