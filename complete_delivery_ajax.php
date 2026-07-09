<?php
session_name('DRIVER_SESSION');
session_start();
include 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'driver') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$LATE_PENALTY_PER_HOUR = 500;

// Check if POST data exists
if (!isset($_POST['delivery_id']) || empty($_POST['delivery_id'])) {
    echo json_encode(['success' => false, 'message' => 'Delivery ID missing']);
    exit();
}

$delivery_id = (int)$_POST['delivery_id'];

// Check if delivery exists and belongs to this driver
$check_query = mysqli_query($conn, "SELECT * FROM deliveries WHERE id = $delivery_id AND driver_id = $user_id");
if (!$check_query || mysqli_num_rows($check_query) == 0) {
    echo json_encode(['success' => false, 'message' => 'Delivery not found or not assigned to you']);
    exit();
}

$customer_signature = isset($_POST['customer_signature_data']) ? $_POST['customer_signature_data'] : '';
$driver_signature = isset($_POST['driver_signature_data']) ? $_POST['driver_signature_data'] : '';
$customer_name = isset($_POST['customer_name']) ? $_POST['customer_name'] : '';

// Check if signatures are empty or too small
if (empty($customer_signature) || strlen($customer_signature) < 100) {
    echo json_encode(['success' => false, 'message' => 'Customer signature required or incomplete']);
    exit();
}

if (empty($driver_signature) || strlen($driver_signature) < 100) {
    echo json_encode(['success' => false, 'message' => 'Driver signature required or incomplete']);
    exit();
}

// Create signatures directory
$signature_dir = __DIR__ . '/signatures/';
if (!file_exists($signature_dir)) {
    mkdir($signature_dir, 0777, true);
}

// Save customer signature
$customer_sig_file = 'signatures/customer_sig_' . $delivery_id . '_' . time() . '.png';
$customer_full_path = __DIR__ . '/' . $customer_sig_file;
$customer_sig_data = str_replace('data:image/png;base64,', '', $customer_signature);
$customer_sig_data = str_replace(' ', '+', $customer_sig_data);
$customer_decoded = base64_decode($customer_sig_data);
if ($customer_decoded === false) {
    echo json_encode(['success' => false, 'message' => 'Invalid customer signature data']);
    exit();
}
file_put_contents($customer_full_path, $customer_decoded);

// Save driver signature
$driver_sig_file = 'signatures/driver_sig_' . $delivery_id . '_' . time() . '.png';
$driver_full_path = __DIR__ . '/' . $driver_sig_file;
$driver_sig_data = str_replace('data:image/png;base64,', '', $driver_signature);
$driver_sig_data = str_replace(' ', '+', $driver_sig_data);
$driver_decoded = base64_decode($driver_sig_data);
if ($driver_decoded === false) {
    echo json_encode(['success' => false, 'message' => 'Invalid driver signature data']);
    exit();
}
file_put_contents($driver_full_path, $driver_decoded);

// Get delivery info
$delivery_query = mysqli_query($conn, "SELECT d.*, c.name as customer_name, c.address 
    FROM deliveries d 
    LEFT JOIN customers c ON d.customer_id = c.id 
    WHERE d.id = $delivery_id");
$delivery_info = mysqli_fetch_assoc($delivery_query);

if (!$delivery_info) {
    echo json_encode(['success' => false, 'message' => 'Delivery not found']);
    exit();
}

// Calculate penalty
$penalty = 0;
$time_window_end = $delivery_info['time_window_end'];
$current_time = date('H:i:s');

if ($time_window_end && $current_time > $time_window_end) {
    $deadline_h = (int)substr($time_window_end, 0, 2);
    $deadline_m = (int)substr($time_window_end, 3, 2);
    $current_h = (int)substr($current_time, 0, 2);
    $current_m = (int)substr($current_time, 3, 2);
    
    $deadline_total = ($deadline_h * 60) + $deadline_m;
    $current_total = ($current_h * 60) + $current_m;
    
    $minutes_late = $current_total - $deadline_total;
    
    if ($minutes_late > 0) {
        $hours_late = ceil($minutes_late / 60);
        $penalty = $hours_late * $LATE_PENALTY_PER_HOUR;
    }
}

// Update delivery
$update = "UPDATE deliveries SET 
    status = 'delivered', 
    delivered_at = NOW(), 
    signature_path = '$customer_sig_file',
    driver_signature_path = '$driver_sig_file',
    penalty_amount = $penalty
    WHERE id = $delivery_id";

if (!mysqli_query($conn, $update)) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    exit();
}

// Insert notification
$message_text = "✅ Delivery {$delivery_info['delivery_code']} completed by $username for {$delivery_info['customer_name']}" . ($penalty > 0 ? " (Late: KES $penalty)" : "");
mysqli_query($conn, "INSERT INTO notifications (delivery_id, message, status) VALUES ($delivery_id, '$message_text', 'unread')");

echo json_encode(['success' => true, 'penalty' => $penalty, 'delivery_code' => $delivery_info['delivery_code']]);
?>
