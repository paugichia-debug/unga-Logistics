<?php
include 'config.php';

$sql = "SELECT id, delivery_code FROM deliveries LIMIT 3";
$result = mysqli_query($conn, $sql);

if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        echo "ID: " . $row['id'] . " - Code: " . $row['delivery_code'] . "<br>";
    }
} else {
    echo "Error: " . mysqli_error($conn);
}
?>