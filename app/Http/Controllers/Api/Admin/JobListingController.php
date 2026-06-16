<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\JobListing;
use App\Http\Resources\JobListingResource;

class JobListingController extends Controller
{
    public function index(Request $request)
    {
        $query = JobListing::query();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('employer_id')) {
            $query->where('employer_id', $request->input('employer_id'));
        }

        $query->with(['category', 'technologies', 'employer']);

        $jobListings = $query->paginate(15);

        return JobListingResource::collection($jobListings)->additional([
            'success' => true,
            'message' => 'All job listings retrieved successfully.'
        ]);
    }

    public function approve($id)
    {
        $jobListing = JobListing::find($id);

        if (!$jobListing) {
            return response()->json([
                'success' => false,
                'message' => 'Job listing not found.'
            ], 404);
        }

        $jobListing->update(['status' => 'approved']);
        $jobListing->load(['category', 'technologies', 'employer']);

        return (new JobListingResource($jobListing))->additional([
            'success' => true,
            'message' => 'Job listing approved successfully.'
        ]);
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:1000'
        ]);

        $jobListing = JobListing::find($id);

        if (!$jobListing) {
            return response()->json([
                'success' => false,
                'message' => 'Job listing not found.'
            ], 404);
        }

        $jobListing->update(['status' => 'rejected']);

        return response()->json([
            'success' => true,
            'message' => 'Job listing rejected successfully.'
        ]);
    }
}
