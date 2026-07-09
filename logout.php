<?php
include 'config.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name('DRIVER_SESSION');
    session_start();
}

$is_driver = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] == 'driver';

if ($is_driver) {
    $driver_id = $_SESSION['user_id'];
    
    // Clear driver's GPS location (if table exists)
    @mysqli_query($conn, "DELETE FROM driver_locations WHERE driver_id = $driver_id");
    
    // REMOVED: Update users table - status column doesn't exist
    // Just skip this step
}

// Clear all session data
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login
header('Location: admin_login.php');
exit();
?>
