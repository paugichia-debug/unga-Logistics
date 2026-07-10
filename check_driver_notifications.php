<?php
session_name('DRIVER_SESSION');
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'driver') {
    echo json_encode(['unread_count' => 0]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check both driver_notifications and notifications tables
$query = "SELECT 
    (SELECT COUNT(*) FROM driver_notifications WHERE driver_id = $user_id AND is_read = 0) as driver_unread,
    (SELECT COUNT(*) FROM notifications WHERE status = 'unread') as admin_unread";

$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

// Return total unread count
$total_unread = ($row['driver_unread'] ?? 0) + ($row['admin_unread'] ?? 0);

echo json_encode(['unread_count' => $total_unread]);
?>
