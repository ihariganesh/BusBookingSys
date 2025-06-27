<?php
session_start();

require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is admin
if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

// Remove past unpaid bookings
if (isset($_GET['remove_past_unpaid'])) {
    // Find bookings where departure time is in the past and payment_status is not 'Paid'
    $stmt = $pdo->prepare("SELECT b.booking_id 
                           FROM bookings b
                           JOIN schedules s ON b.schedule_id = s.schedule_id
                           WHERE s.departure_time < NOW() AND b.payment_status != 'Paid'");
    $stmt->execute();
    $toDelete = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($toDelete) {
        // Delete related payments first to avoid foreign key constraint error
        $in = str_repeat('?,', count($toDelete) - 1) . '?';
        $delPayments = $pdo->prepare("DELETE FROM payments WHERE booking_id IN ($in)");
        $delPayments->execute($toDelete);

        // Now delete bookings
        $delStmt = $pdo->prepare("DELETE FROM bookings WHERE booking_id IN ($in)");
        $delStmt->execute($toDelete);

        $_SESSION['success'] = count($toDelete) . " past unpaid bookings removed.";
    } else {
        $_SESSION['info'] = "No past unpaid bookings to remove.";
    }
    header('Location: bookings.php');
    exit;
}

// Cancel booking
if (isset($_GET['cancel'])) {
    $booking_id = (int)$_GET['cancel'];
    
    // Get booking details
    $stmt = $pdo->prepare("SELECT b.*, s.departure_time 
                          FROM bookings b
                          JOIN schedules s ON b.schedule_id = s.schedule_id
                          WHERE b.booking_id = :booking_id");
    $stmt->execute([':booking_id' => $booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        $_SESSION['error'] = "Booking not found";
        header('Location: bookings.php');
        exit;
    }
    
    // Check if booking can be cancelled (departure time in future)
    if (strtotime($booking['departure_time']) < time()) {
        $_SESSION['error'] = "Cannot cancel booking after departure time";
        header('Location: bookings.php');
        exit;
    }
    
    // Calculate refund amount based on cancellation time
    $departureTime = new DateTime($booking['departure_time']);
    $now = new DateTime();
    $interval = $now->diff($departureTime);
    $hoursDiff = $interval->h + ($interval->days * 24);
    
    $refundAmount = ($hoursDiff > 24) ? $booking['total_amount'] : $booking['total_amount'] * 0.5;
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Update booking status
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'Cancelled', payment_status = 'Refunded' WHERE booking_id = :booking_id");
        $stmt->execute([':booking_id' => $booking_id]);
        
        // Update available seats
        $seat_count = count(explode(',', $booking['seat_numbers']));
        $stmt = $pdo->prepare("UPDATE schedules SET available_seats = available_seats + :seat_count 
                              WHERE schedule_id = :schedule_id");
        $stmt->execute([
            ':seat_count' => $seat_count,
            ':schedule_id' => $booking['schedule_id']
        ]);
        
        // Record refund if applicable
        if ($refundAmount > 0) {
            // Insert refund record in payments table
            $stmt = $pdo->prepare("INSERT INTO payments (booking_id, amount, payment_method, status) 
                                  VALUES (:booking_id, :amount, 'Refund', 'Success')");
            $stmt->execute([
                ':booking_id' => $booking_id,
                ':amount' => -$refundAmount
            ]);

            // Credit refund to user's wallet
            $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + :amount WHERE user_id = :user_id");
            $stmt->execute([
                ':amount' => $refundAmount,
                ':user_id' => $booking['user_id']
            ]);

            // Log wallet transaction if table exists (optional)
            try {
                $stmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, amount, type, description) VALUES (:user_id, :amount, 'credit', 'Booking Refund')");
                $stmt->execute([
                    ':user_id' => $booking['user_id'],
                    ':amount' => $refundAmount
                ]);
            } catch (Exception $e) {
                // Ignore if wallet_transactions table does not exist
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['success'] = "Booking cancelled successfully. Refund amount: ₹" . $refundAmount . " has been credited to the user's wallet.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Cancellation failed: " . $e->getMessage();
    }
    
    header('Location: bookings.php');
    exit;
}

// Get all bookings with user, bus, and route details
$stmt = $pdo->query("SELECT b.*, u.username, u.email, u.phone, 
                     bu.bus_name, bu.bus_number, 
                     r.departure_city, r.arrival_city,
                     s.departure_time, s.arrival_time
                     FROM bookings b
                     JOIN users u ON b.user_id = u.user_id
                     JOIN schedules s ON b.schedule_id = s.schedule_id
                     JOIN buses bu ON s.bus_id = bu.bus_id
                     JOIN routes r ON s.route_id = r.route_id
                     ORDER BY b.booking_date DESC");
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
$page_title = "Manage Bookings";
include('../includes/header.php');
?>

<div class="container">
    <h2 class="mb-4">Manage Bookings</h2>
    
    <?php include('../includes/alerts.php'); ?>
    
    <div class="card mb-4">
        <div class="card-body">
            <a href="bookings.php?remove_past_unpaid=1" class="btn btn-sm btn-danger mb-3" onclick="return confirm('Remove all past unpaid bookings?')">
                Remove Past Unpaid Bookings
            </a>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>User</th>
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
                                </td>
                                <td style="white-space: nowrap;">
                                    <!-- View Details button to the left, X (cancel) button to the right -->
                                    <a href="booking_details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-info me-1" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="bookings.php?cancel=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-danger" title="Cancel Booking" onclick="return confirm('Are you sure you want to cancel this booking?')">
                                        <i class="fas fa-times"></i>
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

<?php include('../includes/footer.php'); ?>