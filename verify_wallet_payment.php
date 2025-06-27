<?php
session_start();
require_once 'includes/config.php';
require_once __DIR__ . '/razorpay-php-2.9.1/Razorpay.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

$keyId = 'rzp_test_8D84TQdTgvZcoq';
$keySecret = 'ifKuK9iDVmbRaG0CAaRpchUb';
$api = new Api($keyId, $keySecret);

$user_id = $_SESSION['user_id'];
$razorpay_payment_id = $_POST['razorpay_payment_id'] ?? '';
$razorpay_order_id = $_POST['razorpay_order_id'] ?? '';
$razorpay_signature = $_POST['razorpay_signature'] ?? '';
$amount = floatval($_POST['amount'] ?? 0);

if (!$razorpay_payment_id || !$razorpay_order_id || !$razorpay_signature || !$user_id || $amount <= 0) {
    echo "Invalid request";
    exit;
}

$attributes = [
    'razorpay_order_id' => $razorpay_order_id,
    'razorpay_payment_id' => $razorpay_payment_id,
    'razorpay_signature' => $razorpay_signature
];

try {
    $api->utility->verifyPaymentSignature($attributes);

    // Credit wallet
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + :amount WHERE user_id = :user_id");
    $stmt->execute([':amount' => $amount, ':user_id' => $user_id]);
    $stmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, amount, type, description) VALUES (:user_id, :amount, 'credit', 'Wallet Top-up via Razorpay')");
    $stmt->execute([':user_id' => $user_id, ':amount' => $amount]);
    $pdo->commit();

    echo "success";
} catch (SignatureVerificationError $e) {
    echo "Signature verification failed: " . $e->getMessage();
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}