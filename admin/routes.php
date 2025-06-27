<?php
session_start();
// Include database connection and authentication
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is admin
if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

// Add new route
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_route'])) {
    $departure_city = trim($_POST['departure_city']);
    $arrival_city = trim($_POST['arrival_city']);
    $distance = (float)$_POST['distance'];
    $estimated_duration = trim($_POST['estimated_duration']);
    $base_price = (float)$_POST['base_price'];
    
    $stmt = $pdo->prepare("INSERT INTO routes (departure_city, arrival_city, distance, estimated_duration, base_price) 
                          VALUES (:departure_city, :arrival_city, :distance, :estimated_duration, :base_price)");
    $stmt->execute([
        ':departure_city' => $departure_city,
        ':arrival_city' => $arrival_city,
        ':distance' => $distance,
        ':estimated_duration' => $estimated_duration,
        ':base_price' => $base_price
    ]);
    
    $_SESSION['success'] = "Route added successfully";
    header('Location: routes.php');
    exit;
}

// Update route
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_route'])) {
    $route_id = (int)$_POST['route_id'];
    $departure_city = trim($_POST['departure_city']);
    $arrival_city = trim($_POST['arrival_city']);
    $distance = (float)$_POST['distance'];
    $estimated_duration = trim($_POST['estimated_duration']);
    $base_price = (float)$_POST['base_price'];
    
    $stmt = $pdo->prepare("UPDATE routes SET 
                          departure_city = :departure_city,
                          arrival_city = :arrival_city,
                          distance = :distance,
                          estimated_duration = :estimated_duration,
                          base_price = :base_price
                          WHERE route_id = :route_id");
    $stmt->execute([
        ':departure_city' => $departure_city,
        ':arrival_city' => $arrival_city,
        ':distance' => $distance,
        ':estimated_duration' => $estimated_duration,
        ':base_price' => $base_price,
        ':route_id' => $route_id
    ]);
    
    $_SESSION['success'] = "Route updated successfully";
    header('Location: routes.php');
    exit;
}

// Delete route
if (isset($_GET['delete'])) {
    $route_id = (int)$_GET['delete'];
    
    // Check if route has any schedules
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM schedules WHERE route_id = :route_id");
    $stmt->execute([':route_id' => $route_id]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        $_SESSION['error'] = "Cannot delete route with existing schedules";
    } else {
        $stmt = $pdo->prepare("DELETE FROM routes WHERE route_id = :route_id");
        $stmt->execute([':route_id' => $route_id]);
        $_SESSION['success'] = "Route deleted successfully";
    }
    
    header('Location: routes.php');
    exit;
}

// Get all routes
$stmt = $pdo->query("SELECT * FROM routes ORDER BY departure_city, arrival_city");
$routes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Manage Routes";
include('../includes/header.php');
?>

<div class="container">
    <h2 class="mb-4">Manage Routes</h2>
    
    <?php include('../includes/alerts.php'); ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRouteModal">
                <i class="fas fa-plus"></i> Add New Route
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Distance (km)</th>
                            <th>Duration</th>
                            <th>Base Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($routes as $route): ?>
                            <tr>
                                <td><?php echo $route['route_id']; ?></td>
                                <td><?php echo htmlspecialchars($route['departure_city']); ?></td>
                                <td><?php echo htmlspecialchars($route['arrival_city']); ?></td>
                                <td><?php echo $route['distance']; ?></td>
                                <td><?php echo htmlspecialchars($route['estimated_duration']); ?></td>
                                <td>₹<?php echo $route['base_price']; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info edit-route" data-route-id="<?php echo $route['route_id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="routes.php?delete=<?php echo $route['route_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="emergency_contacts.php?route_id=<?php echo $route['route_id']; ?>" class="btn btn-sm btn-warning" title="Emergency Contacts">
                                        <i class="fas fa-phone"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Route Modal -->
<div class="modal fade" id="addRouteModal" tabindex="-1" aria-labelledby="addRouteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addRouteModalLabel">Add New Route</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="routes.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="departure_city" class="form-label">Departure City</label>
                        <input type="text" class="form-control" id="departure_city" name="departure_city" required>
                    </div>
                    <div class="mb-3">
                        <label for="arrival_city" class="form-label">Arrival City</label>
                        <input type="text" class="form-control" id="arrival_city" name="arrival_city" required>
                    </div>
                    <div class="mb-3">
                        <label for="distance" class="form-label">Distance (km)</label>
                        <input type="number" class="form-control" id="distance" name="distance" required min="1" step="0.1">
                    </div>
                    <div class="mb-3">
                        <label for="estimated_duration" class="form-label">Estimated Duration</label>
                        <input type="text" class="form-control" id="estimated_duration" name="estimated_duration" required placeholder="e.g. 6 hours">
                    </div>
                    <div class="mb-3">
                        <label for="base_price" class="form-label">Base Price (₹)</label>
                        <input type="number" class="form-control" id="base_price" name="base_price" required min="1" step="0.01">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_route" class="btn btn-primary">Add Route</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Route Modal -->
<div class="modal fade" id="editRouteModal" tabindex="-1" aria-labelledby="editRouteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRouteModalLabel">Edit Route</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="routes.php">
                <input type="hidden" name="route_id" id="edit_route_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_departure_city" class="form-label">Departure City</label>
                        <input type="text" class="form-control" id="edit_departure_city" name="departure_city" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_arrival_city" class="form-label">Arrival City</label>
                        <input type="text" class="form-control" id="edit_arrival_city" name="arrival_city" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_distance" class="form-label">Distance (km)</label>
                        <input type="number" class="form-control" id="edit_distance" name="distance" required min="1" step="0.1">
                    </div>
                    <div class="mb-3">
                        <label for="edit_estimated_duration" class="form-label">Estimated Duration</label>
                        <input type="text" class="form-control" id="edit_estimated_duration" name="estimated_duration" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_base_price" class="form-label">Base Price (₹)</label>
                        <input type="number" class="form-control" id="edit_base_price" name="base_price" required min="1" step="0.01">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_route" class="btn btn-primary">Update Route</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle edit route button click
    $('.edit-route').click(function() {
        var routeId = $(this).data('route-id');
        
        // Fetch route details via AJAX
        $.ajax({
            url: '../../functions/get_route_details.php',
            method: 'GET',
            data: { route_id: routeId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#edit_route_id').val(response.route_id);
                    $('#edit_departure_city').val(response.departure_city);
                    $('#edit_arrival_city').val(response.arrival_city);
                    $('#edit_distance').val(response.distance);
                    $('#edit_estimated_duration').val(response.estimated_duration);
                    $('#edit_base_price').val(response.base_price);
                    
                    $('#editRouteModal').modal('show');
                } else {
                    alert('Failed to fetch route details');
                }
            },
            error: function() {
                alert('Error fetching route details');
            }
        });
    });
});
</script>

<?php include('../includes/footer.php'); ?>