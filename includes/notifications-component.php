<?php
require_once 'Notification.php';
require_once 'Auth.php';

$auth = new Auth();
$notification = new Notification();
$userId = $auth->getUserId();
$unreadCount = $notification->getUnreadCount($userId);
$notifications = $notification->getUserNotifications($userId);
?>

<!-- Notification Bell Icon with Counter -->
<div class="notification-wrapper">
    <div class="notification-bell" onclick="toggleNotifications()">
        <i class="fas fa-bell"></i>
        <?php if ($unreadCount > 0): ?>
        <span class="notification-count"><?php echo $unreadCount; ?></span>
        <?php endif; ?>
    </div>

    <!-- Notifications Dropdown -->
    <div id="notificationsDropdown" class="notifications-dropdown">
        <div class="notifications-header">
            <h3>Notifications</h3>
            <?php if ($unreadCount > 0): ?>
            <button onclick="markAllAsRead()" class="mark-all-read">Mark all as read</button>
            <?php endif; ?>
        </div>
        
        <div class="notifications-list">
            <?php if (empty($notifications)): ?>
            <div class="no-notifications">No notifications</div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" 
                     data-id="<?php echo $notif['id']; ?>">
                    <div class="notification-icon">
                        <?php
                        $iconClass = 'info-circle';
                        switch ($notif['type']) {
                            case 'success':
                                $iconClass = 'check-circle';
                                break;
                            case 'warning':
                                $iconClass = 'exclamation-triangle';
                                break;
                            case 'error':
                                $iconClass = 'times-circle';
                                break;
                            case 'application':
                                $iconClass = 'file-alt';
                                break;
                            case 'scholarship':
                                $iconClass = 'graduation-cap';
                                break;
                            case 'system':
                                $iconClass = 'cog';
                                break;
                        }
                        ?>
                        <i class="fas fa-<?php echo $iconClass; ?>"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                        <div class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                        <div class="notification-time">
                            <?php echo date('M j, Y g:i A', strtotime($notif['created_at'])); ?>
                        </div>
                    </div>
                    <?php if (!$notif['is_read']): ?>
                    <div class="notification-actions">
                        <button onclick="markAsRead(<?php echo $notif['id']; ?>)" class="mark-read-btn">
                            <i class="fas fa-check"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.notification-wrapper {
    position: relative;
    display: inline-block;
}

.notification-bell {
    cursor: pointer;
    padding: 10px;
    font-size: 1.2em;
    position: relative;
}

.notification-count {
    position: absolute;
    top: 0;
    right: 0;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 0.7em;
    min-width: 15px;
    text-align: center;
}

.notifications-dropdown {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    width: 300px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    z-index: 1000;
    max-height: 400px;
    overflow-y: auto;
}

.notifications-header {
    padding: 15px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notifications-header h3 {
    margin: 0;
    font-size: 1.1em;
    color: #333;
}

.mark-all-read {
    background: none;
    border: none;
    color: #007bff;
    cursor: pointer;
    font-size: 0.9em;
}

.notifications-list {
    padding: 10px 0;
}

.notification-item {
    padding: 15px;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: flex-start;
    gap: 15px;
    transition: background-color 0.2s;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #f0f7ff;
}

.notification-icon {
    color: #6c757d;
    font-size: 1.2em;
    padding-top: 3px;
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    margin-bottom: 5px;
    color: #333;
}

.notification-message {
    font-size: 0.9em;
    color: #666;
    margin-bottom: 5px;
}

.notification-time {
    font-size: 0.8em;
    color: #888;
}

.notification-actions {
    padding-top: 3px;
}

.mark-read-btn {
    background: none;
    border: none;
    color: #007bff;
    cursor: pointer;
    padding: 5px;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.mark-read-btn:hover {
    background-color: #e9ecef;
}

.no-notifications {
    text-align: center;
    padding: 20px;
    color: #666;
}
</style>

<script>
function toggleNotifications() {
    const dropdown = document.getElementById('notificationsDropdown');
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

function markAsRead(notificationId) {
    fetch('mark-notification-read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const item = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
            if (item) {
                item.classList.remove('unread');
                const actions = item.querySelector('.notification-actions');
                if (actions) actions.remove();
            }
            
            // Update unread count
            const count = document.querySelector('.notification-count');
            const currentCount = parseInt(count.textContent);
            if (currentCount > 1) {
                count.textContent = currentCount - 1;
            } else {
                count.style.display = 'none';
            }
        }
    });
}

function markAllAsRead() {
    fetch('mark-all-notifications-read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
                const actions = item.querySelector('.notification-actions');
                if (actions) actions.remove();
            });
            const counter = document.querySelector('.notification-count');
            if (counter) counter.style.display = 'none';
            const markAllBtn = document.querySelector('.mark-all-read');
            if (markAllBtn) markAllBtn.style.display = 'none';
        }
    });
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('notificationsDropdown');
    const bell = document.querySelector('.notification-bell');
    if (!bell.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.style.display = 'none';
    }
});
</script> 