<?php
// SESSION KEEPER - MUST BE FIRST
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_lifetime', 86400);
session_set_cookie_params(86400);

if (session_status() === PHP_SESSION_NONE) {
    session_name('ADMIN_SESSION');
    session_start();
}

$_SESSION['last_activity'] = time();

// DATABASE CONNECTION
$host = getenv('DB_HOST') ?: 'mysql';
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: '';
$port = getenv('DB_PORT') ?: 3306;

$conn = mysqli_connect($host, $user, $password, $database, $port);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
