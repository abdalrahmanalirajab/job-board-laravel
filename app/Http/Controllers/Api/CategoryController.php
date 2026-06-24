<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\JobListingResource;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        try {
            $categories = Category::withCount(['jobListings as jobs_count' => function ($query) {
                $query->approved();
            }])->get();

            return CategoryResource::collection($categories)->additional([
                'success' => true,
                'message' => 'Categories retrieved successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve categories.',
                'data'    => null,
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $category = Category::withCount(['jobListings as jobs_count' => function ($query) {
                $query->approved();
            }])->find($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found.',
                    'data'    => null,
                ], 404);
            }

            $jobs = $category->jobListings()
                ->approved()
                ->with(['category', 'technologies', 'employer.employerProfile'])
                ->paginate(10);

            return response()->json([
                'success' => true,
                'message' => 'Category and its jobs retrieved successfully.',
                'data' => [
                    'category' => new CategoryResource($category),
                    'jobs' => JobListingResource::collection($jobs)->response()->getData(true),
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve category.',
                'data'    => null,
            ], 500);
        }
    }
}
