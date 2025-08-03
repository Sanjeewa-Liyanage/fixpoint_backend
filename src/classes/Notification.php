<?php

class Notification extends Model {
    public $notification_id;
    public $user_id;
    public $title;
    public $message;
    public $type;
    public $is_read;
    public $created_at;

    public function __construct($notification_id = null, $user_id = null, $title = null, $message = null, $type = 'assignment') {
        $this->notification_id = $notification_id;
        $this->user_id = $user_id;
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->is_read = false;
    }

    /**
     * Create a new notification
     * @return bool Success status
     */
    public function create() {
        $conn = DatabaseConnection::getConnection();
        $sql = "INSERT INTO notifications (user_id, title, message, type) VALUES (:user_id, :title, :message, :type) RETURNING notification_id";
        $stmt = $conn->prepare($sql);
        
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':message', $this->message);
        $stmt->bindParam(':type', $this->type);
        
        $result = $stmt->execute();
        
        if ($result) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $this->notification_id = $row['notification_id'];
                return true;
            }
        }
        
        return false;
    }

    /**
     * Read a notification by ID
     * @return bool Success status
     */
    public function read() {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM notifications WHERE notification_id = :notification_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':notification_id', $this->notification_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $this->user_id = $result['user_id'];
            $this->title = $result['title'];
            $this->message = $result['message'];
            $this->type = $result['type'];
            $this->is_read = $result['is_read'];
            $this->created_at = $result['created_at'];
            return true;
        }
        
        return false;
    }

    /**
     * Update a notification
     * @return bool Success status
     */
    public function update() {
        $conn = DatabaseConnection::getConnection();
        $sql = "UPDATE notifications SET title = :title, message = :message, type = :type, is_read = :is_read WHERE notification_id = :notification_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':message', $this->message);
        $stmt->bindParam(':type', $this->type);
        $stmt->bindParam(':is_read', $this->is_read, PDO::PARAM_BOOL);
        $stmt->bindParam(':notification_id', $this->notification_id);
        return $stmt->execute();
    }

    /**
     * Delete a notification
     * @return bool Success status
     */
    public function delete() {
        $conn = DatabaseConnection::getConnection();
        $sql = "DELETE FROM notifications WHERE notification_id = :notification_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':notification_id', $this->notification_id);
        return $stmt->execute();
    }

    /**
     * Mark notification as read
     * @return bool Success status
     */
    public function markAsRead() {
        $this->is_read = true;
        return $this->update();
    }

    /**
     * Create a cluster assignment notification
     * @param int $userId The technician user ID
     * @param int $clusterId The cluster ID
     * @param int $branchCount Number of branches in cluster
     * @return bool Success status
     */
    public static function createClusterAssignmentNotification($userId, $clusterId, $branchCount) {
        // Validate that userId is not null
        if (!$userId || $userId <= 0) {
            error_log("Invalid user_id provided to createClusterAssignmentNotification: " . var_export($userId, true));
            return false;
        }
        
        $notification = new Notification();
        $notification->user_id = $userId;
        $notification->title = "New Cluster Assignment";
        $notification->message = "You have been assigned to cluster #{$clusterId} containing {$branchCount} branches. Please check your dashboard for details.";
        $notification->type = "assignment";
        
        $result = $notification->create();
        if (!$result) {
            error_log("Failed to create notification in database for user {$userId}");
        }
        
        return $result;
    }

    /**
     * Get notifications for a user
     * @param int $userId The user ID
     * @param bool $unreadOnly Whether to fetch only unread notifications
     * @param int $limit Maximum number of notifications to fetch
     * @return array|bool Array of notifications or false on failure
     */
    public static function getUserNotifications($userId, $unreadOnly = false, $limit = 50) {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM notifications WHERE user_id = :user_id";
        
        if ($unreadOnly) {
            $sql .= " AND is_read = FALSE";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT :limit";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $results ?: [];
    }

    /**
     * Get unread notification count for a user
     * @param int $userId The user ID
     * @return int Number of unread notifications
     */
    public static function getUnreadCount($userId) {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = FALSE";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Mark all notifications as read for a user
     * @param int $userId The user ID
     * @return bool Success status
     */
    public static function markAllAsRead($userId) {
        $conn = DatabaseConnection::getConnection();
        $sql = "UPDATE notifications SET is_read = TRUE WHERE user_id = :user_id AND is_read = FALSE";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        return $stmt->execute();
    }

    /**
     * Delete old notifications (cleanup)
     * @param int $daysOld Number of days old to delete
     * @return bool Success status
     */
    public static function deleteOldNotifications($daysOld = 30) {
        $conn = DatabaseConnection::getConnection();
        $sql = "DELETE FROM notifications WHERE created_at < NOW() - INTERVAL '{$daysOld} days' AND is_read = TRUE";
        $stmt = $conn->prepare($sql);
        return $stmt->execute();
    }
}
