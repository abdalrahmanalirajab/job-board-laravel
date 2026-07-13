<?php

namespace App\Http\Controllers\Api\Employer;

use App\Domain\Events\ApplicationAccepted;
use App\Domain\Events\ApplicationRejected;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $query = Application::whereHas('jobListing', function ($q) use ($user) {
                $q->where('employer_id', $user->id);
            });

            if ($request->has('job_id')) {
                $query->where('job_listing_id', $request->job_id);
            }

            if ($request->has('status') && in_array($request->status, ['pending', 'accepted', 'rejected', 'paid'])) {
                $query->where('status', $request->status);
            }

            $applications = $query->with([
                'jobListing',
                'candidate.candidateProfile',
                'payment',
            ])->latest()->paginate(10);

            return ApplicationResource::collection($applications)->additional([
                'success' => true,
                'message' => 'Applications retrieved successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve applications: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    public function accept($id)
    {
        try {
            $application = Application::find($id);

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found.',
                    'data'    => null,
                ], 404);
            }

            if ($application->jobListing->employer_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to manage this application.',
                    'data'    => null,
                ], 403);
            }

            if ($application->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'This application has already been reviewed.',
                    'data'    => null,
                ], 422);
            }

            $application->update(['status' => 'accepted']);
            $application->load('jobListing');

            event(new ApplicationAccepted(
                $application->id,
                $application->job_listing_id,
                $application->candidate_id,
                $application->jobListing->employer_id,
            ));

            return (new ApplicationResource($application->load(['jobListing', 'candidate.candidateProfile'])))
                ->additional([
                    'success' => true,
                    'message' => 'Application accepted successfully.',
                ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept application: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        try {
            $application = Application::find($id);

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found.',
                    'data'    => null,
                ], 404);
            }

            if ($application->jobListing->employer_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to manage this application.',
                    'data'    => null,
                ], 403);
            }

            if ($application->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'This application has already been reviewed.',
                    'data'    => null,
                ], 422);
            }

            $reason = $request->input('reason', $request->input('rejection_reason', ''));

            $application->update([
                'status'           => 'rejected',
                'rejection_reason' => $reason,
            ]);
            $application->load('jobListing');

            event(new ApplicationRejected(
                $application->id,
                $application->job_listing_id,
                $application->candidate_id,
                $application->jobListing->employer_id,
                $reason,
            ));

            return (new ApplicationResource($application->load(['jobListing', 'candidate.candidateProfile'])))
                ->additional([
                    'success' => true,
                    'message' => 'Application rejected successfully.',
                ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject application: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }
}
