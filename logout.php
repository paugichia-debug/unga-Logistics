<?php
include 'config.php';

// Check if a session is already active
if (session_status() === PHP_SESSION_NONE) {
    session_name('DRIVER_SESSION');
    session_start();
} else {
    // If session is already active, just use it
    if (session_name() !== 'DRIVER_SESSION') {
        session_name('DRIVER_SESSION');
        session_regenerate_id(true);
    }
}

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

// Now handle admin session - clear any existing session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Start a new session to clear session data
if (session_status() === PHP_SESSION_NONE) {
    session_name('ADMIN_SESSION');
    session_start();
} else {
    session_name('ADMIN_SESSION');
    session_regenerate_id(true);
    session_start();
}

// Clear all session variables
$_SESSION = array();

// Destroy the session
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
