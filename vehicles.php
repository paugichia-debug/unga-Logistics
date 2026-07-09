<?php
session_name('ADMIN_SESSION');
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit();
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $result = mysqli_query($conn, "SELECT status FROM vehicles WHERE id = $id");
    $vehicle = mysqli_fetch_assoc($result);
    $new_status = ($vehicle['status'] == 'available') ? 'maintenance' : 'available';
    mysqli_query($conn, "UPDATE vehicles SET status = '$new_status' WHERE id = $id");
    header('Location: vehicles.php');
    exit();
}

// Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $driver = mysqli_fetch_assoc(mysqli_query($conn, "SELECT driver_id FROM vehicles WHERE id = $id"));
    if ($driver && $driver['driver_id']) {
        mysqli_query($conn, "UPDATE users SET vehicle_id = NULL WHERE id = {$driver['driver_id']}");
    }
    mysqli_query($conn, "DELETE FROM vehicles WHERE id = $id");
    header('Location: vehicles.php');
    exit();
}

// Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $plate = mysqli_real_escape_string($conn, $_POST['plate']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $capacity = $_POST['capacity'];
    $status = $_POST['status'];
    $fixed_cost = $_POST['fixed_cost'];
    $cost_per_km = $_POST['cost_per_km'];
    $driver_id = !empty($_POST['driver_id']) ? $_POST['driver_id'] : 'NULL';
    
    if (isset($_POST['id']) && $_POST['id'] != '') {
        $id = $_POST['id'];
        mysqli_query($conn, "UPDATE vehicles SET plate_number='$plate', vehicle_type='$type', capacity_tonnes=$capacity, status='$status', fixed_cost=$fixed_cost, cost_per_km=$cost_per_km, driver_id=$driver_id WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO vehicles (plate_number, vehicle_type, capacity_tonnes, status, fixed_cost, cost_per_km, driver_id) VALUES ('$plate', '$type', $capacity, '$status', $fixed_cost, $cost_per_km, $driver_id)");
    }
    header('Location: vehicles.php');
    exit();
}

$vehicles = mysqli_query($conn, "SELECT v.*, u.username as driver_name FROM vehicles v LEFT JOIN users u ON v.driver_id = u.id ORDER BY v.id");
$drivers = mysqli_query($conn, "SELECT id, username FROM users WHERE role = 'driver' ORDER BY username");
$edit_vehicle = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM vehicles WHERE id = $edit_id");
    $edit_vehicle = mysqli_fetch_assoc($result);
}

include 'header.php';
?>

