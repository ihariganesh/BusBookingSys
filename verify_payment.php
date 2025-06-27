<?php
require_once __DIR__ . '/razorpay-php-2.9.1/Razorpay.php';
require_once 'includes/config.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

$keyId = 'rzp_test_8D84TQdTgvZcoq';
$keySecret = 'ifKuK9iDVmbRaG0CAaRpchUb';

$api = new Api($keyId, $keySecret);

// Get POST data
$razorpay_payment_id = $_POST['razorpay_payment_id'] ?? '';
$razorpay_order_id = $_POST['razorpay_order_id'] ?? '';
$razorpay_signature = $_POST['razorpay_signature'] ?? '';
$booking_id = $_POST['booking_id'] ?? '';

if (!$razorpay_payment_id || !$razorpay_order_id || !$razorpay_signature || !$booking_id) {
    echo "Invalid request";
    exit;
}

// Verify signature
$attributes = [
    'razorpay_order_id' => $razorpay_order_id,
    'razorpay_payment_id' => $razorpay_payment_id,
    'razorpay_signature' => $razorpay_signature
];

try {
    $api->utility->verifyPaymentSignature($attributes);

    // Fetch payment details
    $payment = $api->payment->fetch($razorpay_payment_id);

    // Mark booking as paid
    $stmt = $pdo->prepare("UPDATE bookings SET payment_status = 'Paid', status = 'Confirmed' WHERE booking_id = :booking_id");
    $stmt->execute([':booking_id' => $booking_id]);

    // Record payment
    $stmt = $pdo->prepare("INSERT INTO payments (booking_id, amount, payment_method, status) VALUES (:booking_id, :amount, 'Razorpay', 'Success')");
    $stmt->execute([
        ':booking_id' => $booking_id,
        ':amount' => $payment->amount / 100
    ]);

    echo "success";
} catch (SignatureVerificationError $e) {
    echo "Signature verification failed: " . $e->getMessage();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}