<?php
session_name('ADMIN_SESSION');
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit();
}

// Handle add driver
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_driver'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = md5($_POST['password']);
    
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Email already exists!";
    } else {
        mysqli_query($conn, "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$password', 'driver')");
        $success = "Driver added successfully!";
        header('Location: drivers.php');
        exit();
    }
}

// Handle delete driver
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "UPDATE vehicles SET driver_id = NULL WHERE driver_id = $id");
    mysqli_query($conn, "DELETE FROM users WHERE id = $id AND role = 'driver'");
    header('Location: drivers.php');
    exit();
}

// Get all drivers
$drivers = mysqli_query($conn, "SELECT u.*, COUNT(d.id) as delivery_count 
    FROM users u 
    LEFT JOIN deliveries d ON u.id = d.driver_id 
    WHERE u.role = 'driver' 
    GROUP BY u.id 
    ORDER BY u.id");

include 'header.php';
?>

<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
        <h3 style="margin: 0; color: #ffffff;">👨‍✈️ Driver Management</h3>
        <button onclick="document.getElementById('addForm').style.display='block'" class="btn" style="background: #d4af37; color: #1a202c; padding: 8px 20px; border: none; border-radius: 8px; cursor: pointer;">+ Add New Driver</button>
    </div>
    
    <!-- Add Driver Form -->
    <div id="addForm" class="glass-card" style="display: none; margin-bottom: 15px; padding: 20px;">
        <h3 style="color: #ffffff; margin-bottom: 15px;">Add New Driver</h3>
        <?php if(isset($error)): ?>
            <div style="background: rgba(229,62,62,0.2); color: #fc8181; padding: 10px; border-radius: 8px; margin-bottom: 15px;"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: block; margin-bottom: 5px;">Full Name *</label>
                    <input type="text" name="username" required style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: #ffffff; padding: 10px; border-radius: 8px; width: 100%;">
                </div>
                <div>
                    <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: block; margin-bottom: 5px;">Email *</label>
                    <input type="email" name="email" required style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: #ffffff; padding: 10px; border-radius: 8px; width: 100%;">
                </div>
            </div>
            <div style="margin-top: 15px;">
                <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: block; margin-bottom: 5px;">Password *</label>
                <input type="password" name="password" required style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: #ffffff; padding: 10px; border-radius: 8px; width: 100%;">
            </div>
            <div style="margin-top: 15px; display: flex; gap: 10px;">
                <button type="submit" name="add_driver" class="btn" style="background: #d4af37; color: #1a202c; padding: 10px 25px; border: none; border-radius: 8px; cursor: pointer;">Save Driver</button>
                <button type="button" onclick="document.getElementById('addForm').style.display='none'" class="btn btn-danger" style="padding: 10px 25px; border: none; border-radius: 8px; cursor: pointer; background: rgba(229,62,62,0.2); color: #fc8181;">Cancel</button>
            </div>
        </form>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Deliveries</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $display_id = 1;
                while($row = mysqli_fetch_assoc($drivers)): 
                ?>
                <tr>
                    <td><?php echo $display_id; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td>
                        <span style="display: inline-block; padding: 2px 12px; border-radius: 20px; font-size: 12px; background: rgba(212,175,55,0.2); color: #d4af37;">
                            <?php echo $row['delivery_count']; ?>
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <a href="drivers.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" style="padding: 3px 10px; font-size: 11px; background: rgba(229,62,62,0.2); color: #fc8181;" onclick="return confirm('Delete this driver?')">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php 
                $display_id++;
                endwhile; 
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
