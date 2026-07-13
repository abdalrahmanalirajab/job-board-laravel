<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $service,
    ) {}

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $unreadOnly      = $request->boolean('unread');
            $filterCategory  = $request->input('category');
            $perPage         = (int) $request->input('per_page', 15);

            $notifications = $this->service->getForUser(
                $request->user(),
                $unreadOnly,
                $filterCategory,
                $perPage,
            );

            return NotificationResource::collection($notifications)->additional([
                'success' => true,
                'message' => 'Notifications retrieved successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notifications: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    public function unreadCount(Request $request): JsonResponse
    {
        try {
            $count = $this->service->getUnreadCount($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Unread count retrieved.',
                'data'    => ['unread_count' => $count],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve unread count.',
                'data'    => null,
            ], 500);
        }
    }

    public function markAsRead(Request $request, $id): JsonResponse
    {
        try {
            $success = $this->service->markAsRead((string) $id, $request->user());

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found.',
                    'data'    => null,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read.',
                'data'    => null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read.',
                'data'    => null,
            ], 500);
        }
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $count = $this->service->markAllAsRead($request->user());

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read.',
                'data'    => ['count' => $count],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all notifications as read.',
                'data'    => null,
            ], 500);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $success = $this->service->delete((string) $id, $request->user());

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found.',
                    'data'    => null,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted.',
                'data'    => null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification.',
                'data'    => null,
            ], 500);
        }
    }

    public function count(Request $request): JsonResponse
    {
        try {
            $user  = $request->user();
            $total = $user->notifications()->count();
            $unread = $this->service->getUnreadCount($user);

            return response()->json([
                'success' => true,
                'message' => 'Notification counts retrieved.',
                'data'    => [
                    'total'  => $total,
                    'unread' => $unread,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve counts.',
                'data'    => null,
            ], 500);
        }
    }
}
