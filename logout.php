<?php
include 'config.php';

// Start session properly
if (session_status() === PHP_SESSION_NONE) {
    session_name('DRIVER_SESSION');
    session_start();
}

$is_driver = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] == 'driver';

if ($is_driver) {
    $driver_id = $_SESSION['user_id'];
    
    // Clear driver's GPS location (if table exists)
    mysqli_query($conn, "DELETE FROM driver_locations WHERE driver_id = $driver_id");
    
    // Update driver status in users table (not drivers table)
    mysqli_query($conn, "UPDATE users SET status = 'offline' WHERE id = $driver_id");
}

// Clear all session data
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to admin login
header('Location: admin_login.php');
exit();
?>
