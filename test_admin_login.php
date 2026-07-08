<?php
$email = 'pauline.gichia@unga.com';
$password = md5('admin123');
echo "Testing: $email<br>";
echo "Password hash: $password<br>";
$sql = "SELECT * FROM users WHERE email='$email' AND password='$password'";
echo "SQL: $sql<br>";
?>