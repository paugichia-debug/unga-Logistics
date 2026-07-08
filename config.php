<?php
$host = getenv('DB_HOST') ?: 'mysql';
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: '';
$port = getenv('DB_PORT') ?: 3306;

if (!$host || !$user || !$database) {
    die("Database variables not set. Check DB_HOST, DB_USER, DB_NAME");
}

$conn = mysqli_connect($host, $user, $password, $database, $port);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Database connected successfully!";
?>
