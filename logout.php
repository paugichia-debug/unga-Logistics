<?php
include 'config.php';

// Check if driver session exists before destroying
session_name('DRIVER_SESSION');
session_start();

$is_driver = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] == 'driver';

if ($is_driver) {
    $driver_id = $_SESSION['user_id'];
    
    // Clear driver's GPS location
    mysqli_query($conn, "DELETE FROM driver_locations WHERE driver_id = $driver_id");
    
    // Update driver status to offline
    mysqli_query($conn, "UPDATE drivers SET status = 'offline' WHERE user_id = $driver_id");
    
    // Destroy driver session
    session_destroy();
}

// Destroy admin session if exists
session_name('ADMIN_SESSION');
session_start();
session_destroy();

// Redirect based on where the user came from
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

if (strpos($referrer, 'driver') !== false && !$is_driver) {
    header('Location: driver_login.php');
} else {
    header('Location: admin_login.php');
}
exit();
?>
