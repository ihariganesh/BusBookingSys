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

// Add new schedule
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_schedule'])) {
    $bus_id = (int)$_POST['bus_id'];
    $route_id = (int)$_POST['route_id'];
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $price = (float)$_POST['price'];

    // Get total seats from bus
    $stmt = $pdo->prepare("SELECT total_seats FROM buses WHERE bus_id = :bus_id");
    $stmt->execute([':bus_id' => $bus_id]);
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bus) {
        $_SESSION['error'] = "Invalid bus selected";
        header('Location: schedules.php');
        exit;
    }

    $available_seats = $bus['total_seats'];

    $stmt = $pdo->prepare("INSERT INTO schedules (bus_id, route_id, departure_time, arrival_time, available_seats, price) 
                          VALUES (:bus_id, :route_id, :departure_time, :arrival_time, :available_seats, :price)");
    $stmt->execute([
        ':bus_id' => $bus_id,
        ':route_id' => $route_id,
        ':departure_time' => $departure_time,
        ':arrival_time' => $arrival_time,
        ':available_seats' => $available_seats,
        ':price' => $price
    ]);

    $_SESSION['success'] = "Schedule added successfully";
    header('Location: schedules.php');
    exit;
}

// Update schedule
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_schedule'])) {
    $schedule_id = (int)$_POST['schedule_id'];
    $bus_id = (int)$_POST['bus_id'];
    $route_id = (int)$_POST['route_id'];
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $price = (float)$_POST['price'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE schedules SET 
                          bus_id = :bus_id,
                          route_id = :route_id,
                          departure_time = :departure_time,
                          arrival_time = :arrival_time,
                          price = :price,
                          status = :status
                          WHERE schedule_id = :schedule_id");
    $stmt->execute([
        ':bus_id' => $bus_id,
        ':route_id' => $route_id,
        ':departure_time' => $departure_time,
        ':arrival_time' => $arrival_time,
        ':price' => $price,
        ':status' => $status,
        ':schedule_id' => $schedule_id
    ]);

    $_SESSION['success'] = "Schedule updated successfully";
    header('Location: schedules.php');
    exit;
}

