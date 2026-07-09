<?php
session_name('ADMIN_SESSION');
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit();
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    mysqli_query($conn, "UPDATE notifications SET status = 'read' WHERE status = 'unread'");
    header('Location: admin_notifications.php');
    exit();
}

// Delete single notification
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM notifications WHERE id = $id");
    header('Location: admin_notifications.php');
    exit();
}

// Mark single as read
if (isset($_GET['read'])) {
    $id = $_GET['read'];
    mysqli_query($conn, "UPDATE notifications SET status = 'read' WHERE id = $id");
    header('Location: admin_notifications.php');
    exit();
}

// Get all notifications
$notifications = mysqli_query($conn, "SELECT * FROM notifications ORDER BY created_at DESC");
$unread_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM notifications WHERE status = 'unread'");
$unread = mysqli_fetch_assoc($unread_result);
$unread_count = $unread['count'];

include 'header.php';
?>

<!-- Stats -->
<div class="stats">
    <div class="stat-card"><h3><?php echo mysqli_num_rows($notifications); ?></h3><p>Total Notifications</p></div>
    <div class="stat-card"><h3><?php echo $unread_count; ?></h3><p>Unread</p></div>
</div>

<!-- Notifications -->
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
        <h3 style="margin: 0; color: #ffffff;">🔔 Notifications</h3>
        <?php if($unread_count > 0): ?>
            <a href="?mark_all_read=1" class="btn" style="background: #48bb78; color: white; padding: 8px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none;">✓ Mark All as Read</a>
        <?php endif; ?>
    </div>
    
    <?php if(mysqli_num_rows($notifications) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($notifications)): ?>
            <div style="background: <?php echo $row['status'] == 'unread' ? 'rgba(66, 153, 225, 0.15)' : 'rgba(255,255,255,0.05)'; ?>; border-left: <?php echo $row['status'] == 'unread' ? '4px solid #4299e1' : '4px solid transparent'; ?>; border-radius: 8px; padding: 15px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div style="flex: 1;">
                    <div style="color: #ffffff; font-size: 14px; margin-bottom: 5px;">
                        <?php echo htmlspecialchars($row['message']); ?>
                        <?php if($row['status'] == 'unread'): ?>
                            <span style="display: inline-block; padding: 1px 10px; border-radius: 12px; font-size: 10px; background: #4299e1; color: white; margin-left: 8px;">NEW</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 11px; color: rgba(255,255,255,0.4);">
                        <?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?>
                    </div>
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <?php if($row['status'] == 'unread'): ?>
                        <a href="?read=<?php echo $row['id']; ?>" class="btn btn-sm" style="padding: 4px 12px; font-size: 11px; text-decoration: none;">Mark Read</a>
                    <?php endif; ?>
                    <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" style="padding: 4px 12px; font-size: 11px; text-decoration: none; background: rgba(229,62,62,0.2); color: #fc8181; border: 1px solid rgba(229,62,62,0.2); border-radius: 20px;" onclick="return confirm('Delete this notification?')">Delete</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 60px; color: rgba(255,255,255,0.5);">
            📭 No notifications yet. When drivers complete deliveries or report issues, they will appear here.
        </div>
    <?php endif; ?>
</div>

<!-- Notification Sound -->
<audio id="notificationSound" preload="auto">
    <source src="/UNGA-LOGISTICS/sounds/notification.wav" type="audio/wav">
</audio>

<script>
var sound = document.getElementById('notificationSound');
var lastUnreadCount = <?php echo $unread_count; ?>;

function playNotificationSound() {
    sound.play().catch(function(error) {
        console.log('Audio play failed:', error);
    });
}

function checkNewNotifications() {
    fetch('check_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.unread_count > lastUnreadCount) {
                playNotificationSound();
                setTimeout(function() {
                    location.reload();
                }, 1000);
            }
            lastUnreadCount = data.unread_count;
            
            var badge = document.querySelector('.sidebar .badge');
            if (badge) {
                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            }
        })
        .catch(error => console.error('Error:', error));
}

setInterval(checkNewNotifications, 15000);

document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        checkNewNotifications();
    }
});
</script>

<?php include 'footer.php'; ?>
