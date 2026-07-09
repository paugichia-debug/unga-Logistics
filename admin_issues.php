<?php
session_name('ADMIN_SESSION');
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit();
}

if (isset($_GET['resolve'])) {
    $id = intval($_GET['resolve']);
    mysqli_query($conn, "UPDATE driver_issues SET status = 'resolved' WHERE id = $id");
    header('Location: admin_issues.php');
    exit();
}

$issues = mysqli_query($conn, "SELECT i.*, u.username as driver_name 
    FROM driver_issues i
    LEFT JOIN users u ON i.driver_id = u.id
    ORDER BY i.created_at DESC");

// Count pending issues
$pending_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM driver_issues WHERE status = 'pending'");
$pending = mysqli_fetch_assoc($pending_result);
$pending_count = $pending['count'];

include 'header.php';
?>

<!-- Stats -->
<div class="stats">
    <div class="stat-card"><h3><?php echo mysqli_num_rows($issues); ?></h3><p>Total Issues</p></div>
    <div class="stat-card"><h3 style="color: #fc8181;"><?php echo $pending_count; ?></h3><p>Pending</p></div>
</div>

<!-- Issues List -->
<div class="glass-card">
    <h3>⚠️ Driver Issue Reports</h3>
    
    <?php if(mysqli_num_rows($issues) == 0): ?>
        <div style="text-align: center; padding: 40px; color: rgba(255,255,255,0.5);">
            ✅ No issues reported yet. All drivers are on track.
        </div>
    <?php else: ?>
        <?php while($row = mysqli_fetch_assoc($issues)): ?>
        <div style="background: rgba(255,255,255,0.05); border-radius: 12px; padding: 15px; margin-bottom: 15px; border-left: <?php echo $row['status'] == 'pending' ? '4px solid #fc8181' : '4px solid #68d391'; ?>;">
            <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 10px;">
                <div style="flex: 1;">
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 8px;">
                        <span style="display: inline-block; padding: 2px 14px; border-radius: 20px; font-size: 12px; 
                            <?php 
                            if($row['issue_type'] == 'mechanical') echo 'background: rgba(229,62,62,0.2); color: #fc8181;';
                            elseif($row['issue_type'] == 'traffic') echo 'background: rgba(237,137,54,0.2); color: #f6ad55;';
                            elseif($row['issue_type'] == 'accident') echo 'background: rgba(229,62,62,0.3); color: #fc8181;';
                            elseif($row['issue_type'] == 'weather') echo 'background: rgba(66,153,225,0.2); color: #63b3ed;';
                            else echo 'background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.6);';
                            ?>
                        ">
                            <?php echo ucfirst($row['issue_type']); ?>
                        </span>
                        <span style="display: inline-block; padding: 2px 14px; border-radius: 20px; font-size: 12px; 
                            <?php echo $row['status'] == 'pending' ? 'background: rgba(229,62,62,0.2); color: #fc8181;' : 'background: rgba(72,187,120,0.2); color: #68d391;'; ?>
                        ">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                    </div>
                    <div style="color: #ffffff; font-size: 14px; margin-bottom: 5px;">
                        <strong>Driver:</strong> <?php echo htmlspecialchars($row['driver_name']); ?>
                    </div>
                    <div style="color: rgba(255,255,255,0.7); font-size: 13px; margin-bottom: 5px;">
                        <strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($row['description'])); ?>
                    </div>
                    <div style="font-size: 11px; color: rgba(255,255,255,0.4);">
                        Reported: <?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?>
                    </div>
                </div>
                <div>
                    <?php if($row['status'] == 'pending'): ?>
                    <a href="?resolve=<?php echo $row['id']; ?>" class="btn" style="background: #48bb78; color: white; padding: 6px 18px; border: none; border-radius: 20px; cursor: pointer; text-decoration: none; font-size: 13px;" onclick="return confirm('Mark this issue as resolved?')">✓ Mark Resolved</a>
                    <?php else: ?>
                    <span style="color: rgba(255,255,255,0.3); font-size: 13px;">✅ Resolved</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
