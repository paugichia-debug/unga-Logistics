<?php
include 'config.php';

$email = 'pauline.gichia@unga.com';
$password = md5('admin123');

$sql = "SELECT * FROM users WHERE email='$email' AND password='$password'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 1) {
    $user = mysqli_fetch_assoc($result);
    echo "User found: " . $user['username'] . " - Role: " . $user['role'];
} else {
    echo "User not found";
}
?>