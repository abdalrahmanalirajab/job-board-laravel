<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApplicationRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\JobListing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
  /**
   * Candidate applies for a job
   */
  public function apply(StoreApplicationRequest $request, JobListing $job)
  {
    $user = $request->user();

    // Ensure user is a candidate
    if ($user->role !== 'candidate') {
      return response()->json(['message' => 'Only candidates can apply for jobs.'], 403);
    }

    // Check if job is approved
    if ($job->status !== 'approved') {
      return response()->json(['message' => 'You can only apply to approved jobs.'], 422);
    }

    // Prevent duplicate applications
    $existing = Application::where('job_listing_id', $job->id)
      ->where('candidate_id', $user->id)
      ->first();

    if ($existing) {
      return response()->json(['message' => 'You have already applied for this job.'], 409);
    }

    $data = [
      'job_listing_id' => $job->id,
      'candidate_id' => $user->id,
      'contact_email' => $request->input('contact_email', $user->email),
      'contact_phone' => $request->input('contact_phone'),
      'status' => 'pending',
      'applied_at' => now(),
    ];

    // Handle resume upload
    if ($request->hasFile('resume')) {
      $path = $request->file('resume')->store('resumes', 'public');
      $data['resume_path'] = $path;
    }

    $application = Application::create($data);

    return new ApplicationResource($application);
  }

  /**
   * Candidate cancels their application
   */
  public function cancel(Request $request, Application $application)
  {
    $user = $request->user();

    if ($application->candidate_id !== $user->id) {
      return response()->json(['message' => 'You can only cancel your own applications.'], 403);
    }

    if ($application->status !== 'pending') {
      return response()->json(['message' => 'You can only cancel pending applications.'], 422);
    }

    $application->delete();

    return response()->json(['message' => 'Application cancelled successfully.']);
  }

  /**
   * List candidate's own applications
   */
  public function candidateApplications(Request $request)
  {
    $user = $request->user();

    $applications = Application::where('candidate_id', $user->id)
      ->with(['jobListing', 'candidate'])
      ->latest()
      ->paginate(20);

    return ApplicationResource::collection($applications);
  }

  /**
   * List applications received by employer (for their jobs)
   */
  public function employerApplications(Request $request)
  {
    $user = $request->user();

    $applications = Application::whereHas('jobListing', function ($query) use ($user) {
      $query->where('employer_id', $user->id);
    })
      ->with(['jobListing', 'candidate'])
      ->latest()
      ->paginate(20);

    return ApplicationResource::collection($applications);
  }

  /**
   * Employer accepts an application
   */
  public function accept(Request $request, Application $application)
  {
    $user = $request->user();

    // Ensure employer owns the job
    if ($application->jobListing->employer_id !== $user->id) {
      return response()->json(['message' => 'You can only manage applications for your own jobs.'], 403);
    }

    if ($application->status !== 'pending') {
      return response()->json(['message' => 'This application has already been processed.'], 422);
    }

    $application->update(['status' => 'accepted']);

    return new ApplicationResource($application->load(['jobListing', 'candidate']));
  }

  /**
   * Employer rejects an application
   */
  public function reject(Request $request, Application $application)
  {
    $user = $request->user();

    // Ensure employer owns the job
    if ($application->jobListing->employer_id !== $user->id) {
      return response()->json(['message' => 'You can only manage applications for your own jobs.'], 422);
    }

    if ($application->status !== 'pending') {
      return response()->json(['message' => 'This application has already been processed.'], 422);
    }

    $application->update(['status' => 'rejected']);

    return new ApplicationResource($application->load(['jobListing', 'candidate']));
  }
}