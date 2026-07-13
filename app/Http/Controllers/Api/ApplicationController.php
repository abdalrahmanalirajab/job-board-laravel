<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApplicationRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\JobListing;
use App\Domain\Events\ApplicationSubmitted;
use App\Domain\Events\ApplicationWithdrawn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    /**
     * Candidate applies for a job
     */
    public function store(StoreApplicationRequest $request, $jobId)
    {
        $job = JobListing::find($jobId);
        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Job listing not found.',
                'data' => null
            ], 404);
        }

        // Check if job is approved
        if ($job->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'You can only apply for approved job listings',
                'data' => null
            ], 422);
        }

        // Check if job deadline is passed
        if ($job->deadline && $job->deadline->endOfDay()->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'The application deadline for this job has passed',
                'data' => null
            ], 422);
        }

        // Prevent duplicate applications
        $existing = Application::where('job_listing_id', $job->id)
            ->where('candidate_id', $request->user()->id)
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You have already applied for this job',
                'data' => null
            ], 422);
        }

        $resumePath = null;
        if ($request->filled('resume_url')) {
            $resumePath = $request->input('resume_url');
        } elseif ($request->hasFile('resume')) {
            $resumePath = $request->file('resume')->store('resumes', 'public');
        }

        $application = Application::create([
            'job_listing_id' => $job->id,
            'candidate_id' => $request->user()->id,
            'resume_path' => $resumePath,
            'resume_name' => $request->input('resume_name'),
            'contact_email' => $request->input('contact_email', $request->input('email')),
            'contact_phone' => $request->input('contact_phone', $request->input('phone')),
            'status' => 'pending',
            'applied_at' => now(),
        ]);

        event(new ApplicationSubmitted(
            $application->id,
            $job->id,
            $request->user()->id,
            $job->employer_id,
        ));

        return (new ApplicationResource($application->load(['jobListing', 'candidate'])))
            ->additional([
                'success' => true,
                'message' => 'Application submitted successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Candidate cancels their application
     */
    public function destroy($id)
    {
        $application = Application::find($id);
        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found.',
                'data' => null
            ], 404);
        }

        if ($application->candidate_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to cancel this application.',
                'data' => null
            ], 403);
        }

        if ($application->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'You cannot cancel an application that has already been reviewed',
                'data' => null
            ], 422);
        }

        if ($application->resume_path) {
            Storage::disk('public')->delete($application->resume_path);
        }

        $application->load('jobListing');
        $employerId = $application->jobListing->employer_id;

        $application->delete();

        event(new ApplicationWithdrawn(
            $id,
            $application->job_listing_id,
            $application->candidate_id,
            $employerId,
        ));

        return response()->json([
            'success' => true,
            'message' => 'Application cancelled successfully.',
            'data' => null
        ]);
    }

    /**
     * List candidate's own applications
     */
    public function myApplications(Request $request)
    {
        $user = $request->user();
        $query = Application::where('candidate_id', $user->id);

        if ($request->has('status') && in_array($request->status, ['pending', 'accepted', 'rejected'])) {
            $query->where('status', $request->status);
        }

        $applications = $query->with([
            'jobListing' => function ($q) {
                $q->with(['category', 'employer.employerProfile']);
            }
        ])->latest()->paginate(10);

        return ApplicationResource::collection($applications)->additional([
            'success' => true,
            'message' => 'Your applications retrieved successfully.'
        ]);
    }
}