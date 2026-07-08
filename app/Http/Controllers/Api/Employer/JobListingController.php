<?php

namespace App\Http\Controllers\Api\Employer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobListingRequest;
use App\Http\Requests\UpdateJobListingRequest;
use App\Http\Resources\JobListingDetailResource;
use App\Http\Resources\JobListingResource;
use App\Models\JobListing;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;


class JobListingController extends Controller
{
    public function index()
    {
        try {
            $jobListings = JobListing::where('employer_id', Auth::id())
                ->with(['category', 'technologies', 'employer.employerProfile'])
                ->withCount('applications')
                ->get();

            return JobListingResource::collection($jobListings)->additional([
                'success' => true,
                'message' => 'Your job listings retrieved successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve job listings.',
                'data'    => null,
            ], 500);
        }
    }

    public function store(StoreJobListingRequest $request)
    {
        try {
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('job-logos', 'public');
            }

            $data = $request->safe()->except(['technologies', 'logo']);
            $data['employer_id'] = Auth::id();
            $data['status']      = 'pending'; // Always pending — admin must approve
            if ($logoPath !== null) {
                $data['logo'] = $logoPath;
            }

            $jobListing = JobListing::create($data);

            if ($request->has('technologies')) {
                foreach ((array) $request->input('technologies', []) as $techName) {
                    $jobListing->technologies()->create(['name' => $techName]);
                }
            }

            $jobListing->load(['category', 'technologies', 'employer.employerProfile']);

            return (new JobListingResource($jobListing))
                ->additional([
                    'success' => true,
                    'message' => 'Job listing created successfully and is pending admin approval.',
                ])
                ->response()
                ->setStatusCode(201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create job listing.',
                'data'    => null,
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $jobListing = JobListing::with(['category', 'technologies', 'employer.employerProfile'])
                ->withCount('applications')
                ->find($id);

            if (!$jobListing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job listing not found.',
                    'data'    => null,
                ], 404);
            }

            if ($jobListing->employer_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. You do not own this job listing.',
                    'data'    => null,
                ], 403);
            }

            return (new JobListingDetailResource($jobListing))->additional([
                'success' => true,
                'message' => 'Job listing retrieved successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve job listing.',
                'data'    => null,
            ], 500);
        }
    }

    public function update(UpdateJobListingRequest $request, $id)
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

            if ($jobListing->employer_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. You do not own this job listing.',
                    'data'    => null,
                ], 403);
            }

            $data = $request->safe()->except(['technologies', 'logo']);

            // Reset to pending if previously rejected
            if ($jobListing->status === 'rejected') {
                $data['status'] = 'pending';
            }

            // Handle logo upload — delete old file first
            if ($request->hasFile('logo')) {
                if ($jobListing->logo) {
                    Storage::disk('public')->delete($jobListing->logo);
                }
                $data['logo'] = $request->file('logo')->store('job-logos', 'public');
            }

            $jobListing->update($data);

            // Sync technologies: delete all old → insert new
            if ($request->has('technologies')) {
                $jobListing->technologies()->delete();
                foreach ((array) $request->input('technologies', []) as $techName) {
                    $jobListing->technologies()->create(['name' => $techName]);
                }
            }

            $jobListing->load(['category', 'technologies', 'employer.employerProfile']);

            return (new JobListingResource($jobListing))->additional([
                'success' => true,
                'message' => 'Job listing updated successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update job listing.',
                'data'    => null,
            ], 500);
        }
    }

    public function destroy($id)
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

            if ($jobListing->employer_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. You do not own this job listing.',
                    'data'    => null,
                ], 403);
            }

            // Delete logo file from storage if exists
            if ($jobListing->logo) {
                Storage::disk('public')->delete($jobListing->logo);
            }

            // Explicitly delete associated technologies
            $jobListing->technologies()->delete();

            $jobListing->delete();

            return response()->json([
                'success' => true,
                'message' => 'Job listing deleted successfully.',
                'data'    => null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete job listing.',
                'data'    => null,
            ], 500);
        }
    }
}
