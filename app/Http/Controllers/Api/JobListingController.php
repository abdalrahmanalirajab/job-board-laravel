<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\JobListing;
use App\Http\Resources\JobListingResource;
use App\Http\Resources\JobListingDetailResource;

class JobListingController extends Controller
{
    public function index(Request $request)
    {
        $query = JobListing::query()->approved();

        // Apply filters
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

        // Apply sorting
        $sortBy = $request->input('sort_by', 'latest');
        switch ($sortBy) {
            case 'oldest':
                $query->oldest();
                break;
            case 'salary_high':
                $query->orderBy('salary_max', 'desc')->orderBy('salary_min', 'desc');
                break;
            case 'salary_low':
                $query->orderBy('salary_min', 'asc')->orderBy('salary_max', 'asc');
                break;
            case 'latest':
            default:
                $query->latest();
                break;
        }

        // Eager load relationships
        $query->with(['category', 'technologies', 'employer.employerProfile']);

        // Paginate
        $jobListings = $query->paginate(10);

        return JobListingResource::collection($jobListings)->additional([
            'success' => true,
            'message' => 'Approved job listings retrieved successfully.'
        ]);
    }

    public function show($id)
    {
        $query = JobListing::query()->approved();

        $relations = ['category', 'technologies', 'employer.employerProfile'];

        // Eager load comments conditionally to prevent breaking if Member 3 has not implemented Comment model yet
        if (class_exists('App\\Models\\Comment')) {
            $relations['comments'] = function ($query) {
                if (method_exists('App\\Models\\Comment', 'scopeVisible')) {
                    $query->visible();
                } else {
                    $query->where('is_visible', true);
                }
            };
        }

        $jobListing = $query->with($relations)->find($id);

        if (!$jobListing) {
            return response()->json([
                'success' => false,
                'message' => 'Job listing not found or not approved.'
            ], 404);
        }

        return (new JobListingDetailResource($jobListing))->additional([
            'success' => true,
            'message' => 'Job listing details retrieved successfully.'
        ]);
    }
}
