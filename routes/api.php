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
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Employer\ApplicationController as EmployerApplicationController;
use App\Http\Controllers\Api\Employer\CandidateController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\ChatController;


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

// Stripe webhook — NO auth middleware
Route::post('/payments/stripe/webhook', [PaymentController::class, 'stripeWebhook']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

    // Upload routes
    Route::post('/uploads/resume', [\App\Http\Controllers\Api\UploadController::class, 'resume']);

    // AI routes (any authenticated user)
    Route::post('/ai/interview-prep', [AiController::class, 'interviewPrep']);

    // Candidate AI routes
    Route::middleware('candidate')->group(function () {
        Route::post('/ai/optimize-profile', [AiController::class, 'optimizeProfile']);
    });

    // Chat routes (any authenticated user)
    Route::prefix('chat')->group(function () {
        Route::get('/unread-count', [ChatController::class, 'unreadCount']);
        Route::get('/conversations', [ChatController::class, 'index']);
        Route::post('/conversations', [ChatController::class, 'store']);
        Route::get('/conversations/{id}', [ChatController::class, 'show']);
        Route::post('/conversations/{id}/messages', [ChatController::class, 'sendMessage']);
        Route::post('/conversations/{id}/read', [ChatController::class, 'markRead']);
        Route::post('/conversations/{id}/typing', [ChatController::class, 'typing']);

        // Chat AI routes
        Route::post('/conversations/{id}/ai/smart-replies', [AiController::class, 'smartReplies']);
        Route::post('/conversations/{id}/ai/summarize', [AiController::class, 'summarizeConversation']);
        Route::post('/conversations/{id}/ai/suggest-schedule', [AiController::class, 'suggestSchedule']);
        Route::post('/ai/check-tone', [AiController::class, 'checkTone']);
    });

    // Employer routes
    Route::middleware('employer')->prefix('employer')->group(function () {
        Route::get('/jobs', [EmployerJobListingController::class, 'index']);
        Route::post('/jobs', [EmployerJobListingController::class, 'store']);
        Route::get('/jobs/{id}', [EmployerJobListingController::class, 'show']);
        Route::put('/jobs/{id}', [EmployerJobListingController::class, 'update']);
        Route::delete('/jobs/{id}', [EmployerJobListingController::class, 'destroy']);

        // Employer payments
        Route::get('/payments', [PaymentController::class, 'myPayments']);

        // Employer analytics
        Route::get('/analytics', [AnalyticsController::class, 'overview']);
        Route::get('/analytics/{id}', [AnalyticsController::class, 'jobStats']);

        // Employer candidate search
        Route::get('/candidates/search', [CandidateController::class, 'search']);

        // AI job description generation
        Route::post('/ai/generate-description', [AiController::class, 'generateJobDescription']);
    });

    // Candidate routes
    Route::middleware('candidate')->group(function () {
        Route::post('/jobs/{id}/apply', [ApplicationController::class, 'store']);
        Route::delete('/applications/{id}', [ApplicationController::class, 'destroy']);
        Route::get('/candidate/applications', [ApplicationController::class, 'myApplications']);

        // Saved jobs
        Route::get('/saved-jobs', [\App\Http\Controllers\Api\SavedJobController::class, 'index']);
        Route::get('/saved-jobs/ids', [\App\Http\Controllers\Api\SavedJobController::class, 'savedIds']);
        Route::post('/saved-jobs/{jobId}/toggle', [\App\Http\Controllers\Api\SavedJobController::class, 'toggle']);
        Route::delete('/saved-jobs/{jobId}', [\App\Http\Controllers\Api\SavedJobController::class, 'destroy']);
    });

    // Employer application routes
    Route::middleware('employer')->group(function () {
        Route::get('/employer/applications', [EmployerApplicationController::class, 'index']);
        Route::put('/applications/{id}/accept', [EmployerApplicationController::class, 'accept']);
        Route::put('/applications/{id}/reject', [EmployerApplicationController::class, 'reject']);
    });

    // Employer payment routes (inside auth:sanctum + employer)
    Route::middleware('employer')->group(function () {
        Route::post('/payments/checkout', [PaymentController::class, 'checkout']);
        Route::post('/payments/confirm', [PaymentController::class, 'confirm']);
        Route::get('/payments/{id}/status', [PaymentController::class, 'status']);
    });

    // Admin routes
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/jobs', [AdminJobListingController::class, 'index']);
        Route::put('/jobs/{id}/approve', [AdminJobListingController::class, 'approve']);
        Route::put('/jobs/{id}/reject', [AdminJobListingController::class, 'reject']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/comments', [AdminController::class, 'comments']);
        Route::delete('/comments/{id}', [AdminController::class, 'deleteComment']);
    });

    // Authenticated comment actions
    Route::post('/jobs/{id}/comments', [CommentController::class, 'store']);
    Route::delete('/comments/{id}', [CommentController::class, 'destroy']);

    // Notification routes (authenticated)
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/count', [NotificationController::class, 'count']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::put('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // Admin analytics
    Route::middleware('admin')->group(function () {
        Route::get('/admin/analytics/overview', [AnalyticsController::class, 'platformOverview']);
    });
});
