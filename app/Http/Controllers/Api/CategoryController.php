<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
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

            return (new CategoryResource($category))->additional([
                'success' => true,
                'message' => 'Category retrieved successfully.',
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
