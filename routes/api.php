<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JobListingController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\Employer\JobListingController as EmployerJobListingController;
use App\Http\Controllers\Api\Admin\JobListingController as AdminJobListingController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AnalyticsController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/send-reset-link', [AuthController::class, 'sendResetLink']);
Route::post('/reset', [AuthController::class, 'reset'])->name('password.reset');

// Public routes
Route::get('/jobs', [JobListingController::class, 'index']);
Route::get('/jobs/{id}', [JobListingController::class, 'show']);
Route::get('/jobs/{id}/comments', [CommentController::class, 'index']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    // profile routes 
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

    // Employer routes
    Route::middleware('employer')->prefix('employer')->group(function () {
        Route::get('/jobs', [EmployerJobListingController::class, 'index']);
        Route::post('/jobs', [EmployerJobListingController::class, 'store']);
        Route::get('/jobs/{id}', [EmployerJobListingController::class, 'show']);
        Route::put('/jobs/{id}', [EmployerJobListingController::class, 'update']);
        Route::delete('/jobs/{id}', [EmployerJobListingController::class, 'destroy']);
    });

    // Candidate routes
    Route::middleware('candidate')->group(function () {
        Route::post('/jobs/{id}/apply', [ApplicationController::class, 'store']);
        Route::delete('/applications/{id}', [ApplicationController::class, 'destroy']);
        Route::get('/candidate/applications', [ApplicationController::class, 'myApplications']);
    });

    // Employer application routes
    Route::middleware('employer')->group(function () {
        Route::get('/employer/applications', [\App\Http\Controllers\Api\Employer\ApplicationController::class, 'index']);
        Route::put('/applications/{id}/accept', [\App\Http\Controllers\Api\Employer\ApplicationController::class, 'accept']);
        Route::put('/applications/{id}/reject', [\App\Http\Controllers\Api\Employer\ApplicationController::class, 'reject']);
    });

    // Admin routes
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/jobs', [AdminJobListingController::class, 'index']);
        Route::put('/jobs/{id}/approve', [AdminJobListingController::class, 'approve']);
        Route::put('/jobs/{id}/reject', [AdminJobListingController::class, 'reject']);
    });

    // Authenticated comment actions
    Route::post('/jobs/{id}/comments', [CommentController::class, 'store']);
    Route::delete('/comments/{id}', [CommentController::class, 'destroy']);

    // Employer payment routes
    Route::middleware('employer')->group(function () {
        Route::post('/payments/checkout', [PaymentController::class, 'checkout']);
    });

    // Notification routes (authenticated)
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    // Analytics routes
    Route::middleware('employer')->group(function () {
        Route::get('/analytics/jobs', [AnalyticsController::class, 'jobStats']);
    });

    // Admin analytics
    Route::middleware('admin')->group(function () {
        Route::get('/admin/analytics/overview', [AnalyticsController::class, 'platformOverview']);
    });
});

// Public payment webhook (no auth)
Route::post('/payments/webhook', [PaymentController::class, 'webhook']);