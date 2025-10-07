<?php

class Notifications {
    private static $instance = null;
    private $db;

    private function __construct() {
        require_once 'Database.php';
        $this->db = Database::getInstance()->getConnection();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getUnreadMessagesCount($user_id) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM messages 
            WHERE recipient_id = ? 
            AND read_status = 0
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'];
    }

    public function getUnreadNotificationsCount($user_id) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = ? 
            AND read_status = 0
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'];
    }

    public function markMessageAsRead($message_id) {
        $stmt = $this->db->prepare("
            UPDATE messages 
            SET read_status = 1 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $message_id);
        return $stmt->execute();
    }

    public function markNotificationAsRead($notification_id) {
        $stmt = $this->db->prepare("
            UPDATE notifications 
            SET read_status = 1 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $notification_id);
        return $stmt->execute();
    }

    public function createNotification($user_id, $type, $message, $link = '') {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (user_id, type, message, link, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("isss", $user_id, $type, $message, $link);
        return $stmt->execute();
    }

    public function getNotificationBadgeHtml($count) {
        if ($count > 0) {
            return '<span class="notification-badge" data-count="' . $count . '"></span>';
        }
        return '';
    }
} 