<?php

namespace App\Http\Controllers\Api\Admin;

use App\Domain\Events\JobApproved;
use App\Domain\Events\JobRejected;
use App\Http\Controllers\Controller;
use App\Http\Resources\JobListingResource;
use App\Models\JobListing;
use Illuminate\Http\Request;

class JobListingController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = JobListing::query();

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->filled('employer_id')) {
                $query->where('employer_id', $request->input('employer_id'));
            }

            $query->with(['category', 'technologies', 'employer.employerProfile'])->withCount('applications')->latest();

            $jobListings = $query->paginate(15);

            return JobListingResource::collection($jobListings)->additional([
                'success' => true,
                'message' => 'All job listings retrieved successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve job listings.',
                'data'    => null,
            ], 500);
        }
    }

    public function approve($id)
    {
        try {
            $jobListing = JobListing::find($id);

            if (!$jobListing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job listing not found.',
                    'data'    => null,
                ], 404);
            }

            $jobListing->update(['status' => 'approved']);
            $jobListing->load(['category', 'technologies', 'employer.employerProfile']);

            event(new JobApproved($jobListing->id, $jobListing->employer_id, $jobListing->title));

            return (new JobListingResource($jobListing))->additional([
                'success' => true,
                'message' => 'Job listing approved successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve job listing.',
                'data'    => null,
            ], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        try {
            $request->validate([
                'reason' => 'nullable|string|max:1000',
            ]);

            $jobListing = JobListing::find($id);

            if (!$jobListing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job listing not found.',
                    'data'    => null,
                ], 404);
            }

            $reason = $request->input('reason');

            $jobListing->update([
                'status'           => 'rejected',
                'rejection_reason' => $reason,
            ]);
            $jobListing->load('employer');

            event(new JobRejected($jobListing->id, $jobListing->employer_id, $jobListing->title, $reason));

            return response()->json([
                'success' => true,
                'message' => 'Job listing rejected successfully.',
                'data'    => [
                    'id'     => $jobListing->id,
                    'status' => $jobListing->status,
                    'reason' => $reason,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject job listing.',
                'data'    => null,
            ], 500);
        }
    }
}
