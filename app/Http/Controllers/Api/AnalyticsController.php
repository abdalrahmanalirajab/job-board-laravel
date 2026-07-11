<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobListing;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Return overview analytics for the authenticated employer.
     * Middleware: auth:sanctum + employer
     */
    public function overview(Request $request)
    {
        try {
            $user = $request->user();

            // Job counts
            $jobsQuery = JobListing::where('employer_id', $user->id);

            $totalJobs    = (clone $jobsQuery)->count();
            $approvedJobs = (clone $jobsQuery)->where('status', 'approved')->count();
            $pendingJobs  = (clone $jobsQuery)->where('status', 'pending')->count();
            $rejectedJobs = (clone $jobsQuery)->where('status', 'rejected')->count();

            // Application counts across all employer's jobs
            $jobIds = (clone $jobsQuery)->pluck('id');

            $applicationsQuery    = Application::whereIn('job_listing_id', $jobIds);
            $totalApplications    = (clone $applicationsQuery)->count();
            $pendingApplications  = (clone $applicationsQuery)->where('status', 'pending')->count();
            $acceptedApplications = (clone $applicationsQuery)->where('status', 'accepted')->count();
            $rejectedApplications = (clone $applicationsQuery)->where('status', 'rejected')->count();

            // Payment stats
            $paymentsQuery = Payment::where('employer_id', $user->id)->where('status', 'completed');
            $totalPayments = (clone $paymentsQuery)->count();
            $totalRevenue  = (clone $paymentsQuery)->sum('amount');

            // Per-job stats for charts
            $jobStats = (clone $jobsQuery)->withCount('applications')->get()->map(function ($job) {
                return [
                    'jobId'        => $job->id,
                    'title'        => $job->title,
                    'applications' => $job->applications_count,
                    'status'       => $job->status,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Analytics overview retrieved successfully.',
                'data'    => [
                    'total_jobs'            => $totalJobs,
                    'approved_jobs'         => $approvedJobs,
                    'pending_jobs'          => $pendingJobs,
                    'rejected_jobs'         => $rejectedJobs,
                    'total_applications'    => $totalApplications,
                    'pending_applications'  => $pendingApplications,
                    'accepted_applications' => $acceptedApplications,
                    'rejected_applications' => $rejectedApplications,
                    'total_payments'        => $totalPayments,
                    'total_revenue'         => (float) $totalRevenue,
                    'job_stats'             => $jobStats,
                    'active_jobs'           => $approvedJobs,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve analytics overview.',
                'data'    => null,
            ], 500);
        }
    }

    /**
     * Return stats for a specific job listing owned by the authenticated employer.
     * Middleware: auth:sanctum + employer
     */
    public function jobStats(Request $request, $jobId)
    {
        try {
            $user = $request->user();

            $job = JobListing::find($jobId);

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job listing not found.',
                    'data'    => null,
                ], 404);
            }

            if ((int) $job->employer_id !== (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to view stats for this job.',
                    'data'    => null,
                ], 403);
            }

            // Application counts
            $appsQuery            = Application::where('job_listing_id', $job->id);
            $totalApplications    = (clone $appsQuery)->count();
            $pendingApplications  = (clone $appsQuery)->where('status', 'pending')->count();
            $acceptedApplications = (clone $appsQuery)->where('status', 'accepted')->count();
            $rejectedApplications = (clone $appsQuery)->where('status', 'rejected')->count();

            // Applications grouped by day for the last 30 days, using created_at
            $applicationsByDay = Application::where('job_listing_id', $jobId)
                ->where('created_at', '>=', now()->subDays(30))
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn($row) => [
                    'date'  => $row->date,
                    'count' => (int) $row->count,
                ]);

            // Payment info
            $payment = Payment::whereHas('application', fn($q) => $q->where('job_listing_id', $job->id))
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Job stats retrieved successfully.',
                'data'    => [
                    'job' => [
                        'id'         => $job->id,
                        'title'      => $job->title,
                        'status'     => $job->status,
                        'created_at' => $job->created_at,
                        'deadline'   => $job->deadline,
                    ],
                    'total_applications'    => $totalApplications,
                    'pending_applications'  => $pendingApplications,
                    'accepted_applications' => $acceptedApplications,
                    'rejected_applications' => $rejectedApplications,
                    'applications_by_day'   => $applicationsByDay,
                    'payment' => [
                        'status'  => $payment ? $payment->status : null,
                        'amount'  => $payment ? (float) $payment->amount : null,
                        'paid_at' => $payment && $payment->paid_at ? $payment->paid_at : null,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve job stats.',
                'data'    => null,
            ], 500);
        }
    }

    /**
     * Admin-only platform overview.
     */
    public function platformOverview(Request $request)
    {
        try {
            if (!$request->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admins can view platform overview.',
                    'data'    => null,
                ], 403);
            }

            $totalUsers        = \App\Models\User::count();
            $totalEmployers    = \App\Models\User::where('role', 'employer')->count();
            $totalCandidates   = \App\Models\User::where('role', 'candidate')->count();
            $totalJobs         = JobListing::count();
            $totalApprovedJobs = JobListing::where('status', 'approved')->count();
            $totalPendingJobs  = JobListing::where('status', 'pending')->count();
            $totalApplications = Application::count();

            return response()->json([
                'success' => true,
                'message' => 'Platform overview retrieved successfully.',
                'data'    => [
                    'total_users'        => $totalUsers,
                    'total_employers'    => $totalEmployers,
                    'total_candidates'   => $totalCandidates,
                    'total_jobs'         => $totalJobs,
                    'approved_jobs'      => $totalApprovedJobs,
                    'pending_jobs'       => $totalPendingJobs,
                    'total_applications' => $totalApplications,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve platform overview.',
                'data'    => null,
            ], 500);
        }
    }
}
