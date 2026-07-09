<?php
error_reporting(E_ALL & ~E_DEPRECATED);
session_name('ADMIN_SESSION');
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit();
}

$filter_type = isset($_GET['type']) ? $_GET['type'] : 'daily';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$stats_query = "SELECT 
    COUNT(*) as total_deliveries,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed,
    COALESCE(SUM(penalty_amount), 0) as total_penalties,
    SUM(CASE WHEN penalty_amount > 0 THEN 1 ELSE 0 END) as late_deliveries
    FROM deliveries 
    WHERE delivered_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";

$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// FIXED: Added vehicle details
$penalty_query = mysqli_query($conn, "SELECT 
    d.*, 
    c.name as customer_name, 
    u.username as driver_name,
    v.plate_number,
    v.vehicle_type,
    v.capacity_tonnes
    FROM deliveries d 
    LEFT JOIN customers c ON d.customer_id = c.id 
    LEFT JOIN users u ON d.driver_id = u.id 
    LEFT JOIN vehicles v ON d.vehicle_id = v.id 
    WHERE d.penalty_amount > 0 
    AND d.delivered_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
    ORDER BY d.penalty_amount DESC");

if ($filter_type == 'daily') {
    $group_by = "DATE(delivered_at)";
    $period_label = "Day";
} elseif ($filter_type == 'weekly') {
    $group_by = "YEARWEEK(delivered_at)";
    $period_label = "Week";
} else {
    $group_by = "DATE_FORMAT(delivered_at, '%Y-%m')";
    $period_label = "Month";
}

$summary_query = mysqli_query($conn, "SELECT 
    $group_by as period,
    COUNT(*) as total,
    COALESCE(SUM(penalty_amount), 0) as penalties,
    COUNT(CASE WHEN penalty_amount > 0 THEN 1 END) as late_count
    FROM deliveries 
    WHERE delivered_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
    GROUP BY $group_by
    ORDER BY period DESC");

include 'header.php';
?>

<!-- Stats -->
<div class="stats">
    <div class="stat-card"><h3><?php echo $stats['total_deliveries'] ?? 0; ?></h3><p>Total Deliveries</p></div>
    <div class="stat-card"><h3><?php echo $stats['completed'] ?? 0; ?></h3><p>Completed</p></div>
    <div class="stat-card"><h3><?php echo $stats['late_deliveries'] ?? 0; ?></h3><p>Late Deliveries</p></div>
    <div class="stat-card"><h3 style="color: #fc8181;">KES <?php echo number_format($stats['total_penalties'] ?? 0); ?></h3><p>Total Penalties</p></div>
</div>

<!-- Filter Bar -->
<div class="glass-card">
    <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
        <div>
            <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: block; margin-bottom: 3px;">Report Type</label>
            <select name="type" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: #ffffff; padding: 8px 12px; border-radius: 8px;">
                <option value="daily" <?php echo $filter_type == 'daily' ? 'selected' : ''; ?>>Daily</option>
                <option value="weekly" <?php echo $filter_type == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                <option value="monthly" <?php echo $filter_type == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
            </select>
        </div>
        <div>
            <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: block; margin-bottom: 3px;">Start Date</label>
            <input type="date" name="start_date" value="<?php echo $start_date; ?>" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: #ffffff; padding: 8px 12px; border-radius: 8px;">
        </div>
        <div>
            <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: block; margin-bottom: 3px;">End Date</label>
            <input type="date" name="end_date" value="<?php echo $end_date; ?>" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: #ffffff; padding: 8px 12px; border-radius: 8px;">
        </div>
        <div style="margin-top: 18px;">
            <button type="submit" class="btn" style="background: #d4af37; color: #1a202c; padding: 8px 25px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">Apply Filter</button>
            <a href="download_report_pdf.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn" style="background: #e53e3e; color: white; padding: 8px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; font-weight: bold;">📄 Download PDF</a>
        </div>
    </form>
</div>

<!-- Summary Table -->
<div class="glass-card">
    <h3>📈 <?php echo ucfirst($filter_type); ?> Summary</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th><?php echo $period_label; ?></th>
                    <th>Deliveries</th>
                    <th>Late Deliveries</th>
                    <th>Penalties (KES)</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($summary_query) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($summary_query)): ?>
                    <tr>
                        <td><?php echo $row['period']; ?></td>
                        <td><?php echo $row['total']; ?></td>
                        <td>
                            <span style="display: inline-block; padding: 2px 12px; border-radius: 20px; font-size: 12px; <?php echo $row['late_count'] > 0 ? 'background: rgba(229,62,62,0.2); color: #fc8181;' : 'background: rgba(72,187,120,0.2); color: #68d391;'; ?>">
                                <?php echo $row['late_count']; ?>
                            </span>
                        </td>
                        <td style="color: #fc8181;">KES <?php echo number_format($row['penalties']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align: center; color: rgba(255,255,255,0.5); padding: 30px;">No data for selected period</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Penalties Table -->
<div class="glass-card">
    <h3>⚠️ Late Delivery Penalties</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Delivery Code</th>
                    <th>Customer</th>
                    <th>Driver</th>
                    <th>Vehicle</th>
                    <th>Delivered At</th>
                    <th>Deadline</th>
                    <th>Penalty (KES)</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($penalty_query) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($penalty_query)): ?>
                    <tr>
                        <td><strong><?php echo $row['delivery_code']; ?></strong></td>
                        <td style="word-break: break-word;"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                        <td><?php echo $row['driver_name'] ?? 'Unassigned'; ?></td>
                        <td>
                            <?php if($row['plate_number']): ?>
                                <strong><?php echo $row['plate_number']; ?></strong>
                                <br><small style="color: rgba(255,255,255,0.5);"><?php echo ucfirst($row['vehicle_type'] ?? ''); ?> (<?php echo $row['capacity_tonnes'] ?? '?'; ?>t)</small>
                            <?php else: ?>
                                <span style="color: rgba(255,255,255,0.3);">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($row['delivered_at'])); ?></td>
                        <td><?php echo $row['time_window_end'] ? date('H:i', strtotime($row['time_window_end'])) : 'N/A'; ?></td>
                        <td style="color: #fc8181; font-weight: bold;">KES <?php echo number_format($row['penalty_amount']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align: center; color: rgba(255,255,255,0.5); padding: 30px;">✅ No penalties recorded in this period. All deliveries on time!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Summary Note -->
<div class="glass-card">
    <h3>📝 Summary Note</h3>
    <div style="color: rgba(255,255,255,0.7); font-size: 14px; line-height: 1.8;">
        <p><strong>Report Period:</strong> <?php echo date('d/m/Y', strtotime($start_date)); ?> to <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
        <p><strong>Total Penalty Amount:</strong> <span style="color: #fc8181;">KES <?php echo number_format($stats['total_penalties'] ?? 0); ?></span></p>
        <p><strong>On-Time Performance:</strong> 
            <?php 
            $on_time = ($stats['total_deliveries'] ?? 0) - ($stats['late_deliveries'] ?? 0);
            $percentage = ($stats['total_deliveries'] ?? 0) > 0 ? round(($on_time / $stats['total_deliveries']) * 100, 1) : 0;
            $color = $percentage >= 90 ? '#68d391' : ($percentage >= 70 ? '#f6ad55' : '#fc8181');
            ?>
            <span style="color: <?php echo $color; ?>; font-weight: bold;"><?php echo $percentage; ?>%</span> 
            (<?php echo $on_time; ?> out of <?php echo $stats['total_deliveries'] ?? 0; ?> deliveries on time)
        </p>
    </div>
</div>

<?php include 'footer.php'; ?>
