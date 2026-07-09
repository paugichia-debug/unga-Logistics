<?php
session_name('ADMIN_SESSION');
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit();
}

// Handle Add Delivery AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_delivery') {
    $customer_id = intval($_POST['customer_id']);
    $goods_type = mysqli_real_escape_string($conn, $_POST['goods_type']);
    $weight_tonnes = floatval($_POST['weight_tonnes']);
    $delivery_date = mysqli_real_escape_string($conn, $_POST['delivery_date']);
    $time_window_start = mysqli_real_escape_string($conn, $_POST['time_window_start']);
    $time_window_end = mysqli_real_escape_string($conn, $_POST['time_window_end']);
    
    $code_query = mysqli_query($conn, "SELECT MAX(id) as last_id FROM deliveries");
    $last = mysqli_fetch_assoc($code_query);
    $new_id = $last['last_id'] + 1;
    $delivery_code = 'DEL-' . str_pad($new_id, 3, '0', STR_PAD_LEFT);
    
    $query = "INSERT INTO deliveries (delivery_code, customer_id, goods_type, weight_tonnes, delivery_date, time_window_start, time_window_end, status) 
              VALUES ('$delivery_code', $customer_id, '$goods_type', $weight_tonnes, '$delivery_date', '$time_window_start', '$time_window_end', 'pending')";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Delivery added successfully', 'delivery_code' => $delivery_code]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
    exit();
}

// Handle AJAX customer search
if (isset($_GET['action']) && $_GET['action'] === 'search_customers') {
    $search = mysqli_real_escape_string($conn, $_GET['term']);
    $query = "SELECT id, name FROM customers WHERE name LIKE '%$search%' ORDER BY name LIMIT 10";
    $result = mysqli_query($conn, $query);
    $customers = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $customers[] = ['id' => $row['id'], 'name' => $row['name']];
    }
    echo json_encode($customers);
    exit();
}

$deliveries = mysqli_query($conn, "SELECT d.id, d.delivery_code, d.weight_tonnes, d.delivery_date, d.time_window_start, d.time_window_end, d.status, c.name as customer_name 
    FROM deliveries d 
    LEFT JOIN customers c ON d.customer_id = c.id 
    ORDER BY d.id DESC");

include 'header.php';
?>

<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
        <h3 style="margin: 0; color: #ffffff;">📦 Delivery Management</h3>
        <button onclick="openModal()" class="btn" style="background: #d4af37; color: #1a202c; padding: 8px 20px;">+ Add New Delivery</button>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Delivery Code</th>
                    <th>Customer</th>
                    <th>Weight (t)</th>
                    <th>Delivery Date</th>
                    <th>Time Window</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($deliveries)): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><strong><?php echo $row['delivery_code']; ?></strong></td>
                    <td style="max-width: 200px; word-break: break-word; white-space: normal;"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                    <td><?php echo $row['weight_tonnes']; ?> t</td>
                    <td><?php echo $row['delivery_date'] ?: '—'; ?></td>
                    <td>
                        <?php 
                        if($row['time_window_start'] && $row['time_window_end']) {
                            echo date('H:i', strtotime($row['time_window_start'])) . ' - ' . date('H:i', strtotime($row['time_window_end']));
                        } else {
                            echo '—';
                        }
                        ?>
                    </td>
                    <td>
                        <span style="display: inline-block; padding: 2px 12px; border-radius: 20px; font-size: 12px; 
                            <?php 
                            if($row['status'] == 'delivered') echo 'background: rgba(72,187,120,0.2); color: #68d391;';
                            elseif($row['status'] == 'pending') echo 'background: rgba(237,137,54,0.2); color: #f6ad55;';
                            elseif($row['status'] == 'assigned') echo 'background: rgba(66,153,225,0.2); color: #63b3ed;';
                            else echo 'background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.5);';
                            ?>
                        ">
                            <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                        </span>
                    </td>
                    <td>
                        <a href="view.php?id=<?php echo $row['id']; ?>" class="btn btn-sm" style="padding: 3px 12px; font-size: 11px;">View</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Delivery Modal -->
