<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->paginate($this->perPage());

        return $this->successResponse($notifications);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return $this->successResponse([
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return $this->successResponse(null, 'Notification marked as read.');
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $this->successResponse(null, 'All notifications marked as read.');
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $request->user()
            ->notifications()
            ->where('id', $id)
            ->delete();

        return $this->successResponse(null, 'Notification deleted.');
    }
}
