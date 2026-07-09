<?php
session_name('ADMIN_SESSION');
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit();
}

$vehicles_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM vehicles");
$vehicles_count = mysqli_fetch_assoc($vehicles_result)['count'];

$deliveries_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM deliveries");
$deliveries_count = mysqli_fetch_assoc($deliveries_result)['count'];

$drivers_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'driver'");
$drivers_count = mysqli_fetch_assoc($drivers_result)['count'];

$unread_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM notifications WHERE status = 'unread'");
$unread = mysqli_fetch_assoc($unread_result);

$recent = mysqli_query($conn, "SELECT id, delivery_code, status FROM deliveries ORDER BY id DESC LIMIT 5");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Unga Group PLC</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(160deg, #f5f7fa 0%, #e8e4d8 50%, #f5f7fa 100%);
            min-height: 100vh;
            position: relative;
        }
        /* Subtle pattern overlay */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23d4af37' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 0;
        }
        .sidebar {
            background: #1a202c;
            width: 250px;
            position: fixed;
            height: 100%;
            padding: 2rem 1rem;
            z-index: 10;
        }
        .sidebar h2 { 
            color: #d4af37; 
            margin-bottom: 2rem; 
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
            margin: 5px 0;
            border-radius: 6px;
        }
        .sidebar a:hover, .sidebar a.active { background: #4a5568; }
        .content { 
            margin-left: 250px; 
            padding: 2rem;
            position: relative;
            z-index: 1;
        }
        .header {
            background: white;
            padding: 1rem 2rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            flex-wrap: wrap;
            gap: 10px;
            border-radius: 12px;
            border-left: 5px solid #d4af37;
        }
        .header-left h2 {
            color: #2d3748;
            font-size: 20px;
        }
        .header-left .date {
            font-size: 13px;
            color: #718096;
        }
        .header-right {
            text-align: right;
        }
        .header-right .clock {
            font-size: 22px;
            font-weight: bold;
            color: #d4af37;
            font-variant-numeric: tabular-nums;
        }
        .header-right .admin-info {
            font-size: 12px;
            color: #718096;
            margin-top: 2px;
        }
        .logout-btn {
            background: #c0392b;
            color: white;
            padding: 6px 14px;
            text-decoration: none;
            border-radius: 6px;
            font-size: 13px;
            margin-left: 8px;
        }
        .logout-btn:hover {
            background: #a93226;
        }
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            text-align: center;
            flex: 1;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            border-bottom: 3px solid #d4af37;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            font-size: 28px;
            color: #d4af37;
            margin-bottom: 5px;
        }
        .stat-card p {
            color: #4a5568;
            font-size: 14px;
        }
        .section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #d4af37;
        }
        .section h3 {
            color: #2d3748;
            margin-bottom: 1rem;
            padding-bottom: 10px;
            border-bottom: 2px solid #e8e4d8;
        }
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }
        th {
            background: #f8f6f2;
            font-weight: 600;
            color: #2d3748;
        }
        .btn {
            padding: 6px 12px;
            background: #d4af37;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
        }
        .btn:hover {
            background: #c49a2a;
        }
        .badge {
            background: #e53e3e;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 10px;
            margin-left: 5px;
        }
        #map { height: 400px; width: 100%; border-radius: 12px; margin-bottom: 15px; }
        .driver-list { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px; }
        .driver-item {
            background: #edf2f7;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        .driver-item.active { background: #d4af37; color: white; }
        .info-text { font-size: 12px; color: #718096; margin-top: 10px; }
        
        .footer {
            background: #1a202c;
            color: #a0aec0;
            padding: 2rem;
            border-radius: 12px;
            margin-top: 2rem;
        }
        .footer-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        .footer-section {
            flex: 1;
            min-width: 200px;
        }
        .footer-section h4 {
            color: #d4af37;
            margin-bottom: 1rem;
            font-size: 16px;
        }
        .footer-section p {
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 8px;
        }
        .footer-section a {
            color: #a0aec0;
            text-decoration: none;
        }
        .footer-section a:hover {
            color: #d4af37;
        }
        .copyright {
            text-align: center;
            padding-top: 1.5rem;
            margin-top: 1.5rem;
            border-top: 1px solid #2d3748;
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .content { margin-left: 0; padding: 1rem; }
            .sidebar { display: none; }
            .stats { flex-direction: column; }
            .footer-content { flex-direction: column; text-align: center; }
            .header { flex-direction: column; text-align: center; }
            .header-right { text-align: center; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Unga Logistics</h2>
        <a href="admin_dashboard.php" class="active">Dashboard</a>
        <a href="vehicles.php">Vehicles</a>
        <a href="deliveries.php">Deliveries</a>
        <a href="drivers.php">Drivers</a>
        <a href="ga_optimize.php">GA Optimization</a>
        <a href="admin_notifications.php">Notifications 
            <?php if($unread['count'] > 0): ?>
                <span class="badge"><?php echo $unread['count']; ?></span>
            <?php endif; ?>
        </a>
        <a href="reports.php">Reports</a>
        <a href="admin_issues.php">Issues</a>
        <a href="logout.php">Logout</a>
    </div>
    
    <div class="content">
        <!-- HEADER WITH 24-HOUR CLOCK -->
        <div class="header">
            <div class="header-left">
                <h2>Welcome, <?php echo $_SESSION['username']; ?></h2>
                <div class="date"><?php echo date('l, d/m/Y'); ?></div>
            </div>
            <div class="header-right">
                <div class="clock" id="clockDisplay"><?php echo date('H:i:s'); ?></div>
                <div class="admin-info">
                    Admin Panel
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </div>
        
        <div class="stats">
            <div class="stat-card"><h3><?php echo $vehicles_count; ?></h3><p>Vehicles</p></div>
            <div class="stat-card"><h3><?php echo $deliveries_count; ?></h3><p>Total Deliveries</p></div>
            <div class="stat-card"><h3><?php echo $drivers_count; ?></h3><p>Drivers</p></div>
        </div>
        
        <div class="section">
            <h3>📍 Live Driver Tracking</h3>
            <div id="map"></div>
            <div id="driverList" class="driver-list"></div>
            <p class="info-text">📍 Green markers show active drivers. Updates every 10 seconds.</p>
        </div>
        
        <div class="section">
            <h3>Recent Deliveries</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Delivery Code</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($recent)): ?>
                        <tr>
                            <td style="white-space: nowrap;"><?php echo $row['id']; ?></td>
                            <td style="white-space: nowrap;"><?php echo $row['delivery_code']; ?></td>
                            <td style="white-space: nowrap;"><?php echo $row['status']; ?></td>
                            <td style="white-space: nowrap;"><a href="view_delivery.php?id=<?php echo $row['id']; ?>" class="btn">View</a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="footer">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>🏢 Unga Group PLC</h4>
                    <p>Since 1908</p>
                    <p>Leading logistics and supply chain solutions provider in Kenya.</p>
                    <p>📍 Ngano House, Commercial Street, Industrial Area, Nairobi</p>
                    <p>📮 P.O. Box 30386, Nairobi, Kenya</p>
                </div>
                <div class="footer-section">
                    <h4>📞 Contact Us</h4>
                    <p>📧 Email: customercare@unga.com</p>
                    <p>📞 Phone: 0709 772 000</p>
                    <p>📞 Phone: 0707 202020</p>
                    <p>📞 Phone: 020 7603000</p>
                </div>
                <div class="footer-section">
                    <h4>📍 Regional Locations</h4>
                    <p>📍 Nairobi</p>
                    <p>📍 Eldoret</p>
                </div>
                <div class="footer-section">
                    <h4>🔗 Quick Links</h4>
                    <p><a href="vehicles.php">Vehicle Management</a></p>
                    <p><a href="deliveries.php">Delivery Management</a></p>
                    <p><a href="drivers.php">Driver Management</a></p>
                    <p><a href="reports.php">Reports & Analytics</a></p>
                </div>
            </div>
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> Unga Group PLC. All rights reserved. | Logistics Management System v1.0
            </div>
        </div>
    </div>
    
    <script>
        // LIVE CLOCK UPDATES
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('clockDisplay').textContent = hours + ':' + minutes + ':' + seconds;
        }
        setInterval(updateClock, 1000);
        
        let map;
        let markers = [];
        
        function initMap() {
            map = L.map('map').setView([-1.2864, 36.8172], 8);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
            loadDrivers();
        }
        
        function loadDrivers() {
            fetch('get_driver_locations.php')
                .then(response => response.json())
                .then(data => {
                    markers.forEach(marker => map.removeLayer(marker));
                    markers = [];
                    
                    let driverListHtml = '';
                    let bounds = [];
                    
                    data.forEach(driver => {
                        if (driver.lat && driver.lng) {
                            let marker = L.marker([driver.lat, driver.lng]).addTo(map);
                            marker.bindTooltip(driver.driver_name, { permanent: true, direction: 'top', offset: [0, -20] });
                            marker.bindPopup(`<b>${driver.driver_name}</b><br>Vehicle: ${driver.plate_number}<br>Last: ${driver.last_update}`);
                            
                            markers.push(marker);
                            bounds.push([driver.lat, driver.lng]);
                            driverListHtml += `<div class="driver-item active">🚚 ${driver.driver_name} (${driver.plate_number})</div>`;
                        }
                    });
                    
                    if (bounds.length > 0) {
                        map.fitBounds(bounds);
                    }
                    
                    if (data.length === 0) {
                        driverListHtml = '<span class="info-text">No active drivers.</span>';
                    }
                    
                    document.getElementById('driverList').innerHTML = driverListHtml;
                })
                .catch(error => console.error('Error:', error));
        }
        
        setInterval(loadDrivers, 10000);
        initMap();
    </script>
</body>
</html>
