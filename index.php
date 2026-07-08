<?php
// Start session
session_start();

// Include database config
include 'config.php';

// If user is logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: driver_dashboard.php");
    }
    exit;
}

// If not logged in, show login page
header("Location: login.php");
exit;
?>
