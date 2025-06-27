<?php
$page_title = "Booking Details";
include('../includes/header.php');

require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is admin
if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

// Check if booking ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid booking ID";
    header('Location: bookings.php');
    exit;
}

$booking_id = (int)$_GET['id'];

// Get booking details with all related information
$stmt = $pdo->prepare("SELECT 
                        b.booking_id, b.booking_date, b.seat_numbers, b.total_amount, 
                        b.payment_status, b.status,
                        u.user_id, u.username, u.email, u.full_name, u.phone,
                        s.schedule_id, s.departure_time, s.arrival_time, s.available_seats, s.price,
                        bu.bus_id, bu.bus_number, bu.bus_name, bu.total_seats, bu.bus_type, bu.amenities,
                        r.route_id, r.departure_city, r.arrival_city, r.distance, r.estimated_duration,
                        p.payment_id, p.amount, p.payment_method, p.transaction_id, p.payment_date
                      FROM bookings b
                      JOIN users u ON b.user_id = u.user_id
                      JOIN schedules s ON b.schedule_id = s.schedule_id
                      JOIN buses bu ON s.bus_id = bu.bus_id
                      JOIN routes r ON s.route_id = r.route_id
                      LEFT JOIN payments p ON b.booking_id = p.booking_id
                      WHERE b.booking_id = :booking_id");
$stmt->execute([':booking_id' => $booking_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    $_SESSION['error'] = "Booking not found";
    header('Location: bookings.php');
    exit;
}

// Format dates
$departure_time = date('M d, Y h:i A', strtotime($booking['departure_time']));
$arrival_time = date('M d, Y h:i A', strtotime($booking['arrival_time']));
$booking_date = date('M d, Y h:i A', strtotime($booking['booking_date']));
$payment_date = !empty($booking['payment_date']) ? date('M d, Y h:i A', strtotime($booking['payment_date'])) : 'N/A';

// Calculate duration if needed
if (!empty($booking['estimated_duration'])) {
    $duration_str = $booking['estimated_duration'];
} else {
    $departure = new DateTime($booking['departure_time']);
    $arrival = new DateTime($booking['arrival_time']);
    $duration = $departure->diff($arrival);
    $duration_str = $duration->format('%h hours %i minutes');
}
?>

<div class="container">
    <h2 class="mb-4">Booking Details #<?php echo $booking['booking_id']; ?></h2>
    
    <?php include('../includes/alerts.php'); ?>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Booking Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>User Details</h6>
                    <p>
                        <strong>Name:</strong> <?php echo htmlspecialchars($booking['full_name']); ?><br>
                        <strong>Username:</strong> <?php echo htmlspecialchars($booking['username']); ?><br>
                        <strong>Email:</strong> <?php echo htmlspecialchars($booking['email']); ?><br>
                        <strong>Phone:</strong> <?php echo htmlspecialchars($booking['phone']); ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <h6>Booking Status</h6>
                    <p>
                        <strong>Status:</strong> 
                        <span class="badge bg-<?php 
                            switch($booking['status']) {
                                case 'Confirmed': echo 'success'; break;
                                case 'Cancelled': echo 'danger'; break;
                                case 'Completed': echo 'info'; break;
                                default: echo 'secondary';
                            }
                        ?>">
                            <?php echo $booking['status']; ?>
                        </span><br>
                        <strong>Booking Date:</strong> <?php echo $booking_date; ?><br>
                        <strong>Payment Status:</strong> 
                        <span class="badge bg-<?php 
                            switch($booking['payment_status']) {
                                case 'Paid': echo 'success'; break;
                                case 'Pending': echo 'warning'; break;
                                case 'Failed': echo 'danger'; break;
                                case 'Refunded': echo 'info'; break;
                                default: echo 'secondary';
                            }
                        ?>">
                            <?php echo $booking['payment_status']; ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Trip Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Route Information</h6>
                    <p>
                        <strong>From:</strong> <?php echo htmlspecialchars($booking['departure_city']); ?><br>
                        <strong>To:</strong> <?php echo htmlspecialchars($booking['arrival_city']); ?><br>
                        <strong>Distance:</strong> <?php echo $booking['distance']; ?> km<br>
                        <strong>Duration:</strong> <?php echo $duration_str; ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <h6>Schedule Information</h6>
                    <p>
                        <strong>Departure:</strong> <?php echo $departure_time; ?><br>
                        <strong>Arrival:</strong> <?php echo $arrival_time; ?><br>
                        <strong>Bus:</strong> <?php echo htmlspecialchars($booking['bus_name'] . ' (' . $booking['bus_number'] . ')'); ?><br>
                        <strong>Type:</strong> <?php echo $booking['bus_type']; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Seat & Payment Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Seat Information</h6>
                    <p>
                        <strong>Seat Numbers:</strong> <?php echo htmlspecialchars($booking['seat_numbers']); ?><br>
                        <strong>Total Seats:</strong> <?php echo count(explode(',', $booking['seat_numbers'])); ?><br>
                        <strong>Price per Seat:</strong> ₹<?php echo $booking['price']; ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <h6>Payment Information</h6>
                    <p>
                        <strong>Total Amount:</strong> ₹<?php echo $booking['total_amount']; ?><br>
                        <strong>Payment Method:</strong> <?php echo $booking['payment_method'] ?? 'N/A'; ?><br>
                        <?php if (!empty($booking['transaction_id'])): ?>
                            <strong>Transaction ID:</strong> <?php echo $booking['transaction_id']; ?><br>
                        <?php endif; ?>
                        <strong>Payment Date:</strong> <?php echo $payment_date; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <a href="bookings.php" class="btn btn-secondary">Back to Bookings</a>
    
    <?php if ($booking['status'] == 'Confirmed' && strtotime($booking['departure_time']) > time()): ?>
        <a href="bookings.php?cancel=<?php echo $booking['booking_id']; ?>" class="btn btn-danger float-end" onclick="return confirm('Are you sure you want to cancel this booking?')">
            <i class="fas fa-times"></i> Cancel Booking
        </a>
    <?php endif; ?>
</div>

<?php include('../includes/footer.php'); ?>