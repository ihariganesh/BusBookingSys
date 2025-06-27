<?php
session_start();
require_once __DIR__ . '/razorpay-php-2.9.1/Razorpay.php';
require_once 'includes/config.php';
require_once 'includes/auth.php';

use Razorpay\Api\Api;

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['booking_id'])) {
    header('Location: my_bookings.php');
    exit;
}

$booking_id = $_GET['booking_id'];

// Fetch booking details
$stmt = $pdo->prepare("SELECT b.*, s.*, r.*, bu.*
                      FROM bookings b
                      JOIN schedules s ON b.schedule_id = s.schedule_id
                      JOIN routes r ON s.route_id = r.route_id
                      JOIN buses bu ON s.bus_id = bu.bus_id
                      WHERE b.booking_id = :booking_id AND b.user_id = :user_id");
$stmt->execute([
    ':booking_id' => $booking_id,
    ':user_id' => $_SESSION['user_id']
]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header('Location: my_bookings.php');
    exit;
}

// Fetch wallet balance
$stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE user_id = :user_id");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$wallet_balance = $stmt->fetchColumn();

// Razorpay credentials
$keyId = 'rzp_test_8D84TQdTgvZcoq';
$keySecret = 'ifKuK9iDVmbRaG0CAaRpchUb';
$api = new Api($keyId, $keySecret);

// Create Razorpay Order
$orderData = [
    'receipt'         => 'rcptid_' . $booking_id,
    'amount'          => $booking['total_amount'] * 100, // in paise
    'currency'        => 'INR',
    'payment_capture' => 1 // auto capture
];
$razorpayOrder = $api->order->create($orderData);
$razorpayOrderId = $razorpayOrder['id'];

$page_title = "Payment";
include('includes/header.php');
?>

<div class="container">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Payment</h3>
                </div>
                <div class="card-body">
                    <div class="booking-summary mb-4">
                        <h4>Booking Summary</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Bus:</strong> <?php echo htmlspecialchars($booking['bus_name']); ?></p>
                                <p><strong>Type:</strong> <?php echo htmlspecialchars($booking['bus_type']); ?></p>
                                <p><strong>Route:</strong> <?php echo htmlspecialchars($booking['departure_city']); ?> to <?php echo htmlspecialchars($booking['arrival_city']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Departure:</strong> <?php echo date('M d, Y h:i A', strtotime($booking['departure_time'])); ?></p>
                                <p><strong>Seats:</strong> <?php echo htmlspecialchars($booking['seat_numbers']); ?></p>
                                <p><strong>Total Amount:</strong> ₹<?php echo $booking['total_amount']; ?></p>
                            </div>
                        </div>
                    </div>
                    <!-- Wallet Payment Option -->
                    <?php if ($wallet_balance >= $booking['total_amount']): ?>
                        <form method="post" action="wallet_pay.php" style="display:inline;">
                            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                            <button type="submit" class="btn btn-success btn-lg mb-2">Pay with Wallet (₹<?php echo $booking['total_amount']; ?>)</button>
                        </form>
                        <span class="mx-2">or</span>
                    <?php endif; ?>
                    <!-- Razorpay Payment Button -->
                    <button id="rzp-button1" class="btn btn-primary btn-lg mb-2">Pay Now with Razorpay (₹<?php echo $booking['total_amount']; ?>)</button>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <!-- ...existing right column code if any... -->
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
var options = {
    "key": "<?php echo $keyId; ?>",
    "amount": "<?php echo $booking['total_amount'] * 100; ?>",
    "currency": "INR",
    "name": "Bus Booking System",
    "description": "Bus Ticket Payment",
    "order_id": "<?php echo $razorpayOrderId; ?>",
    "handler": function (response){
        // Send payment_id, order_id, signature, and booking_id to server for verification and booking confirmation
        $.ajax({
            url: 'verify_payment.php',
            type: 'POST',
            data: {
                razorpay_payment_id: response.razorpay_payment_id,
                razorpay_order_id: response.razorpay_order_id,
                razorpay_signature: response.razorpay_signature,
                booking_id: "<?php echo $booking_id; ?>"
            },
            success: function(res) {
                if(res.trim() === "success") {
                    window.location.href = "booking_confirmation.php?booking_id=<?php echo $booking_id; ?>";
                } else {
                    alert("Payment verification failed: " + res);
                }
            }
        });
    },
    "prefill": {
        "name": "<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>",
        "email": "<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>"
    },
    "theme": {
        "color": "#3399cc"
    }
};
var rzp1 = new Razorpay(options);
document.getElementById('rzp-button1').onclick = function(e){
    rzp1.open();
    e.preventDefault();
}
</script>

<?php include('includes/footer.php'); ?>