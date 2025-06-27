<?php
session_start();
require_once 'includes/config.php';
if (!isset($_SESSION['user_id']) || !isset($_POST['booking_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$booking_id = $_POST['booking_id'];

// Fetch booking and user wallet
$stmt = $pdo->prepare("SELECT total_amount FROM bookings WHERE booking_id = :booking_id AND user_id = :user_id");
$stmt->execute([':booking_id' => $booking_id, ':user_id' => $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE user_id = :user_id");
$stmt->execute([':user_id' => $user_id]);
$wallet_balance = $stmt->fetchColumn();

if ($booking && $wallet_balance >= $booking['total_amount']) {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - :amount WHERE user_id = :user_id");
    $stmt->execute([':amount' => $booking['total_amount'], ':user_id' => $user_id]);
    $stmt = $pdo->prepare("UPDATE bookings SET payment_status = 'Paid', status = 'Confirmed' WHERE booking_id = :booking_id");
    $stmt->execute([':booking_id' => $booking_id]);
    $stmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, amount, type, description) VALUES (:user_id, :amount, 'debit', 'Bus Ticket Payment')");
    $stmt->execute([':user_id' => $user_id, ':amount' => $booking['total_amount']]);
    $pdo->commit();
    header("Location: booking_confirmation.php?booking_id=$booking_id");
    exit;
} else {
    $pdo->rollBack();
    header("Location: payment.php?booking_id=$booking_id&error=Insufficient+wallet+balance");
    exit;
}