// Delete schedule
if (isset($_GET['delete'])) {
    $schedule_id = (int)$_GET['delete'];

    // Check if schedule has any bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE schedule_id = :schedule_id");
    $stmt->execute([':schedule_id' => $schedule_id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        $_SESSION['error'] = "Cannot delete schedule with existing bookings";
    } else {
        $stmt = $pdo->prepare("DELETE FROM schedules WHERE schedule_id = :schedule_id");
        $stmt->execute([':schedule_id' => $schedule_id]);
        $_SESSION['success'] = "Schedule deleted successfully";
    }

    header('Location: schedules.php');
    exit;
}

// Get all schedules with bus and route details
$stmt = $pdo->query("SELECT s.*, b.bus_number, b.bus_name, r.departure_city, r.arrival_city 
                     FROM schedules s
                     JOIN buses b ON s.bus_id = b.bus_id
                     JOIN routes r ON s.route_id = r.route_id
                     ORDER BY s.departure_time DESC");
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get buses and routes for dropdowns
$buses = $pdo->query("SELECT * FROM buses WHERE is_active = 1 ORDER BY bus_name")->fetchAll(PDO::FETCH_ASSOC);
$routes = $pdo->query("SELECT * FROM routes ORDER BY departure_city, arrival_city")->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Manage Schedules";
include('../includes/header.php');
?>

<div class="container">
    <h2 class="mb-4">Manage Schedules</h2>
    
    <?php include('../includes/alerts.php'); ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                <i class="fas fa-plus"></i> Add New Schedule
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Bus</th>
                            <th>Route</th>
                            <th>Departure</th>
                            <th>Arrival</th>
                            <th>Seats</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td><?php echo $schedule['schedule_id']; ?></td>
                                <td><?php echo htmlspecialchars($schedule['bus_name'] . ' (' . $schedule['bus_number'] . ')'); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($schedule['departure_city']); ?> <i class="fas fa-arrow-right"></i> 
                                    <?php echo htmlspecialchars($schedule['arrival_city']); ?>
                                </td>
                                <td><?php echo date('M d, Y h:i A', strtotime($schedule['departure_time'])); ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($schedule['arrival_time'])); ?></td>
                                <td><?php echo $schedule['available_seats']; ?></td>
                                <td>₹<?php echo $schedule['price']; ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        switch($schedule['status']) {
                                            case 'Scheduled': echo 'primary'; break;
                                            case 'Departed': echo 'warning'; break;
                                            case 'Arrived': echo 'success'; break;
                                            case 'Cancelled': echo 'danger'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>">
                                        <?php echo $schedule['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info edit-schedule" data-schedule-id="<?php echo $schedule['schedule_id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="schedules.php?delete=<?php echo $schedule['schedule_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash"></i>
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

<!-- Add Schedule Modal -->
<div class="modal fade" id="addScheduleModal" tabindex="-1" aria-labelledby="addScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addScheduleModalLabel">Add New Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="schedules.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="bus_id" class="form-label">Bus</label>
                        <select class="form-select" id="bus_id" name="bus_id" required>
                            <option value="">Select Bus</option>
                            <?php foreach ($buses as $bus): ?>
                                <option value="<?php echo $bus['bus_id']; ?>">
                                    <?php echo htmlspecialchars($bus['bus_name'] . ' (' . $bus['bus_number'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="route_id" class="form-label">Route</label>
                        <select class="form-select" id="route_id" name="route_id" required>
                            <option value="">Select Route</option>
                            <?php foreach ($routes as $route): ?>
                                <option value="<?php echo $route['route_id']; ?>">
                                    <?php echo htmlspecialchars($route['departure_city'] . ' to ' . $route['arrival_city']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="departure_time" class="form-label">Departure Time</label>
                        <input type="datetime-local" class="form-control" id="departure_time" name="departure_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="arrival_time" class="form-label">Arrival Time</label>
                        <input type="datetime-local" class="form-control" id="arrival_time" name="arrival_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Price (₹)</label>
                        <input type="number" class="form-control" id="price" name="price" required min="1" step="0.01">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_schedule" class="btn btn-primary">Add Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Schedule Modal -->
<div class="modal fade" id="editScheduleModal" tabindex="-1" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editScheduleModalLabel">Edit Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="schedules.php">
                <input type="hidden" name="schedule_id" id="edit_schedule_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_bus_id" class="form-label">Bus</label>
                        <select class="form-select" id="edit_bus_id" name="bus_id" required>
                            <option value="">Select Bus</option>
                            <?php foreach ($buses as $bus): ?>
                                <option value="<?php echo $bus['bus_id']; ?>">
                                    <?php echo htmlspecialchars($bus['bus_name'] . ' (' . $bus['bus_number'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_route_id" class="form-label">Route</label>
                        <select class="form-select" id="edit_route_id" name="route_id" required>
                            <option value="">Select Route</option>
                            <?php foreach ($routes as $route): ?>
                                <option value="<?php echo $route['route_id']; ?>">
                                    <?php echo htmlspecialchars($route['departure_city'] . ' to ' . $route['arrival_city']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_departure_time" class="form-label">Departure Time</label>
                        <input type="datetime-local" class="form-control" id="edit_departure_time" name="departure_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_arrival_time" class="form-label">Arrival Time</label>
                        <input type="datetime-local" class="form-control" id="edit_arrival_time" name="arrival_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_price" class="form-label">Price (₹)</label>
                        <input type="number" class="form-control" id="edit_price" name="price" required min="1" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="Scheduled">Scheduled</option>
                            <option value="Departed">Departed</option>
                            <option value="Arrived">Arrived</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_schedule" class="btn btn-primary">Update Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle edit schedule button click
    $('.edit-schedule').click(function() {
        var scheduleId = $(this).data('schedule-id');
        
        // Fetch schedule details via AJAX
        $.ajax({
            url: '../../functions/get_schedule_details.php',
            method: 'GET',
            data: { schedule_id: scheduleId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#edit_schedule_id').val(response.schedule_id);
                    $('#edit_bus_id').val(response.bus_id);
                    $('#edit_route_id').val(response.route_id);
                    
                    // Format datetime for the input field
                    var departureTime = new Date(response.departure_time);
                    var arrivalTime = new Date(response.arrival_time);
                    
                    $('#edit_departure_time').val(departureTime.toISOString().slice(0, 16));
                    $('#edit_arrival_time').val(arrivalTime.toISOString().slice(0, 16));
                    
                    $('#edit_price').val(response.price);
                    $('#edit_status').val(response.status);
                    
                    $('#editScheduleModal').modal('show');
                } else {
                    alert('Failed to fetch schedule details');
                }
            },
            error: function() {
                alert('Error fetching schedule details');
            }
        });
    });
});
</script>

<?php include('../includes/footer.php'); ?>