<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\JobListingDetailResource;
use App\Http\Resources\JobListingResource;
use App\Models\JobListing;
use Illuminate\Http\Request;

class JobListingController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = JobListing::query()->approved();

            if ($request->filled('search')) {
                $query->search($request->input('search'));
            }
            if ($request->filled('category_id')) {
                $query->byCategory($request->input('category_id'));
            }
            if ($request->filled('location')) {
                $query->byLocation($request->input('location'));
            }
            if ($request->filled('work_type')) {
                $query->byWorkType($request->input('work_type'));
            }
            if ($request->filled('experience_level')) {
                $query->byExperience($request->input('experience_level'));
            }
            if ($request->filled('salary_min') || $request->filled('salary_max')) {
                $query->bySalary($request->input('salary_min'), $request->input('salary_max'));
            }
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->input('date_from'));
            }

            $sortBy = $request->input('sort_by', 'latest');
            match ($sortBy) {
                'oldest'      => $query->oldest(),
                'salary_high' => $query->orderByDesc('salary_max')->orderByDesc('salary_min'),
                'salary_low'  => $query->orderBy('salary_min')->orderBy('salary_max'),
                default       => $query->latest(),
            };

            $query->with(['category', 'technologies', 'employer.employerProfile']);

            $jobListings = $query->paginate(10);

            return JobListingResource::collection($jobListings)->additional([
                'success' => true,
                'message' => 'Approved job listings retrieved successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve job listings.',
                'data'    => null,
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $relations = ['category', 'technologies', 'employer.employerProfile'];

            if (class_exists('App\\Models\\Comment')) {
                $relations['comments'] = function ($query) {
                    $query->where('is_visible', true);
                };
            }

            $jobListing = JobListing::approved()->with($relations)->find($id);

            if (!$jobListing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job listing not found or not approved.',
                    'data'    => null,
                ], 404);
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
}
