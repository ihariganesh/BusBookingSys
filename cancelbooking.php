<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Check if booking_id is provided
if (!isset($_GET['booking_id'])) {
    header('Location: mybookings.php');
    exit;
}

$booking_id = $_GET['booking_id'];

// Get booking details
$stmt = $pdo->prepare("SELECT b.*, s.departure_time 
                      FROM bookings b
                      JOIN schedules s ON b.schedule_id = s.schedule_id
                      WHERE b.booking_id = :booking_id AND b.user_id = :user_id");
$stmt->execute([
    ':booking_id' => $booking_id,
    ':user_id' => $_SESSION['user_id']
]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header('Location: mybookings.php');
    exit;
}

// Check if booking can be cancelled (not already cancelled and departure time in future)
if ($booking['status'] == 'Cancelled' || strtotime($booking['departure_time']) < time()) {
    $_SESSION['error'] = "This booking cannot be cancelled.";
    header('Location: mybookings.php');
    exit;
}

// Only process refund if payment_status is 'Paid'
$isPaid = (isset($booking['payment_status']) && strtolower($booking['payment_status']) === 'paid');

// Calculate refund amount based on new cancellation policy (only if paid)
$refundAmount = 0;
if ($isPaid) {
    $departureTime = new DateTime($booking['departure_time']);
    $now = new DateTime();
    $interval = $now->diff($departureTime);
    $hoursDiff = ($interval->days * 24) + $interval->h + ($interval->i / 60);

    if ($hoursDiff > 24) {
        $refundAmount = $booking['total_amount'] * 0.5;
    } elseif ($hoursDiff > 6) {
        $refundAmount = $booking['total_amount'] * 0.8;
    } elseif ($hoursDiff > 0) {
        $refundAmount = $booking['total_amount'];
    } else {
        $refundAmount = 0;
    }
}

// Start transaction
$pdo->beginTransaction();

try {
    // Set payment_status based on whether booking was paid
    if ($isPaid) {
        $paymentStatus = 'Refunded';
    } else {
        $paymentStatus = 'Cancelled'; // or 'Not Paid'
    }

    // Update booking status and payment_status
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'Cancelled', payment_status = :payment_status WHERE booking_id = :booking_id");
    $stmt->execute([
        ':payment_status' => $paymentStatus,
        ':booking_id' => $booking_id
    ]);
    
    // Update available seats
    $seat_count = count(explode(',', $booking['seat_numbers']));
    $stmt = $pdo->prepare("UPDATE schedules SET available_seats = available_seats + :seat_count 
                          WHERE schedule_id = (SELECT schedule_id FROM bookings WHERE booking_id = :booking_id)");
    $stmt->execute([
        ':seat_count' => $seat_count,
        ':booking_id' => $booking_id
    ]);
    
    // Record refund if applicable (only if paid)
    if ($isPaid && $refundAmount > 0) {
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
            ':user_id' => $_SESSION['user_id']
        ]);

        // Log wallet transaction if table exists (optional)
        try {
            $stmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, amount, type, description) VALUES (:user_id, :amount, 'credit', 'Booking Refund')");
            $stmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':amount' => $refundAmount
            ]);
        } catch (Exception $e) {
            // Ignore if wallet_transactions table does not exist
        }
    }
    
    $pdo->commit();
    if ($isPaid && $refundAmount > 0) {
        $_SESSION['success'] = "Booking cancelled successfully. Refund amount: ₹" . number_format($refundAmount, 2);
    } elseif ($isPaid && $refundAmount == 0) {
        $_SESSION['success'] = "Booking cancelled successfully. No refund as per cancellation policy.";
    } else {
        $_SESSION['success'] = "Booking cancelled successfully. No payment was made, so no refund.";
    }
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Cancellation failed: " . $e->getMessage();
}

header('Location: mybookings.php');
exit;
?>