<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
        <h3 style="margin: 0; color: #ffffff;">🚛 Vehicle Management</h3>
        <button onclick="document.getElementById('addForm').style.display='block'" class="btn" style="background: #d4af37; color: #1a202c; padding: 8px 20px;">+ Add Vehicle</button>
    </div>
    
    <!-- Add/Edit Form -->
    <div id="addForm" class="glass-card" style="display: <?php echo $edit_vehicle ? 'block' : 'none'; ?>; margin-bottom: 15px; padding: 20px;">
        <h3 style="color: #ffffff; margin-bottom: 15px;"><?php echo $edit_vehicle ? 'Edit Vehicle' : 'Add New Vehicle'; ?></h3>
        <form method="POST">
            <?php if($edit_vehicle): ?>
                <input type="hidden" name="id" value="<?php echo $edit_vehicle['id']; ?>">
            <?php endif; ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: block; margin-bottom: 5px;">Plate Number</label>
                    <input type="text" name="plate" required value="<?php echo $edit_vehicle['plate_number'] ?? ''; ?>" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: #ffffff; padding: 10px; border-radius: 8px; width: 100%;">
                </div>
                <div>
                    <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: block; margin-bottom: 5px;">Vehicle Type</label>
                    <input type="text" name="type" required value="<?php echo $edit_vehicle['vehicle_type'] ?? ''; ?>" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: #ffffff; padding: 10px; border-radius: 8px; width: 100%;">
                </div>
                <div>
                    <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: block; margin-bottom: 5px;">Capacity (tonnes)</label>
                    <input type="number" name="capacity" required value="<?php echo $edit_vehicle['capacity_tonnes'] ?? ''; ?>" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: #ffffff; padding: 10px; border-radius: 8px; width: 100%;">
                </div>
                <div>
                    <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: block; margin-bottom: 5px;">Status</label>
                    <select name="status" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: #ffffff; padding: 10px; border-radius: 8px; width: 100%;">
                        <option value="available" <?php echo ($edit_vehicle['status'] ?? '') == 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="maintenance" <?php echo ($edit_vehicle['status'] ?? '') == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        <option value="on_route" <?php echo ($edit_vehicle['status'] ?? '') == 'on_route' ? 'selected' : ''; ?>>On Route</option>
                    </select>
                </div>
                <div>
                    <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: block; margin-bottom: 5px;">Fixed Cost (KES)</label>
                    <input type="number" name="fixed_cost" required value="<?php echo $edit_vehicle['fixed_cost'] ?? ''; ?>" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: #ffffff; padding: 10px; border-radius: 8px; width: 100%;">
                </div>
                <div>
                    <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: block; margin-bottom: 5px;">Cost per km (KES)</label>
                    <input type="number" step="0.01" name="cost_per_km" required value="<?php echo $edit_vehicle['cost_per_km'] ?? ''; ?>" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: #ffffff; padding: 10px; border-radius: 8px; width: 100%;">
                </div>
                <div>
                    <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: block; margin-bottom: 5px;">Assign Driver</label>
                    <select name="driver_id" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: #ffffff; padding: 10px; border-radius: 8px; width: 100%;">
                        <option value="">-- Unassigned --</option>
                        <?php 
                        mysqli_data_seek($drivers, 0);
                        while($driver = mysqli_fetch_assoc($drivers)): ?>
                            <option value="<?php echo $driver['id']; ?>" <?php echo ($edit_vehicle['driver_id'] ?? '') == $driver['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($driver['username']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div style="margin-top: 15px; display: flex; gap: 10px;">
                <button type="submit" class="btn" style="background: #d4af37; color: #1a202c; padding: 10px 25px;">Save Vehicle</button>
                <button type="button" onclick="document.getElementById('addForm').style.display='none'" class="btn btn-danger" style="padding: 10px 25px;">Cancel</button>
            </div>
        </form>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Plate Number</th>
                    <th>Type</th>
                    <th>Capacity (t)</th>
                    <th>Driver</th>
                    <th>Status</th>
                    <th>Fixed Cost</th>
                    <th>Cost/km</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                mysqli_data_seek($vehicles, 0);
                $sn = 1;
                while($row = mysqli_fetch_assoc($vehicles)): 
                ?>
                <tr>
                    <td><?php echo $sn; ?></td>
                    <td><strong><?php echo $row['plate_number']; ?></strong></td>
                    <td><?php echo $row['vehicle_type']; ?></td>
                    <td><?php echo number_format($row['capacity_tonnes']); ?> t</td>
                    <td><?php echo $row['driver_name'] ?? '—'; ?></td>
                    <td>
                        <span style="display: inline-block; padding: 2px 12px; border-radius: 20px; font-size: 12px; background: <?php echo $row['status'] == 'available' ? 'rgba(72, 187, 120, 0.2)' : ($row['status'] == 'maintenance' ? 'rgba(229, 62, 62, 0.2)' : 'rgba(66, 153, 225, 0.2)'); ?>; color: <?php echo $row['status'] == 'available' ? '#68d391' : ($row['status'] == 'maintenance' ? '#fc8181' : '#63b3ed'); ?>;">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                    </td>
                    <td>KES <?php echo number_format($row['fixed_cost']); ?></td>
                    <td>KES <?php echo number_format($row['cost_per_km'], 2); ?></td>
                    <td>
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <?php if($row['status'] == 'available'): ?>
                                <a href="vehicles.php?toggle=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm" style="padding: 3px 10px; font-size: 11px; background: rgba(237, 137, 54, 0.2); color: #f6ad55; border: 1px solid rgba(237, 137, 54, 0.2);">Maint</a>
                            <?php else: ?>
                                <a href="vehicles.php?toggle=<?php echo $row['id']; ?>" class="btn btn-sm" style="padding: 3px 10px; font-size: 11px;">Active</a>
                            <?php endif; ?>
                            <a href="vehicles.php?edit=<?php echo $row['id']; ?>" class="btn btn-sm" style="padding: 3px 10px; font-size: 11px;">Edit</a>
                            <a href="vehicles.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" style="padding: 3px 10px; font-size: 11px; background: rgba(229, 62, 62, 0.2); color: #fc8181; border: 1px solid rgba(229, 62, 62, 0.2);" onclick="return confirm('Delete this vehicle?')">Del</a>
                        </div>
                    </td>
                </tr>
                <?php 
                $sn++;
                endwhile; 
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
