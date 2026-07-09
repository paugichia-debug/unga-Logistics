<?php
session_name('ADMIN_SESSION');
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit();
}

$message = '';
$route_details = [];

function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $R = 6371;
    $lat1 = deg2rad($lat1);
    $lat2 = deg2rad($lat2);
    $dlat = deg2rad($lat2 - $lat1);
    $dlng = deg2rad($lng2 - $lng1);
    $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlng/2) * sin($dlng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return round($R * $c, 1);
}

function getBestVehicle($weight, $vehicles) {
    $best = null;
    foreach ($vehicles as $v) {
        if ($weight <= $v['capacity_tonnes']) {
            if ($best === null || $v['capacity_tonnes'] < $best['capacity_tonnes']) {
                $best = $v;
            }
        }
    }
    return $best;
}

if (isset($_POST['assign_routes'])) {
    $assigned = 0;
    $to_assign = mysqli_query($conn, "SELECT d.id, d.driver_id, d.delivery_code, c.name as customer_name 
        FROM deliveries d LEFT JOIN customers c ON d.customer_id = c.id 
        WHERE d.delivery_date = CURDATE() AND d.status = 'pending' AND d.vehicle_id IS NOT NULL");
    while ($row = mysqli_fetch_assoc($to_assign)) {
        mysqli_query($conn, "UPDATE deliveries SET status = 'assigned' WHERE id = " . $row['id']);
        if ($row['driver_id']) {
            $notify_message = "New delivery assigned: " . $row['delivery_code'] . " to " . $row['customer_name'];
            mysqli_query($conn, "INSERT INTO driver_notifications (driver_id, message, delivery_id) VALUES (" . $row['driver_id'] . ", '" . mysqli_real_escape_string($conn, $notify_message) . "', " . $row['id'] . ")");
        }
        $assigned++;
    }
    $message = "✅ $assigned deliveries assigned successfully! Drivers notified.";
}

if (isset($_POST['run_ga'])) {
    mysqli_query($conn, "UPDATE deliveries SET vehicle_id = NULL, driver_id = NULL, status = 'pending' WHERE delivery_date = CURDATE()");
    
    $deliveries = mysqli_query($conn, "SELECT d.*, c.name as customer_name, c.address, c.lat, c.lng 
        FROM deliveries d LEFT JOIN customers c ON d.customer_id = c.id 
        WHERE d.status = 'pending' AND d.delivery_date = CURDATE() ORDER BY d.id");
    
    $delivery_list = [];
    while ($row = mysqli_fetch_assoc($deliveries)) {
        $delivery_list[] = $row;
    }
    
    $vehicles = mysqli_query($conn, "SELECT v.*, u.id as driver_id, u.username as driver_name 
        FROM vehicles v LEFT JOIN users u ON v.driver_id = u.id 
        WHERE v.status = 'available' ORDER BY v.capacity_tonnes ASC");
    
    $vehicle_list = [];
    while ($row = mysqli_fetch_assoc($vehicles)) {
        $vehicle_list[] = $row;
    }
    
    if (count($delivery_list) > 0 && count($vehicle_list) > 0) {
        $tempRoutes = [];
        
        $groups = [];
        foreach ($delivery_list as $d) {
            preg_match('/\(([^)]+)\)/', $d['customer_name'], $matches);
            $county = isset($matches[1]) ? $matches[1] : 'Other';
            $groups[$county][] = $d;
        }
        
        foreach ($groups as $county => $deliveriesInGroup) {
            usort($deliveriesInGroup, function($a, $b) {
                return $b['weight_tonnes'] <=> $a['weight_tonnes'];
            });
            
            $currentRoute = [];
            $currentWeight = 0;
            
            foreach ($deliveriesInGroup as $delivery) {
                if ($currentWeight + $delivery['weight_tonnes'] > 30 || count($currentRoute) >= 5) {
                    if (!empty($currentRoute)) {
                        $tempRoutes[] = $currentRoute;
                        $currentRoute = [];
                        $currentWeight = 0;
                    }
                }
                $currentRoute[] = $delivery;
                $currentWeight += $delivery['weight_tonnes'];
            }
            if (!empty($currentRoute)) {
                $tempRoutes[] = $currentRoute;
            }
        }
        
        $finalRoutes = [];
        foreach ($tempRoutes as $route) {
            $totalWeight = array_sum(array_column($route, 'weight_tonnes'));
            $best_vehicle = getBestVehicle($totalWeight, $vehicle_list);
            
            if ($best_vehicle !== null) {
                foreach ($route as $delivery) {
                    $driver_id_sql = isset($best_vehicle['driver_id']) && $best_vehicle['driver_id'] > 0 ? $best_vehicle['driver_id'] : 'NULL';
                    mysqli_query($conn, "UPDATE deliveries SET vehicle_id = {$best_vehicle['id']}, driver_id = $driver_id_sql WHERE id = {$delivery['id']}");
                }
                
                $depot_lat = -1.3167;
                $depot_lng = 36.8500;
                $totalDistance = 0;
                $prevLat = $depot_lat;
                $prevLng = $depot_lng;
                
                foreach ($route as $delivery) {
                    $totalDistance += calculateDistance($prevLat, $prevLng, $delivery['lat'], $delivery['lng']);
                    $prevLat = $delivery['lat'];
                    $prevLng = $delivery['lng'];
                }
                $totalDistance += calculateDistance($prevLat, $prevLng, $depot_lat, $depot_lng);
                
                $fuel_cost = round($totalDistance * 50);
                $route_cost = $best_vehicle['fixed_cost'] + $fuel_cost;
                
                $finalRoutes[] = [
                    'vehicle' => $best_vehicle,
                    'driver_name' => $best_vehicle['driver_name'] ?? 'Unassigned',
                    'stops' => $route,
                    'total_distance' => round($totalDistance, 1),
                    'total_weight' => $totalWeight,
                    'fuel_cost' => $fuel_cost,
                    'total_cost' => $route_cost,
                    'num_stops' => count($route)
                ];
                
                foreach ($vehicle_list as $idx => $v) {
                    if ($v['id'] == $best_vehicle['id']) {
                        unset($vehicle_list[$idx]);
                        $vehicle_list = array_values($vehicle_list);
                        break;
                    }
                }
            }
        }
        
        $route_details = $finalRoutes;
        $_SESSION['ga_routes'] = serialize($route_details);
        $total_deliveries = array_sum(array_column($route_details, 'num_stops'));
        $message = "GA complete! " . count($route_details) . " routes covering " . $total_deliveries . " deliveries.";
    } else {
        $message = "No pending deliveries or no available vehicles.";
    }
}

$pending_count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM deliveries WHERE delivery_date = CURDATE() AND status = 'pending'"));
$assigned_count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM deliveries WHERE delivery_date = CURDATE() AND status = 'assigned'"));

// Load route details from session if exists
if (empty($route_details) && isset($_SESSION['ga_routes'])) {
    $route_details = unserialize($_SESSION['ga_routes']);
}

include 'header.php';
?>

<!-- Stats -->
<div class="stats">
    <div class="stat-card"><h3><?php echo $pending_count; ?></h3><p>Pending</p></div>
    <div class="stat-card"><h3><?php echo $assigned_count; ?></h3><p>Assigned</p></div>
</div>

<!-- Pending Deliveries Table -->
<div class="glass-card">
    <h3>📋 Pending Deliveries</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Delivery Code</th>
                    <th>Customer</th>
                    <th>Weight (t)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $pending = mysqli_query($conn, "SELECT d.id, d.delivery_code, d.weight_tonnes, c.name as customer_name FROM deliveries d LEFT JOIN customers c ON d.customer_id = c.id WHERE d.delivery_date = CURDATE() AND d.status = 'pending' ORDER BY d.id");
                if(mysqli_num_rows($pending) > 0): 
                    while($row = mysqli_fetch_assoc($pending)): 
                ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['delivery_code']; ?></td>
                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                    <td><?php echo $row['weight_tonnes']; ?> t</td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="4" style="text-align:center;padding:40px;color:rgba(255,255,255,0.5);">No pending deliveries. Run GA to plan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- GA Buttons -->
<div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
    <form method="POST" style="margin:0;">
        <button type="submit" name="run_ga" class="btn" style="background: #d4af37; color: #1a202c; padding: 12px 30px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">🚀 Run GA</button>
    </form>
    <form method="POST" style="margin:0;">
        <button type="submit" name="assign_routes" class="btn" style="background: #48bb78; color: white; padding: 12px 30px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">✓ Assign Routes</button>
    </form>
</div>

<?php if($message): ?>
<div style="background: rgba(72,187,120,0.2); color: #68d391; padding: 12px; border-radius: 8px; margin-bottom: 20px;"><?php echo $message; ?></div>
<?php endif; ?>

<!-- Route Maps -->
<?php if(!empty($route_details)): ?>
<div class="glass-card">
    <h3>🗺️ Route Maps</h3>
    <?php 
    $depot = ['lat' => -1.3167, 'lng' => 36.8500]; 
    $total_routes = count($route_details); 
    foreach($route_details as $index => $route): 
    ?>
    <div class="route-card" id="card<?php echo $index; ?>" style="display:<?php echo $index==0?'block':'none';?>; background: rgba(255,255,255,0.05); border-radius: 12px; padding: 15px; margin-bottom: 15px;">
        <div style="color: #ffffff; margin-bottom: 10px;">
            <h3 style="margin:0; font-size:16px;">Route <?php echo $index+1; ?> of <?php echo $total_routes; ?> - <?php echo $route['vehicle']['plate_number']; ?> (<?php echo ucfirst($route['vehicle']['vehicle_type']); ?>)</h3>
            <p style="margin:5px 0 0; font-size:12px; opacity:0.8;">Driver: <?php echo $route['driver_name']; ?> | Weight: <?php echo $route['total_weight']; ?> t | Stops: <?php echo $route['num_stops']; ?> | Distance: <?php echo $route['total_distance']; ?> km</p>
        </div>
        <div id="map<?php echo $index; ?>" style="height:350px; width:100%; border-radius:12px; margin-bottom:10px;"></div>
        <div style="display:flex; gap:15px; font-size:12px; color:rgba(255,255,255,0.6); flex-wrap:wrap;">
            <span>📏 <?php echo $route['total_distance']; ?> km</span>
            <span>⛽ Fuel: KES <?php echo number_format($route['fuel_cost']); ?></span>
            <span>💰 Total: KES <?php echo number_format($route['total_cost']); ?></span>
        </div>
    </div>
    <?php endforeach; ?>
    <div style="text-align:center; margin-top:15px;">
        <button id="prevBtn" class="btn" style="background: #64748b; color: #fff; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; margin:0 10px;">← Previous</button>
        <span id="routeCounter" style="color: #ffffff; margin:0 15px; font-weight:bold;">Route 1 of <?php echo $total_routes; ?></span>
        <button id="nextBtn" class="btn" style="background: #d4af37; color: #1a202c; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; margin:0 10px;">Next →</button>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
<script>
var totalRoutes = <?php echo $total_routes; ?>;
var currentIndex = 0;
var maps = {};
var depot = <?php echo json_encode($depot); ?>;
var routeDetails = <?php echo json_encode($route_details); ?>;

function loadMap(index){
    var route = routeDetails[index];
    var mapId = 'map'+index;
    
    // Hide all cards
    for(var i=0; i<totalRoutes; i++){
        var card = document.getElementById('card'+i);
        if(card) card.style.display = 'none';
    }
    document.getElementById('card'+index).style.display = 'block';
    document.getElementById('routeCounter').innerHTML = 'Route '+(index+1)+' of '+totalRoutes;
    document.getElementById('prevBtn').disabled = (index === 0);
    document.getElementById('nextBtn').disabled = (index === totalRoutes-1);
    
    if(maps[mapId]){
        maps[mapId].invalidateSize();
        return;
    }
    
    var map = L.map(mapId).setView([depot.lat, depot.lng], 8);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    
    var waypoints = [L.latLng(depot.lat, depot.lng)];
    
    for(var i = 0; i < route.stops.length; i++){
        waypoints.push(L.latLng(route.stops[i].lat, route.stops[i].lng));
        L.marker([route.stops[i].lat, route.stops[i].lng])
            .bindPopup('<b>📦 ' + (i+1) + '. ' + route.stops[i].customer_name + '</b><br>Weight: ' + route.stops[i].weight_tonnes + ' t')
            .addTo(map);
    }
    
    waypoints.push(L.latLng(depot.lat, depot.lng));
    L.marker([depot.lat, depot.lng])
        .bindPopup('<b>🏭 Depot (Start/End)</b>')
        .addTo(map);
    
    L.Routing.control({
        waypoints: waypoints,
        router: L.Routing.osrmv1({serviceUrl: 'https://router.project-osrm.org/route/v1'}),
        showAlternatives: false,
        fitSelectedRoutes: true,
        show: false,
        lineOptions: {styles: [{color: '#d4af37', weight: 5}]}
    }).addTo(map);
    
    maps[mapId] = map;
}

loadMap(0);

document.getElementById('prevBtn').onclick = function(){
    if(currentIndex > 0){
        currentIndex--;
        loadMap(currentIndex);
    }
};

document.getElementById('nextBtn').onclick = function(){
    if(currentIndex < totalRoutes-1){
        currentIndex++;
        loadMap(currentIndex);
    }
};
</script>
<?php endif; ?>

<?php include 'footer.php'; ?>
