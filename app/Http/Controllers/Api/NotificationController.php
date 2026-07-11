<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * List authenticated user's notifications.
     * Supports ?unread=true filter to return only unread notifications.
     */
    public function index(Request $request)
    {
        try {
            $query = $request->boolean('unread')
                ? $request->user()->unreadNotifications()
                : $request->user()->notifications();

            $notifications = $query->paginate(15);

            return NotificationResource::collection($notifications)->additional([
                'success' => true,
                'message' => 'Notifications retrieved successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notifications.',
                'data'    => null,
            ], 500);
        }
    }

    /**
     * Return count of unread notifications for the authenticated user.
     */
    public function unreadCount(Request $request)
    {
        try {
            $unreadCount = $request->user()->unreadNotifications()->count();

            return response()->json([
                'success' => true,
                'message' => 'Unread notifications count retrieved.',
                'data'    => [
                    'unread_count' => $unreadCount,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve unread count.',
                'data'    => null,
            ], 500);
        }
    }

    /**
     * Mark a single notification as read by ID.
     */
    public function markAsRead(Request $request, $id)
    {
        try {
            $notification = $request->user()->notifications()->find($id);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found.',
                    'data'    => null,
                ], 404);
            }

            $notification->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read.',
                'data'    => new NotificationResource($notification),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read.',
                'data'    => null,
            ], 500);
        }
    }

    /**
     * Mark all unread notifications as read for the authenticated user.
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $unread = $request->user()->unreadNotifications;
            $count  = $unread->count();

            $unread->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read.',
                'data'    => [
                    'message' => 'All notifications marked as read.',
                    'count'   => $count,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all notifications as read.',
                'data'    => null,
            ], 500);
        }
    }
}
