<?php
session_start();
$page_title = "My Bookings";
require_once 'includes/config.php';
require_once 'includes/auth.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!is_logged_in()) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

include('includes/header.php');

$bookings = [];
$error_message = '';

try {
    if (!$pdo) {
        throw new PDOException("Database connection failed");
    }

    $stmt = $pdo->prepare("SELECT 
        b.booking_id, 
        b.booking_date, 
        b.seat_numbers, 
        b.total_amount, 
        b.status AS booking_status,
        b.payment_status,
        s.schedule_id, 
        s.departure_time, 
        s.arrival_time, 
        s.price,
        r.route_id, 
        r.departure_city, 
        r.arrival_city,
        bu.bus_id, 
        bu.bus_name, 
        bu.bus_type,
        p.payment_date
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.schedule_id
    JOIN routes r ON s.route_id = r.route_id
    JOIN buses bu ON s.bus_id = bu.bus_id
    LEFT JOIN payments p ON b.booking_id = p.booking_id
    WHERE b.user_id = :user_id
    ORDER BY b.booking_date DESC");
    
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Unable to fetch bookings at this time. Please try again later.";
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        $error_message .= "<br><small>Error: " . htmlspecialchars($e->getMessage()) . "</small>";
    }
}
?>

<div class="container mt-4">
    <h2 class="mb-4">My Bookings</h2>
    
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
            <div class="mt-2">
                <a href="search.php" class="btn btn-primary">Search Buses</a>
                <a href="contact.php" class="btn btn-secondary">Contact Support</a>
            </div>
        </div>
    <?php elseif (empty($bookings)): ?>
        <div class="alert alert-info">
            You haven't made any bookings yet. <a href="search.php" class="alert-link">Search buses</a> to book your tickets.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Booking ID</th>
                        <th>Bus</th>
                        <th>Route</th>
                        <th>Departure</th>
                        <th>Seats</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['booking_id']); ?></td>
                            <td><?php echo htmlspecialchars($booking['bus_name']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($booking['departure_city']); ?> 
                                <i class="fas fa-arrow-right"></i> 
                                <?php echo htmlspecialchars($booking['arrival_city']); ?>
                            </td>
                            <td><?php echo date('M d, Y h:i A', strtotime($booking['departure_time'])); ?></td>
                            <td><?php echo htmlspecialchars($booking['seat_numbers']); ?></td>
                            <td>₹<?php echo number_format($booking['total_amount'], 2); ?></td>
                            <td>
                                <?php 
                                $badge_class = 'bg-secondary';
                                switch ($booking['booking_status']) {
                                    case 'Confirmed':
                                        $badge_class = 'bg-success';
                                        break;
                                    case 'Cancelled':
                                        $badge_class = 'bg-danger';
                                        break;
                                    case 'Completed':
                                        $badge_class = 'bg-info';
                                        break;
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars($booking['booking_status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                // Improved: Show "Unpaid" for cancelled bookings that were never paid
                                $payment_badge = 'bg-warning';
                                $payment_label = htmlspecialchars($booking['payment_status']);
                                switch (strtolower($booking['payment_status'])) {
                                    case 'paid':
                                        $payment_badge = 'bg-success';
                                        $payment_label = 'Paid';
                                        break;
                                    case 'failed':
                                        $payment_badge = 'bg-danger';
                                        $payment_label = 'Failed';
                                        break;
                                    case 'refunded':
                                        $payment_badge = 'bg-info';
                                        $payment_label = 'Refunded';
                                        break;
                                    case 'cancelled':
                                    case 'not paid':
                                        if (strtolower($booking['booking_status']) === 'cancelled') {
                                            $payment_badge = 'bg-secondary';
                                            $payment_label = 'Unpaid';
                                        } else {
                                            $payment_badge = 'bg-warning';
                                            $payment_label = 'Pending';
                                        }
                                        break;
                                    case 'pending':
                                    default:
                                        if (strtolower($booking['booking_status']) === 'cancelled') {
                                            $payment_badge = 'bg-secondary';
                                            $payment_label = 'Unpaid';
                                        } else {
                                            $payment_badge = 'bg-warning';
                                            $payment_label = 'Pending';
                                        }
                                        break;
                                }
                                ?>
                                <span class="badge <?php echo $payment_badge; ?>">
                                    <?php echo $payment_label; ?>
                                </span>
                            </td>
                            <td>
                                <a href="booking_confirmation.php?booking_id=<?php echo urlencode($booking['booking_id']); ?>" 
                                   class="btn btn-sm btn-primary" 
                                   title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($booking['booking_status'] !== 'Cancelled' && strtotime($booking['departure_time']) > time()): ?>
                                <button class="btn btn-sm btn-danger cancel-booking" 
                                        data-booking-id="<?php echo htmlspecialchars($booking['booking_id']); ?>" 
                                        title="Cancel Booking">
                                    <i class="fas fa-times"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Cancel Booking Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="cancelModalLabel">Cancel Booking</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this booking?</p>
                <p id="cancellationPolicy" class="fw-bold"></p>
                <p class="text-muted">Cancellation policy applies based on departure time.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" id="confirmCancel">Confirm Cancellation</button>
            </div>
        </div>
    </div>
</div>
<!-- Add these before your custom script -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    var bookingIdToCancel = null;
    
    $('.cancel-booking').click(function() {
        bookingIdToCancel = $(this).data('booking-id');
        var row = $(this).closest('tr');
        var departureTime = row.find('td:eq(3)').text();
        var ticketAmount = row.find('td:eq(5)').text().replace('₹', '').replace(',', '');

        var departureDate = new Date(departureTime);
        var now = new Date();
        var hoursDiff = (departureDate - now) / (1000 * 60 * 60);

        var policyText = '';
        var refundAmount = 0;
        ticketAmount = parseFloat(ticketAmount);

        if (hoursDiff > 24) {
            refundAmount = ticketAmount * 0.5;
            policyText = 'Refund (50%): ₹' + refundAmount.toFixed(2) + '<br><span class="text-muted">Cancelled after 24 hours of booking.</span>';
        } else if (hoursDiff > 6) {
            refundAmount = ticketAmount * 0.8;
            policyText = 'Refund (80%): ₹' + refundAmount.toFixed(2) + '<br><span class="text-muted">Cancelled within 24 hours of departure. 20% service charge applies.</span>';
        } else if (hoursDiff > 0) {
            refundAmount = ticketAmount;
            policyText = 'Full refund: ₹' + refundAmount.toFixed(2) + '<br><span class="text-muted">Cancelled within 6 hours of departure.</span>';
        } else {
            policyText = 'No refund - departure time has passed';
        }

        $('#cancellationPolicy').html(policyText);
        $('#cancelModal').modal('show');
    });
    
    $('#confirmCancel').click(function() {
        if (bookingIdToCancel) {
            window.location.href = 'cancelbooking.php?booking_id=' + encodeURIComponent(bookingIdToCancel);
        }
    });
});
</script>

<?php include('includes/footer.php'); ?>