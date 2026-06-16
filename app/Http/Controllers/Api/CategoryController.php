<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount(['jobListings as jobs_count' => function ($query) {
            $query->approved();
        }])->get();

        return CategoryResource::collection($categories)->additional([
            'success' => true,
            'message' => 'Categories retrieved successfully.'
        ]);
    }

    public function show($id)
    {
        $category = Category::withCount(['jobListings as jobs_count' => function ($query) {
            $query->approved();
        }])->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.'
            ], 404);
        }

        return (new CategoryResource($category))->additional([
            'success' => true,
            'message' => 'Category details retrieved successfully.'
        ]);
    }
}
