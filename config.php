<?php
// ============================================
// FORCE KENYA TIMEZONE
// ============================================
date_default_timezone_set('Africa/Nairobi');

$host = getenv('DB_HOST') ?: 'mysql';
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: '';
$port = getenv('DB_PORT') ?: 3306;

$conn = mysqli_connect($host, $user, $password, $database, $port);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ============================================
// FORCE MYSQL TO USE KENYA TIME
// ============================================
mysqli_query($conn, "SET time_zone = '+03:00'");
?>
