<?php
session_start();
// Include database connection and authentication
$page_title = "Admin Dashboard";
include('../includes/header.php');

require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is admin
if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

// Get stats for dashboard
$bookings_count = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$buses_count = $pdo->query("SELECT COUNT(*) FROM buses")->fetchColumn();

// Calculate total revenue using only 'Paid' bookings (same as reports.php)
$stmt = $pdo->query("SELECT SUM(total_amount) FROM bookings WHERE payment_status = 'Paid' AND status != 'Cancelled'");
$revenue = $stmt->fetchColumn();
if (!$revenue) $revenue = 0;
?>

<div class="container">
    <h2 class="mb-4">Admin Dashboard</h2>
    
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Bookings</h5>
                    <h1 class="display-4"><?php echo $bookings_count; ?></h1>
                    <a href="bookings.php" class="text-white">View all</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Users</h5>
                    <h1 class="display-4"><?php echo $users_count; ?></h1>
                    <a href="users.php" class="text-white">View all</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Buses</h5>
                    <h1 class="display-4"><?php echo $buses_count; ?></h1>
                    <a href="buses.php" class="text-white">View all</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Total Revenue</h5>
                    <h1 class="display-4">₹<?php echo number_format($revenue, 2); ?></h1>
                    <a href="reports.php" class="text-dark">View reports</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedules and Routes Management Cards -->
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h5 class="card-title">Manage Schedules</h5>
                    <h1 class="display-4"><i class="fas fa-calendar-alt"></i></h1>
                    <a href="schedules.php" class="text-white">View all</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <h5 class="card-title">Manage Routes</h5>
                    <h1 class="display-4"><i class="fas fa-road"></i></h1>
                    <a href="routes.php" class="text-white">View all</a>
                </div>
            </div>
        </div>
    </div>
    <!-- End Schedules and Routes Management Cards -->
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Recent Bookings</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("SELECT b.booking_id, u.username, b.total_amount, b.status 
                                                    FROM bookings b
                                                    JOIN users u ON b.user_id = u.user_id
                                                    ORDER BY b.booking_date DESC LIMIT 5");
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<tr>';
                                    echo '<td>' . $row['booking_id'] . '</td>';
                                    echo '<td>' . htmlspecialchars($row['username']) . '</td>';
                                    echo '<td>₹' . $row['total_amount'] . '</td>';
                                    echo '<td><span class="badge bg-' . ($row['status'] == 'Confirmed' ? 'success' : ($row['status'] == 'Cancelled' ? 'danger' : 'warning')) . '">' . $row['status'] . '</span></td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Upcoming Trips</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Bus</th>
                                    <th>Route</th>
                                    <th>Departure</th>
                                    <th>Seats</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("SELECT s.schedule_id, b.bus_name, r.departure_city, r.arrival_city, s.departure_time, s.available_seats 
                                                    FROM schedules s
                                                    JOIN buses b ON s.bus_id = b.bus_id
                                                    JOIN routes r ON s.route_id = r.route_id
                                                    WHERE s.departure_time > NOW()
                                                    ORDER BY s.departure_time ASC LIMIT 5");
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($row['bus_name']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['departure_city']) . ' to ' . htmlspecialchars($row['arrival_city']) . '</td>';
                                    echo '<td>' . date('M d, h:i A', strtotime($row['departure_time'])) . '</td>';
                                    echo '<td>' . $row['available_seats'] . '</td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>