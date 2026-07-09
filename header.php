<?php
// check if session exists
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unga Logistics - Admin</title>
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
        .slide.active { opacity: 1; }
        .slide-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            background: rgba(26, 32, 44, 0.75);
            backdrop-filter: blur(3px);
        }
        .sidebar {
            background: rgba(26, 32, 44, 0.92);
            width: 250px;
            position: fixed;
            height: 100%;
            padding: 2rem 1rem;
            z-index: 10;
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(212, 175, 55, 0.2);
            overflow-y: auto;
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
        .badge {
            background: #e53e3e;
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 10px;
            margin-left: 5px;
        }
        .content { 
            margin-left: 250px; 
            padding: 2rem;
            position: relative;
            z-index: 2;
            min-height: 100vh;
        }
        .header {
            background: rgba(255, 255, 255, 0.10);
            backdrop-filter: blur(20px);
            padding: 1.2rem 2rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.10);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        .header-left h2 {
            color: #ffffff;
            font-size: 20px;
            font-weight: 600;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .header-left h2 span { color: #d4af37; }
        .header-left .date {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 2px;
        }
        .header-right { text-align: right; }
        .header-right .clock {
            font-size: 26px;
            font-weight: 700;
            color: #ffffff;
            font-variant-numeric: tabular-nums;
            text-shadow: 0 2px 15px rgba(0,0,0,0.4);
            letter-spacing: 2px;
        }
        .header-right .clock .seconds {
            font-size: 16px;
            color: #d4af37;
            font-weight: 400;
        }
        .header-right .admin-info {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.5);
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
        .glass-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .glass-card h3 {
            color: #ffffff;
            margin-bottom: 1rem;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(212, 175, 55, 0.3);
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
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
        td { color: rgba(255, 255, 255, 0.8); }
        tr:hover td { background: rgba(255, 255, 255, 0.05); }
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
        .btn-danger {
            background: rgba(229, 62, 62, 0.2);
            color: #fc8181;
            border-color: rgba(229, 62, 62, 0.2);
        }
        .btn-danger:hover {
            background: #e53e3e;
            color: white;
        }
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.10);
            backdrop-filter: blur(15px);
            padding: 20px 30px;
            border-radius: 16px;
            text-align: center;
            flex: 1;
            min-width: 150px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.18);
            border-color: rgba(212, 175, 55, 0.3);
        }
        .stat-card h3 {
            font-size: 32px;
            color: #d4af37;
            margin-bottom: 5px;
            font-weight: 700;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .stat-card p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
            letter-spacing: 1px;
        }
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
        .footer-section { flex: 1; min-width: 180px; }
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
            color: rgba(255, 255, 255, 0.4);
        }
        .footer-section a {
            color: rgba(255, 255, 255, 0.4);
            text-decoration: none;
            transition: all 0.3s;
        }
        .footer-section a:hover { color: #d4af37; }
        .copyright {
            text-align: center;
            padding-top: 1.5rem;
            margin-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 12px;
            color: rgba(255, 255, 255, 0.25);
        }
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

<div class="slideshow-container" id="slideshowContainer">
    <div class="slide active" style="background-image: url('images/unga-warehouse.jpg');"></div>
    <div class="slide" style="background-image: url('images/unga-trucks.jpg');"></div>
    <div class="slide" style="background-image: url('images/unga-delivery.jpg');"></div>
    <div class="slide" style="background-image: url('images/unga-office.jpg');"></div>
    <div class="slide" style="background-image: url('images/unga-logistics.jpg');"></div>
</div>
<div class="slide-overlay"></div>

<div class="sidebar">
    <h2>Unga <span>Logistics</span></h2>
    <a href="admin_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
    <a href="vehicles.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'vehicles.php' ? 'active' : ''; ?>">Vehicles</a>
    <a href="deliveries.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'deliveries.php' ? 'active' : ''; ?>">Deliveries</a>
    <a href="drivers.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'drivers.php' ? 'active' : ''; ?>">Drivers</a>
    <a href="ga_optimize.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'ga_optimize.php' ? 'active' : ''; ?>">GA Optimization</a>
    <a href="admin_notifications.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_notifications.php' ? 'active' : ''; ?>">Notifications</a>
    <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">Reports</a>
    <a href="admin_issues.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_issues.php' ? 'active' : ''; ?>">Issues</a>
    <a href="logout.php">Logout</a>
</div>

<div class="content">

    <div class="header">
        <div class="header-left">
            <h2>Welcome, <span><?php echo $_SESSION['username'] ?? 'Admin'; ?></span></h2>
            <div class="date"><?php echo date('l, d F Y'); ?></div>
        </div>
        <div class="header-right">
            <div class="clock">
                <span id="clockDisplay"><?php echo date('H:i'); ?></span>
                <span class="seconds" id="secondsDisplay"><?php echo ':'.date('s'); ?></span>
            </div>
            <div class="admin-info">
                Admin Panel
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>
