<?php
include 'config.php';

$result = mysqli_query($conn, "SELECT id, delivery_code FROM deliveries LIMIT 5");

echo "<h2>Test: Checking if IDs exist</h2>";

while($row = mysqli_fetch_assoc($result)) {
    echo "ID: " . $row['id'] . " - Code: " . $row['delivery_code'] . "<br>";
    echo '<a href="view_delivery.php?id=' . $row['id'] . '">Click to view ID ' . $row['id'] . '</a><br><br>';
}
?>