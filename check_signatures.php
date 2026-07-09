<?php
include 'config.php';

$delivery_id = 31;
$query = mysqli_query($conn, "SELECT signature_path, driver_signature_path FROM deliveries WHERE id = $delivery_id");
$row = mysqli_fetch_assoc($query);

echo "Customer Signature Path: " . $row['signature_path'] . "<br>";
echo "Driver Signature Path: " . $row['driver_signature_path'] . "<br><br>";

$customer_exists = file_exists($row['signature_path']);
$driver_exists = file_exists($row['driver_signature_path']);

echo "Customer signature file exists: " . ($customer_exists ? 'YES' : 'NO') . "<br>";
echo "Driver signature file exists: " . ($driver_exists ? 'YES' : 'NO') . "<br><br>";

echo "Signatures directory exists: " . (file_exists('signatures/') ? 'YES' : 'NO') . "<br>";

echo "<br>=== All Signature Files ===<br>";
$files = glob('signatures/*.png');
if (count($files) > 0) {
    foreach ($files as $file) {
        echo basename($file) . " (" . filesize($file) . " bytes)<br>";
    }
} else {
    echo "No signature files found.";
}
?>
