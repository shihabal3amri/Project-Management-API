<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // Fetch all unread notifications
    public function getUnreadNotifications()
    {
        $notifications = auth()->user()->unreadNotifications;

        return response()->json($notifications);
    }

    // Fetch all notifications (read and unread)
    public function getAllNotifications()
    {
        $notifications = auth()->user()->notifications;

        return response()->json($notifications);
    }

    // Mark a notification as read
    public function markAsRead($notificationId)
    {
        $notification = auth()->user()->notifications()->find($notificationId);

        if ($notification) {
            $notification->markAsRead();
            return response()->json(['message' => 'Notification marked as read.']);
        } else {
            return response()->json(['error' => 'Notification not found.'], 404);
        }
    }
}
