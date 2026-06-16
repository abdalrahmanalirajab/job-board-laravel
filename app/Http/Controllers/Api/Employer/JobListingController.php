<?php

namespace App\Http\Controllers\Api\Employer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\JobListing;
use App\Http\Resources\JobListingResource;
use App\Http\Requests\StoreJobListingRequest;
use App\Http\Requests\UpdateJobListingRequest;
use Illuminate\Support\Facades\Storage;

class JobListingController extends Controller
{
    public function index()
    {
        $jobListings = JobListing::where('employer_id', auth()->id())
            ->with(['category', 'technologies'])
            ->get();

        return JobListingResource::collection($jobListings)->additional([
            'success' => true,
            'message' => 'Your job listings retrieved successfully.'
        ]);
    }

    public function store(StoreJobListingRequest $request)
    {
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('job-logos', 'public');
        }

        $data = $request->safe()->except(['technologies', 'logo']);
        $data['employer_id'] = auth()->id();
        $data['status'] = 'pending';
        if ($logoPath !== null) {
            $data['logo'] = $logoPath;
        }

        $jobListing = JobListing::create($data);

        if ($request->has('technologies')) {
            $techs = $request->input('technologies') ?? [];
            foreach ($techs as $techName) {
                $jobListing->technologies()->create(['name' => $techName]);
            }
        }

        $jobListing->load(['category', 'technologies']);

        return (new JobListingResource($jobListing))
            ->additional([
                'success' => true,
                'message' => 'Job listing created successfully and is pending approval.'
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function show($id)
    {
        $jobListing = JobListing::with(['category', 'technologies'])->find($id);

        if (!$jobListing) {
            return response()->json([
                'success' => false,
                'message' => 'Job listing not found.'
            ], 404);
        }

        if ($jobListing->employer_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own this job listing.'
            ], 403);
        }

        return (new JobListingResource($jobListing))->additional([
            'success' => true,
            'message' => 'Job listing retrieved successfully.'
        ]);
    }

    public function update(UpdateJobListingRequest $request, $id)
    {
        $jobListing = JobListing::find($id);

        if (!$jobListing) {
            return response()->json([
                'success' => false,
                'message' => 'Job listing not found.'
            ], 404);
        }

        if ($jobListing->employer_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own this job listing.'
            ], 403);
        }

        $data = $request->safe()->except(['technologies', 'logo']);

        if ($jobListing->status === 'rejected') {
            $data['status'] = 'pending';
        }

        if ($request->hasFile('logo')) {
            if ($jobListing->logo) {
                Storage::disk('public')->delete($jobListing->logo);
            }
            $data['logo'] = $request->file('logo')->store('job-logos', 'public');
        }

        $jobListing->update($data);

        if ($request->has('technologies')) {
            $jobListing->technologies()->delete();
            $techs = $request->input('technologies') ?? [];
            foreach ($techs as $techName) {
                $jobListing->technologies()->create(['name' => $techName]);
            }
        }

        $jobListing->load(['category', 'technologies']);

        return (new JobListingResource($jobListing))->additional([
            'success' => true,
            'message' => 'Job listing updated successfully.'
        ]);
    }

    public function destroy($id)
    {
        $jobListing = JobListing::find($id);

        if (!$jobListing) {
            return response()->json([
                'success' => false,
                'message' => 'Job listing not found.'
            ], 404);
        }

        if ($jobListing->employer_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own this job listing.'
            ], 403);
        }

        if ($jobListing->logo) {
            Storage::disk('public')->delete($jobListing->logo);
        }

        $jobListing->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job listing deleted successfully.',
            'data' => null
        ]);
    }
}
