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
            font-family: 'Segoe UI', Arial, sans-serif;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* ============================================ */
        /* SLIDESHOW BACKGROUND */
        /* ============================================ */
        .slideshow-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 1.5s ease-in-out;
        }

        .slide.active {
            opacity: 1;
        }

        /* Dark overlay for readability */
        .slide-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            background: rgba(26, 32, 44, 0.7);
            backdrop-filter: blur(2px);
        }

        /* ============================================ */
        /* SIDEBAR */
        /* ============================================ */
        .sidebar {
            background: rgba(26, 32, 44, 0.92);
            width: 250px;
            position: fixed;
            height: 100%;
            padding: 2rem 1rem;
            z-index: 10;
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(212, 175, 55, 0.2);
        }
        .sidebar h2 { 
            color: #d4af37; 
            margin-bottom: 2rem;
            font-size: 22px;
            letter-spacing: 1px;
        }
        .sidebar h2 span {
            font-weight: 300;
            color: #a0aec0;
            font-size: 12px;
            display: block;
            letter-spacing: 2px;
        }
        .sidebar a {
            color: #cbd5e1;
            text-decoration: none;
            display: block;
            padding: 10px 14px;
            margin: 4px 0;
            border-radius: 8px;
            transition: all 0.3s;
            font-size: 14px;
        }
        .sidebar a:hover {
            background: rgba(212, 175, 55, 0.15);
            color: #d4af37;
            padding-left: 20px;
        }
        .sidebar a.active {
            background: rgba(212, 175, 55, 0.2);
            color: #d4af37;
            border-left: 3px solid #d4af37;
        }

        /* ============================================ */
        /* CONTENT */
        /* ============================================ */
        .content { 
            margin-left: 250px; 
            padding: 2rem;
            position: relative;
            z-index: 2;
        }

        /* ============================================ */
        /* HEADER WITH GLASS EFFECT */
        /* ============================================ */
        .header {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 1.2rem 2rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        .header-left h2 {
            color: #ffffff;
            font-size: 22px;
            font-weight: 600;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .header-left h2 span {
            color: #d4af37;
        }
        .header-left .date {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 2px;
        }

        .header-right {
            text-align: right;
        }
        .header-right .clock {
            font-size: 28px;
            font-weight: 700;
            color: #ffffff;
            font-variant-numeric: tabular-nums;
            text-shadow: 0 2px 15px rgba(0,0,0,0.4);
            letter-spacing: 2px;
        }
        .header-right .clock .seconds {
            font-size: 18px;
            color: #d4af37;
            font-weight: 400;
        }
        .header-right .admin-info {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 4px;
        }
        .logout-btn {
            background: rgba(212, 175, 55, 0.2);
            color: #d4af37;
            padding: 6px 16px;
            text-decoration: none;
            border-radius: 20px;
            font-size: 13px;
            margin-left: 8px;
            border: 1px solid rgba(212, 175, 55, 0.3);
            transition: all 0.3s;
        }
        .logout-btn:hover {
            background: #d4af37;
            color: #1a202c;
        }

        /* ============================================ */
        /* STATS CARDS - GLASS EFFECT */
        /* ============================================ */
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            padding: 20px 30px;
            border-radius: 16px;
            text-align: center;
            flex: 1;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.18);
            border-color: rgba(212, 175, 55, 0.3);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
        }
        .stat-card h3 {
            font-size: 32px;
            color: #d4af37;
            margin-bottom: 5px;
            font-weight: 700;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .stat-card p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            letter-spacing: 1px;
        }

        /* ============================================ */
        /* SECTIONS - GLASS EFFECT */
        /* ============================================ */
        .section {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .section h3 {
            color: #ffffff;
            margin-bottom: 1rem;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(212, 175, 55, 0.3);
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        /* ============================================ */
        /* TABLES */
        /* ============================================ */
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 13px;
        }
        th {
            background: rgba(212, 175, 55, 0.15);
            font-weight: 600;
            color: #d4af37;
        }
        td {
            color: rgba(255, 255, 255, 0.8);
        }
        tr:hover td {
            background: rgba(255, 255, 255, 0.05);
        }
        .btn {
            padding: 5px 14px;
            background: rgba(212, 175, 55, 0.2);
            color: #d4af37;
            text-decoration: none;
            border-radius: 20px;
            font-size: 12px;
            border: 1px solid rgba(212, 175, 55, 0.2);
            transition: all 0.3s;
            display: inline-block;
        }
        .btn:hover {
            background: #d4af37;
            color: #1a202c;
        }

        .badge {
            background: #e53e3e;
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 10px;
            margin-left: 5px;
        }

        /* ============================================ */
        /* MAP & DRIVERS */
        /* ============================================ */
        #map { 
            height: 400px; 
            width: 100%; 
            border-radius: 12px; 
            margin-bottom: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .driver-list { 
            display: flex; 
            gap: 10px; 
            flex-wrap: wrap; 
            margin-top: 15px; 
        }
        .driver-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .driver-item.active { 
            background: rgba(212, 175, 55, 0.2); 
            color: #d4af37;
            border-color: rgba(212, 175, 55, 0.3);
        }
        .info-text { 
            font-size: 12px; 
            color: rgba(255, 255, 255, 0.5); 
            margin-top: 10px; 
        }

        /* ============================================ */
        /* FOOTER */
        /* ============================================ */
        .footer {
            background: rgba(26, 32, 44, 0.8);
            backdrop-filter: blur(15px);
            color: #a0aec0;
            padding: 2rem;
            border-radius: 16px;
            margin-top: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
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
            min-width: 180px;
        }
        .footer-section h4 {
            color: #d4af37;
            margin-bottom: 1rem;
            font-size: 15px;
            letter-spacing: 1px;
        }
        .footer-section p {
            font-size: 13px;
            line-height: 1.8;
            margin-bottom: 4px;
            color: rgba(255, 255, 255, 0.5);
        }
        .footer-section a {
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
            transition: all 0.3s;
        }
        .footer-section a:hover {
            color: #d4af37;
        }
        .copyright {
            text-align: center;
            padding-top: 1.5rem;
            margin-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 12px;
            color: rgba(255, 255, 255, 0.3);
        }

        /* ============================================ */
        /* RESPONSIVE */
        /* ============================================ */
        @media (max-width: 768px) {
            .content { margin-left: 0; padding: 1rem; }
            .sidebar { display: none; }
            .stats { flex-direction: column; }
            .footer-content { flex-direction: column; text-align: center; }
            .header { flex-direction: column; text-align: center; }
            .header-right { text-align: center; }
            .header-right .clock { font-size: 22px; }
        }
    </style>
</head>
<body>

<!-- ============================================ -->
<!-- SLIDESHOW BACKGROUND -->
<!-- ============================================ -->
<div class="slideshow-container" id="slideshowContainer">
    <!-- Replace these with your own images -->
    <div class="slide active" style="background-image: url('images/unga-warehouse.jpg');"></div>
    <div class="slide" style="background-image: url('images/unga-trucks.jpg');"></div>
    <div class="slide" style="background-image: url('images/unga-delivery.jpg');"></div>
    <div class="slide" style="background-image: url('images/unga-office.jpg');"></div>
    <div class="slide" style="background-image: url('images/unga-logistics.jpg');"></div>
</div>
<div class="slide-overlay"></div>

<!-- ============================================ -->
<!-- SIDEBAR -->
<!-- ============================================ -->
<div class="sidebar">
    <h2>Unga <span>Logistics</span></h2>
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

<!-- ============================================ -->
<!-- CONTENT -->
<!-- ============================================ -->
<div class="content">

    <!-- HEADER WITH 24-HOUR CLOCK -->
    <div class="header">
        <div class="header-left">
            <h2>Welcome, <span><?php echo $_SESSION['username']; ?></span></h2>
            <div class="date"><?php echo date('l, d F Y'); ?></div>
        </div>
        <div class="header-right">
            <div class="clock">
                <span id="clockDisplay"><?php echo date('H:i'); ?></span>
                <span class="seconds" id="secondsDisplay"><?php echo date(':s'); ?></span>
            </div>
            <div class="admin-info">
                Admin Panel
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <!-- STATS -->
    <div class="stats">
        <div class="stat-card"><h3><?php echo $vehicles_count; ?></h3><p>Vehicles</p></div>
        <div class="stat-card"><h3><?php echo $deliveries_count; ?></h3><p>Total Deliveries</p></div>
        <div class="stat-card"><h3><?php echo $drivers_count; ?></h3><p>Drivers</p></div>
    </div>

    <!-- MAP SECTION -->
    <div class="section">
        <h3>📍 Live Driver Tracking</h3>
        <div id="map"></div>
        <div id="driverList" class="driver-list"></div>
        <p class="info-text">📍 Gold markers show active drivers. Updates every 10 seconds.</p>
    </div>

    <!-- RECENT DELIVERIES -->
    <div class="section">
        <h3>📦 Recent Deliveries</h3>
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
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['delivery_code']; ?></td>
                        <td><?php echo ucfirst($row['status']); ?></td>
                        <td><a href="view_delivery.php?id=<?php echo $row['id']; ?>" class="btn">View</a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- FOOTER -->
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
                <p>📧 customercare@unga.com</p>
                <p>📞 0709 772 000</p>
                <p>📞 0707 202020</p>
                <p>📞 020 7603000</p>
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

<!-- ============================================ -->
<!-- SCRIPTS -->
<!-- ============================================ -->
<script>
    // ============================================
    // 1. SLIDESHOW BACKGROUND
    // ============================================
    let slideIndex = 0;
    const slides = document.querySelectorAll('.slide');
    const totalSlides = slides.length;

    function changeSlide() {
        slides.forEach(slide => slide.classList.remove('active'));
        slideIndex = (slideIndex + 1) % totalSlides;
        slides[slideIndex].classList.add('active');
    }

    // Change slide every 6 seconds
    setInterval(changeSlide, 6000);

    // ============================================
    // 2. LIVE CLOCK (24-HOUR WITH SECONDS)
    // ============================================
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        
        document.getElementById('clockDisplay').textContent = hours + ':' + minutes;
        document.getElementById('secondsDisplay').textContent = ':' + seconds;
    }
    setInterval(updateClock, 1000);

    // ============================================
    // 3. LIVE DRIVER MAP
    // ============================================
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
                        // Gold marker for UNGA branding
                        let marker = L.marker([driver.lat, driver.lng], {
                            icon: L.divIcon({
                                className: 'custom-marker',
                                html: '🚛',
                                iconSize: [30, 30],
                                iconAnchor: [15, 30]
                            })
                        }).addTo(map);
                        
                        marker.bindTooltip(driver.driver_name, { 
                            permanent: true, 
                            direction: 'top', 
                            offset: [0, -20] 
                        });
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
