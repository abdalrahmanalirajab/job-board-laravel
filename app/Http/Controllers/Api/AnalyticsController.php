<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function jobStats(Request $request)
    {
        $user = $request->user();

        $jobs = JobListing::where('employer_id', $user->id)
            ->withCount(['applications', 'applications as accepted_count' => function ($q) {
                $q->where('status', 'accepted');
            }, 'applications as rejected_count' => function ($q) {
                $q->where('status', 'rejected');
            }, 'applications as pending_count' => function ($q) {
                $q->where('status', 'pending');
            }])
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'title' => $job->title,
                    'status' => $job->status,
                    'total_applications' => $job->applications_count,
                    'accepted' => $job->accepted_count,
                    'rejected' => $job->rejected_count,
                    'pending' => $job->pending_count,
                ];
            });

        $totals = [
            'total_jobs' => $jobs->count(),
            'total_applications' => $jobs->sum('total_applications'),
            'total_accepted' => $jobs->sum('accepted'),
            'total_rejected' => $jobs->sum('rejected'),
            'total_pending' => $jobs->sum('pending'),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Analytics retrieved successfully.',
            'data' => [
                'jobs' => $jobs,
                'totals' => $totals,
            ],
        ]);
    }

    public function platformOverview(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can view platform overview.',
                'data' => null,
            ], 403);
        }

        $totalUsers = \App\Models\User::count();
        $totalEmployers = \App\Models\User::where('role', 'employer')->count();
        $totalCandidates = \App\Models\User::where('role', 'candidate')->count();
        $totalJobs = JobListing::count();
        $totalApprovedJobs = JobListing::where('status', 'approved')->count();
        $totalPendingJobs = JobListing::where('status', 'pending')->count();
        $totalApplications = \App\Models\Application::count();

        return response()->json([
            'success' => true,
            'message' => 'Platform overview retrieved successfully.',
            'data' => [
                'total_users' => $totalUsers,
                'total_employers' => $totalEmployers,
                'total_candidates' => $totalCandidates,
                'total_jobs' => $totalJobs,
                'approved_jobs' => $totalApprovedJobs,
                'pending_jobs' => $totalPendingJobs,
                'total_applications' => $totalApplications,
            ],
        ]);
    }
}