<div id="deliveryModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6);">
    <div style="background: #1a202c; margin: 5% auto; padding: 2rem; width: 500px; border-radius: 16px; border: 1px solid rgba(212, 175, 55, 0.2); max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="color: #ffffff;">Add New Delivery</h3>
            <span onclick="closeModal()" style="font-size: 28px; cursor: pointer; color: #a0aec0;">&times;</span>
        </div>
        <form id="addDeliveryForm">
            <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: block; margin-bottom: 5px;">Customer *</label>
            <div class="autocomplete-container">
                <input type="text" id="customerSearch" placeholder="Type customer name..." autocomplete="off" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: #ffffff; padding: 10px; border-radius: 8px; width: 100%;">
                <input type="hidden" id="customer_id" name="customer_id" required>
                <div id="autocompleteResults" class="autocomplete-results" style="display: none; background: #2d3748; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; max-height: 200px; overflow-y: auto; position: absolute; width: 100%; z-index: 1001;"></div>
            </div>
            <div id="selectedCustomer" class="selected-customer" style="display: none; background: rgba(212, 175, 55, 0.15); padding: 10px; border-radius: 8px; margin: 10px 0; color: #d4af37;"></div>
            
            <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: block; margin-bottom: 5px; margin-top: 10px;">Goods Type</label>
            <input type="text" name="goods_type" value="Maize Flour" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: #ffffff; padding: 10px; border-radius: 8px; width: 100%;">
            
            <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: block; margin-bottom: 5px; margin-top: 10px;">Weight (tonnes) *</label>
            <input type="number" name="weight_tonnes" step="0.01" required placeholder="Weight in tonnes" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: #ffffff; padding: 10px; border-radius: 8px; width: 100%;">
            
            <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: block; margin-bottom: 5px; margin-top: 10px;">Delivery Date *</label>
            <input type="date" name="delivery_date" required style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: #ffffff; padding: 10px; border-radius: 8px; width: 100%;">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: block; margin-bottom: 5px; margin-top: 10px;">Time Window Start *</label>
                    <input type="time" name="time_window_start" value="08:00" required style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: #ffffff; padding: 10px; border-radius: 8px; width: 100%;">
                </div>
                <div>
                    <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: block; margin-bottom: 5px; margin-top: 10px;">Time Window End *</label>
                    <input type="time" name="time_window_end" value="17:00" required style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: #ffffff; padding: 10px; border-radius: 8px; width: 100%;">
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" onclick="closeModal()" style="background: rgba(255,255,255,0.1); color: #a0aec0; padding: 10px 25px; border: none; border-radius: 8px; cursor: pointer;">Cancel</button>
                <button type="submit" style="background: #d4af37; color: #1a202c; padding: 10px 25px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">Add Delivery</button>
            </div>
        </form>
    </div>
</div>

<div id="toast" style="position: fixed; bottom: 20px; right: 20px; background: #48bb78; color: white; padding: 12px 20px; border-radius: 8px; display: none; z-index: 1001;"></div>

<script>
    const modal = document.getElementById('deliveryModal');
    const toast = document.getElementById('toast');
    let searchTimeout;
    
    function openModal() {
        modal.style.display = 'block';
        document.getElementById('addDeliveryForm').reset();
        document.getElementById('customerSearch').value = '';
        document.getElementById('customer_id').value = '';
        document.getElementById('selectedCustomer').style.display = 'none';
        document.getElementById('autocompleteResults').style.display = 'none';
        const today = new Date().toISOString().split('T')[0];
        document.querySelector('input[name="delivery_date"]').value = today;
        document.querySelector('input[name="time_window_start"]').value = '08:00';
        document.querySelector('input[name="time_window_end"]').value = '17:00';
    }
    
    function closeModal() {
        modal.style.display = 'none';
    }
    
    function showToast(message, isError = false) {
        toast.textContent = message;
        toast.style.background = isError ? '#e53e3e' : '#48bb78';
        toast.style.display = 'block';
        setTimeout(() => {
            toast.style.display = 'none';
        }, 3000);
    }
    
    const searchInput = document.getElementById('customerSearch');
    const resultsDiv = document.getElementById('autocompleteResults');
    const customerIdInput = document.getElementById('customer_id');
    const selectedDiv = document.getElementById('selectedCustomer');
    
    searchInput.addEventListener('input', function() {
        const term = this.value.trim();
        
        if (term.length < 2) {
            resultsDiv.style.display = 'none';
            return;
        }
        
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            fetch(`?action=search_customers&term=${encodeURIComponent(term)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        resultsDiv.innerHTML = data.map(c => `<div style="padding: 10px; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.05); color: #cbd5e1;" data-id="${c.id}" data-name="${c.name}">${c.name}</div>`).join('');
                        resultsDiv.style.display = 'block';
                    } else {
                        resultsDiv.innerHTML = '<div style="padding: 10px; color: #718096;">No customers found</div>';
                        resultsDiv.style.display = 'block';
                    }
                });
        }, 300);
    });
    
    resultsDiv.addEventListener('click', function(e) {
        const div = e.target.closest('div');
        if (div && div.dataset.id) {
            const id = div.dataset.id;
            const name = div.dataset.name;
            searchInput.value = name;
            customerIdInput.value = id;
            selectedDiv.innerHTML = '✅ Selected: <strong>' + name + '</strong>';
            selectedDiv.style.display = 'block';
            resultsDiv.style.display = 'none';
        }
    });
    
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
            resultsDiv.style.display = 'none';
        }
    });
    
    document.getElementById('addDeliveryForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!customerIdInput.value) {
            showToast('Please select a customer from the search results', true);
            return;
        }
        
        const formData = new FormData(this);
        formData.append('action', 'add_delivery');
        
        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                showToast('✅ ' + result.delivery_code + ' added successfully!');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showToast('Error: ' + result.message, true);
            }
        } catch (error) {
            showToast('Error adding delivery', true);
        }
    });
    
    window.onclick = function(event) {
        if (event.target === modal) {
            closeModal();
        }
    }
</script>

<?php include 'footer.php'; ?>
