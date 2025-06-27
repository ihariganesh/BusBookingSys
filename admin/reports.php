<?php
$page_title = "Reports";
include('../includes/header.php');

require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is admin
if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

// Default date range (current month)
$start_date = date('Y-m-01');
$end_date = date('Y-m-t');

// Process report filters if form submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $report_type = $_POST['report_type'];
}

// Get bookings report
$stmt = $pdo->prepare("SELECT b.*, u.username, u.email, 
                       bu.bus_name, bu.bus_number, 
                       r.departure_city, r.arrival_city,
                       s.departure_time, s.arrival_time,
                       p.payment_date, p.payment_method
                       FROM bookings b
                       JOIN users u ON b.user_id = u.user_id
                       JOIN schedules s ON b.schedule_id = s.schedule_id
                       JOIN buses bu ON s.bus_id = bu.bus_id
                       JOIN routes r ON s.route_id = r.route_id
                       LEFT JOIN payments p ON b.booking_id = p.booking_id AND p.status = 'Success'
                       WHERE DATE(b.booking_date) BETWEEN :start_date AND :end_date
                       ORDER BY b.booking_date DESC");
$stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total revenue
$total_revenue = 0;
foreach ($bookings as $booking) {
    if ($booking['payment_status'] == 'Paid') {
        $total_revenue += $booking['total_amount'];
    }
}

// Get popular routes
$stmt = $pdo->prepare("SELECT r.departure_city, r.arrival_city, COUNT(b.booking_id) as booking_count
                      FROM routes r
                      JOIN schedules s ON r.route_id = s.route_id
                      JOIN bookings b ON s.schedule_id = b.schedule_id
                      WHERE DATE(b.booking_date) BETWEEN :start_date AND :end_date
                      GROUP BY r.route_id
                      ORDER BY booking_count DESC
                      LIMIT 5");
$stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$popular_routes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get bus utilization
$stmt = $pdo->prepare("SELECT b.bus_name, b.bus_number, 
                       COUNT(s.schedule_id) as trip_count,
                       SUM(CASE WHEN s.status = 'Arrived' THEN 1 ELSE 0 END) as completed_trips,
                       SUM(b2.seat_count) as total_bookings,
                       AVG(b2.seat_count) as avg_occupancy
                       FROM buses b
                       JOIN schedules s ON b.bus_id = s.bus_id
                       LEFT JOIN (
                           SELECT schedule_id, COUNT(*) as seat_count 
                           FROM bookings 
                           GROUP BY schedule_id
                       ) b2 ON s.schedule_id = b2.schedule_id
                       WHERE DATE(s.departure_time) BETWEEN :start_date AND :end_date
                       GROUP BY b.bus_id
                       ORDER BY trip_count DESC");
$stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$bus_utilization = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h2 class="mb-4">Reports</h2>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Report Filters</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="reports.php">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="report_type" class="form-label">Report Type</label>
                        <select class="form-select" id="report_type" name="report_type">
                            <option value="bookings">Bookings Report</option>
                            <option value="revenue">Revenue Report</option>
                            <option value="routes">Route Analysis</option>
                            <option value="buses">Bus Utilization</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Generate Report</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Bookings Report (<?php echo date('M d, Y', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($end_date)); ?>)</h5>
            <div>
                <span class="badge bg-success">Total Bookings: <?php echo count($bookings); ?></span>
                <span class="badge bg-primary ms-2">Total Revenue: ₹<?php echo number_format($total_revenue, 2); ?></span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Booking ID</th>
                            <th>User</th>
                            <th>Bus</th>
                            <th>Route</th>
                            <th>Departure</th>
                            <th>Seats</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo $booking['booking_id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($booking['username']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($booking['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($booking['bus_name'] . ' (' . $booking['bus_number'] . ')'); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($booking['departure_city']); ?> <i class="fas fa-arrow-right"></i> 
                                    <?php echo htmlspecialchars($booking['arrival_city']); ?>
                                </td>
                                <td><?php echo date('M d, Y h:i A', strtotime($booking['departure_time'])); ?></td>
                                <td><?php echo htmlspecialchars($booking['seat_numbers']); ?></td>
                                <td>₹<?php echo $booking['total_amount']; ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        switch($booking['status']) {
                                            case 'Confirmed': echo 'success'; break;
                                            case 'Cancelled': echo 'danger'; break;
                                            case 'Completed': echo 'info'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>">
                                        <?php echo $booking['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    // Show "Unpaid" if payment_status is empty or "Pending"
                                    $status = strtolower(trim($booking['payment_status']));
                                    if ($status == 'paid') {
                                        echo '<span class="badge bg-success">Paid</span>';
                                    } elseif ($status == 'refunded') {
                                        echo '<span class="badge bg-info text-dark">Refunded</span>';
                                    } elseif ($status == 'failed') {
                                        echo '<span class="badge bg-danger">Failed</span>';
                                    } elseif ($status == '' || $status == 'pending') {
                                        echo '<span class="badge bg-secondary">Unpaid</span>';
                                    } else {
                                        echo '<span class="badge bg-secondary">'.htmlspecialchars($booking['payment_status']).'</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Popular Routes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Route</th>
                                    <th>Bookings</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($popular_routes as $route): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($route['departure_city']); ?> to <?php echo htmlspecialchars($route['arrival_city']); ?></td>
                                        <td><?php echo $route['booking_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Bus Utilization</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Bus</th>
                                    <th>Trips</th>
                                    <th>Completed</th>
                                    <th>Avg Occupancy</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bus_utilization as $bus): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($bus['bus_name']); ?></td>
                                        <td><?php echo $bus['trip_count']; ?></td>
                                        <td><?php echo $bus['completed_trips']; ?></td>
                                        <td><?php echo round($bus['avg_occupancy'], 1); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Export Reports</h5>
        </div>
        <div class="card-body">
            <div class="d-flex gap-2">
                <a href="export_report.php?type=bookings&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Export Bookings
                </a>
                <a href="export_report.php?type=routes&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-info">
                    <i class="fas fa-file-excel"></i> Export Routes
                </a>
                <a href="export_report.php?type=buses&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-warning">
                    <i class="fas fa-file-excel"></i> Export Bus Utilization
                </a>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>