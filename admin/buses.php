<?php
session_start();

require_once '../includes/config.php';
require_once '../includes/auth.php';

// Redirect non-admin users
if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

// Add new bus
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_bus'])) {
    $bus_number = trim($_POST['bus_number']);
    $bus_name = trim($_POST['bus_name']);
    $total_seats = (int)$_POST['total_seats'];
    $bus_type = $_POST['bus_type'];
    $amenities = trim($_POST['amenities']);

    $stmt = $pdo->prepare("INSERT INTO buses (bus_number, bus_name, total_seats, bus_type, amenities) 
                          VALUES (:bus_number, :bus_name, :total_seats, :bus_type, :amenities)");
    $stmt->execute([
        ':bus_number' => $bus_number,
        ':bus_name' => $bus_name,
        ':total_seats' => $total_seats,
        ':bus_type' => $bus_type,
        ':amenities' => $amenities
    ]);

    $_SESSION['success'] = "Bus added successfully";
    header('Location: buses.php');
    exit;
}

// Delete bus
if (isset($_GET['delete'])) {
    $bus_id = (int)$_GET['delete'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM schedules WHERE bus_id = :bus_id");
    $stmt->execute([':bus_id' => $bus_id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        $_SESSION['error'] = "Cannot delete bus with existing schedules";
    } else {
        $stmt = $pdo->prepare("DELETE FROM buses WHERE bus_id = :bus_id");
        $stmt->execute([':bus_id' => $bus_id]);
        $_SESSION['success'] = "Bus deleted successfully";
    }

    header('Location: buses.php');
    exit;
}

// Get all buses
$stmt = $pdo->query("SELECT * FROM buses ORDER BY bus_name");
$buses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Now safely include layout files
$page_title = "Manage Buses";
include('../includes/header.php');
?>

<div class="container">
    <h2 class="mb-4">Manage Buses</h2>

    <?php include('../includes/alerts.php'); ?>

    <div class="card mb-4">
        <div class="card-header">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBusModal">
                <i class="fas fa-plus"></i> Add New Bus
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Bus Number</th>
                            <th>Bus Name</th>
                            <th>Type</th>
                            <th>Seats</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($buses as $bus): ?>
                            <tr>
                                <td><?= $bus['bus_id']; ?></td>
                                <td><?= htmlspecialchars($bus['bus_number']); ?></td>
                                <td><?= htmlspecialchars($bus['bus_name']); ?></td>
                                <td><?= htmlspecialchars($bus['bus_type']); ?></td>
                                <td><?= $bus['total_seats']; ?></td>
                                <td>
                                    <span class="badge bg-<?= $bus['is_active'] ? 'success' : 'danger'; ?>">
                                        <?= $bus['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info edit-bus" data-bus-id="<?= $bus['bus_id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="buses.php?delete=<?= $bus['bus_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
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

<!-- Add Bus Modal -->
<div class="modal fade" id="addBusModal" tabindex="-1" aria-labelledby="addBusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="buses.php" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addBusModalLabel">Add New Bus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="bus_number" class="form-label">Bus Number</label>
                    <input type="text" class="form-control" id="bus_number" name="bus_number" required>
                </div>
                <div class="mb-3">
                    <label for="bus_name" class="form-label">Bus Name</label>
                    <input type="text" class="form-control" id="bus_name" name="bus_name" required>
                </div>
                <div class="mb-3">
                    <label for="total_seats" class="form-label">Total Seats</label>
                    <input type="number" class="form-control" id="total_seats" name="total_seats" required min="10">
                </div>
                <div class="mb-3">
                    <label for="bus_type" class="form-label">Bus Type</label>
                    <select class="form-select" id="bus_type" name="bus_type" required>
                        <option value="Standard">Standard</option>
                        <option value="Deluxe">Deluxe</option>
                        <option value="Luxury">Luxury</option>
                        <option value="Sleeper">Sleeper</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="amenities" class="form-label">Amenities (comma separated)</label>
                    <input type="text" class="form-control" id="amenities" name="amenities" value="AC, Reclining Seats, Charging Points">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" name="add_bus" class="btn btn-primary">Add Bus</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Bus Modal -->
<div class="modal fade" id="editBusModal" tabindex="-1" aria-labelledby="editBusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="buses.php" class="modal-content">
            <input type="hidden" name="bus_id" id="edit_bus_id">
            <div class="modal-header">
                <h5 class="modal-title">Edit Bus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="edit_bus_number" class="form-label">Bus Number</label>
                    <input type="text" class="form-control" id="edit_bus_number" name="bus_number" required>
                </div>
                <div class="mb-3">
                    <label for="edit_bus_name" class="form-label">Bus Name</label>
                    <input type="text" class="form-control" id="edit_bus_name" name="bus_name" required>
                </div>
                <div class="mb-3">
                    <label for="edit_total_seats" class="form-label">Total Seats</label>
                    <input type="number" class="form-control" id="edit_total_seats" name="total_seats" required min="10">
                </div>
                <div class="mb-3">
                    <label for="edit_bus_type" class="form-label">Bus Type</label>
                    <select class="form-select" id="edit_bus_type" name="bus_type" required>
                        <option value="Standard">Standard</option>
                        <option value="Deluxe">Deluxe</option>
                        <option value="Luxury">Luxury</option>
                        <option value="Sleeper">Sleeper</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="edit_amenities" class="form-label">Amenities</label>
                    <input type="text" class="form-control" id="edit_amenities" name="amenities">
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="edit_is_active" name="is_active">
                    <label for="edit_is_active" class="form-check-label">Active</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" name="update_bus" class="btn btn-primary">Update Bus</button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.edit-bus').click(function() {
        var busId = $(this).data('bus-id');
        $.ajax({
            url: '../../functions/get_bus_details.php',
            method: 'GET',
            data: { bus_id: busId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#edit_bus_id').val(response.bus_id);
                    $('#edit_bus_number').val(response.bus_number);
                    $('#edit_bus_name').val(response.bus_name);
                    $('#edit_total_seats').val(response.total_seats);
                    $('#edit_bus_type').val(response.bus_type);
                    $('#edit_amenities').val(response.amenities);
                    $('#edit_is_active').prop('checked', response.is_active == 1);
                    $('#editBusModal').modal('show');
                } else {
                    alert('Failed to fetch bus details');
                }
            },
            error: function() {
                alert('Error fetching bus details');
            }
        });
    });
});
</script>

<?php include('../includes/footer.php'); ?>
