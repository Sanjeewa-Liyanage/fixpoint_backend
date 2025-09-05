<?php

class NotificationApi extends ApiResourceBase {
    public function __construct() {
        $this->setRoles([
            'getUserNotifications' => ['admin', 'technician'],
            'markAsRead' => ['admin', 'technician'],
            'markAllAsRead' => ['admin', 'technician'],
            'getUnreadCount' => ['admin', 'technician'],
            'deleteNotification' => ['admin', 'technician']
        ]);
    }

    /**
     * Get notifications for a user
     */
    public function getUserNotifications($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$this->checkRoles($user['role_name'], 'getUserNotifications')) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }

        // Get user ID - check multiple possible keys
        $currentUserId = $user['user_id'] ?? $user['id'] ?? null;
        
        // Technicians can only see their own notifications
        $userId = ($user['role_name'] === 'technician') ? $currentUserId : ($data['user_id'] ?? $currentUserId);
        
        if (!$userId) {
            return ['status' => 'error', 'message' => 'User ID not found in authentication token'];
        }
        
        $unreadOnly = isset($data['unread_only']) ? (bool)$data['unread_only'] : false;
        $limit = isset($data['limit']) ? (int)$data['limit'] : 50;
        
        $notifications = Notification::getUserNotifications($userId, $unreadOnly, $limit);

        return [
            'status' => 'success',
            'notifications' => $notifications,
            'count' => count($notifications)
        ];
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$this->checkRoles($user['role_name'], 'markAsRead')) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }

        $missing = $this->validateFields($data, ['notification_id']);
        if (!empty($missing)) {
            return ['status' => 'error', 'message' => 'Missing: ' . implode(', ', $missing)];
        }

        // Verify notification belongs to user (security check)
        $notification = new Notification($data['notification_id']);
        if (!$notification->read()) {
            return ['status' => 'error', 'message' => 'Notification not found'];
        }

        // Get current user ID
        $currentUserId = $user['user_id'] ?? $user['id'] ?? null;
        
        // Technicians can only mark their own notifications as read
        if ($user['role_name'] === 'technician' && $notification->user_id != $currentUserId) {
            return ['status' => 'error', 'message' => 'Unauthorized: Cannot access other user\'s notifications'];
        }

        if ($notification->markAsRead()) {
            return ['status' => 'success', 'message' => 'Notification marked as read'];
        } else {
            return ['status' => 'error', 'message' => 'Failed to update notification'];
        }
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$this->checkRoles($user['role_name'], 'markAllAsRead')) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }

        // Get current user ID
        $currentUserId = $user['user_id'] ?? $user['id'] ?? null;
        
        // Technicians can only mark their own notifications
        $userId = ($user['role_name'] === 'technician') ? $currentUserId : ($data['user_id'] ?? $currentUserId);

        if (!$userId) {
            return ['status' => 'error', 'message' => 'User ID not found in authentication token'];
        }

        if (Notification::markAllAsRead($userId)) {
            return ['status' => 'success', 'message' => 'All notifications marked as read'];
        } else {
            return ['status' => 'error', 'message' => 'Failed to update notifications'];
        }
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$this->checkRoles($user['role_name'], 'getUnreadCount')) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }

        // Get current user ID
        $currentUserId = $user['user_id'] ?? $user['id'] ?? null;
        
        // Technicians can only see their own count
        $userId = ($user['role_name'] === 'technician') ? $currentUserId : ($data['user_id'] ?? $currentUserId);
        
        if (!$userId) {
            return ['status' => 'error', 'message' => 'User ID not found in authentication token'];
        }
        
        $count = Notification::getUnreadCount($userId);

        return [
            'status' => 'success',
            'unread_count' => $count
        ];
    }

    /**
     * Delete a notification
     */
    public function deleteNotification($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$this->checkRoles($user['role_name'], 'deleteNotification')) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }

        $missing = $this->validateFields($data, ['notification_id']);
        if (!empty($missing)) {
            return ['status' => 'error', 'message' => 'Missing: ' . implode(', ', $missing)];
        }

        // Verify notification belongs to user (security check)
        $notification = new Notification($data['notification_id']);
        if (!$notification->read()) {
            return ['status' => 'error', 'message' => 'Notification not found'];
        }

        // Get current user ID
        $currentUserId = $user['user_id'] ?? $user['id'] ?? null;
        
        // Technicians can only delete their own notifications
        if ($user['role_name'] === 'technician' && $notification->user_id != $currentUserId) {
            return ['status' => 'error', 'message' => 'Unauthorized: Cannot delete other user\'s notifications'];
        }

        if ($notification->delete()) {
            return ['status' => 'success', 'message' => 'Notification deleted successfully'];
        } else {
            return ['status' => 'error', 'message' => 'Failed to delete notification'];
        }
    }
}
