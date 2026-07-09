<?php
session_name('ADMIN_SESSION');
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit();
}

// Get ID from URL
$delivery_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($delivery_id == 0) {
    die("Invalid delivery ID");
}

$query = "SELECT d.*, c.name as customer_name, c.address, c.phone, u.username as driver_name, v.plate_number, v.vehicle_type
    FROM deliveries d 
    LEFT JOIN customers c ON d.customer_id = c.id 
    LEFT JOIN users u ON d.driver_id = u.id 
    LEFT JOIN vehicles v ON d.vehicle_id = v.id
    WHERE d.id = " . $delivery_id;

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query error: " . mysqli_error($conn));
}

$delivery = mysqli_fetch_assoc($result);

if (!$delivery) {
    die("Delivery not found for ID: " . $delivery_id);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delivery Details - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background: linear-gradient(135deg, #f0f2f5 0%, #e2e8f0 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container { max-width: 850px; margin: 0 auto; }
        
        .card { 
            background: white; 
            border-radius: 16px; 
            padding: 25px 30px; 
            margin-bottom: 20px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }
        .card:hover { transform: translateY(-2px); }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f2f5;
        }
        .card-header h3 { 
            color: #1e293b; 
            font-size: 18px;
        }
        .card-header .icon { font-size: 24px; }
        
        .row { 
            padding: 10px 0; 
            border-bottom: 1px solid #f0f2f5; 
            display: flex; 
            align-items: center;
        }
        .row:last-child { border-bottom: none; }
        .label { 
            font-weight: 600; 
            width: 150px; 
            color: #475569;
            font-size: 14px;
            flex-shrink: 0;
        }
        .value { 
            color: #1e293b;
            font-size: 14px;
            word-break: break-word;
        }
        
        .status {
            display: inline-block;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-delivered { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-in_transit { background: #e0e7ff; color: #3730a3; }
        .status-assigned { background: #dbeafe; color: #1e40af; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        
        .penalty { color: #dc2626; font-weight: 700; }
        .weight-value { font-weight: 600; color: #0f172a; }
        
        .btn-group { 
            display: flex; 
            gap: 12px; 
            flex-wrap: wrap;
        }
        .btn { 
            padding: 10px 24px; 
            border: none;
            border-radius: 10px; 
            text-decoration: none; 
            display: inline-block; 
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-back { 
            background: #e2e8f0; 
            color: #475569; 
        }
        .btn-back:hover { background: #cbd5e1; }
        
        .btn-receipt { 
            background: #10b981; 
            color: white; 
        }
        .btn-receipt:hover { background: #059669; }
        
        .btn-edit { 
            background: #3b82f6; 
            color: white; 
        }
        .btn-edit:hover { background: #2563eb; }
        
        .badge-light {
            background: #f1f5f9;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 12px;
            color: #64748b;
        }
        
        @media (max-width: 600px) {
            body { padding: 20px 12px; }
            .card { padding: 18px 16px; }
            .row { flex-direction: column; align-items: flex-start; gap: 4px; }
            .label { width: 100%; }
            .btn-group { flex-direction: column; }
            .btn { text-align: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <span class="icon">📦</span>
                <h3>Delivery Details</h3>
                <span class="badge-light">#<?php echo $delivery_id; ?></span>
            </div>
            
            <div class="row">
                <div class="label">Delivery Code:</div>
                <div class="value"><strong><?php echo htmlspecialchars($delivery['delivery_code']); ?></strong></div>
            </div>
            
            <div class="row">
                <div class="label">Status:</div>
                <div class="value">
                    <span class="status status-<?php echo $delivery['status']; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $delivery['status'])); ?>
                    </span>
                </div>
            </div>
            
            <?php if($delivery['delivered_at']): ?>
            <div class="row">
                <div class="label">Delivered At:</div>
                <div class="value"><?php echo date('d/m/Y H:i', strtotime($delivery['delivered_at'])); ?></div>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="label">Weight:</div>
                <div class="value weight-value">
                    <?php 
                    $weight = isset($delivery['weight_tonnes']) ? (float)$delivery['weight_tonnes'] : 0;
                    echo number_format($weight, 1) . ' tonnes';
                    ?>
                </div>
            </div>
            
            <?php if($delivery['time_window_end']): ?>
            <div class="row">
                <div class="label">Deadline:</div>
                <div class="value"><?php echo date('H:i', strtotime($delivery['time_window_end'])); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if($delivery['penalty_amount'] > 0): ?>
            <div class="row">
                <div class="label">Late Penalty:</div>
                <div class="value penalty">KES <?php echo number_format($delivery['penalty_amount']); ?></div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <div class="card-header">
                <span class="icon">👤</span>
                <h3>Customer Details</h3>
            </div>
            
            <div class="row">
                <div class="label">Name:</div>
                <div class="value"><strong><?php echo htmlspecialchars($delivery['customer_name']); ?></strong></div>
            </div>
            
            <div class="row">
                <div class="label">Address:</div>
                <div class="value"><?php echo htmlspecialchars($delivery['address'] ?: 'N/A'); ?></div>
            </div>
            
            <!-- PHONE REMOVED -->
        </div>
        
        <?php if($delivery['driver_name'] || $delivery['plate_number']): ?>
        <div class="card">
            <div class="card-header">
                <span class="icon">🚚</span>
                <h3>Assigned Vehicle & Driver</h3>
            </div>
            
            <?php if($delivery['driver_name']): ?>
            <div class="row">
                <div class="label">Driver:</div>
                <div class="value">👨‍✈️ <?php echo htmlspecialchars($delivery['driver_name']); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if($delivery['plate_number']): ?>
            <div class="row">
                <div class="label">Vehicle:</div>
                <div class="value">
                    🚛 <?php echo htmlspecialchars($delivery['plate_number']); ?>
                    <?php if($delivery['vehicle_type']): ?>
                    <span class="badge-light"><?php echo ucfirst($delivery['vehicle_type']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="btn-group">
                <a href="deliveries.php" class="btn btn-back">← Back to Deliveries</a>
                <?php if($delivery['status'] == 'delivered'): ?>
                <a href="receipt.php?id=<?php echo $delivery_id; ?>&download=1" class="btn btn-receipt">📄 Download Receipt (PDF)</a>
                <?php endif; ?>
                <?php if($delivery['status'] == 'pending' || $delivery['status'] == 'assigned'): ?>
                <a href="edit_delivery.php?id=<?php echo $delivery_id; ?>" class="btn btn-edit">✏️ Edit Delivery</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
