<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once __DIR__ . '/razorpay-php-2.9.1/Razorpay.php';

use Razorpay\Api\Api;

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch wallet balance
$stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE user_id = :user_id");
$stmt->execute([':user_id' => $user_id]);
$wallet_balance = $stmt->fetchColumn();

// --- Star Coins Section ---
// Create star_coins column if not exists (run once in your DB):
// ALTER TABLE users ADD COLUMN star_coins INT NOT NULL DEFAULT 0;

// Fetch star coins
$stmt = $pdo->prepare("SELECT star_coins FROM users WHERE user_id = :user_id");
$stmt->execute([':user_id' => $user_id]);
$star_coins = (int)$stmt->fetchColumn();

// Handle star coin conversion
if (isset($_POST['convert_star_coins']) && $star_coins > 0) {
    $rupees = $star_coins * 100;
    $pdo->beginTransaction();
    try {
        // Add rupees to wallet and reset star coins
        $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + :rupees, star_coins = 0 WHERE user_id = :user_id");
        $stmt->execute([':rupees' => $rupees, ':user_id' => $user_id]);
        // Log transaction
        $stmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, amount, type, description) VALUES (:user_id, :amount, 'credit', 'Converted $star_coins Star Coins')");
        $stmt->execute([':user_id' => $user_id, ':amount' => $rupees]);
        $pdo->commit();
        header("Location: wallet.php?converted=1");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Conversion failed: " . $e->getMessage();
    }
}

// Fetch wallet transactions
$stmt = $pdo->prepare("SELECT * FROM wallet_transactions WHERE user_id = :user_id ORDER BY created_at DESC");
$stmt->execute([':user_id' => $user_id]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle add money form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount']) && !isset($_POST['convert_star_coins'])) {
    $amount = floatval($_POST['amount']);
    if ($amount > 0) {
        $keyId = 'rzp_test_8D84TQdTgvZcoq';
        $keySecret = 'ifKuK9iDVmbRaG0CAaRpchUb';
        $api = new Api($keyId, $keySecret);

        $orderData = [
            'receipt' => 'wallet_' . $user_id . '_' . time(),
            'amount' => $amount * 100,
            'currency' => 'INR',
            'payment_capture' => 1
        ];
        $razorpayOrder = $api->order->create($orderData);
        $razorpayOrderId = $razorpayOrder['id'];
    }
}
include('includes/header.php');
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-6">
            <h2>My Wallet</h2>
            <p><strong>Balance:</strong> ₹<?php echo number_format($wallet_balance, 2); ?></p>
            <form method="post" id="add-money-form">
                <div class="mb-3">
                    <label for="amount" class="form-label">Add Money (₹):</label>
                    <input type="number" min="1" step="0.01" name="amount" id="amount" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Add Money</button>
            </form>
            <hr>
            <h4>Wallet Transactions</h4>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $txn): ?>
                        <tr>
                            <td><?php echo $txn['created_at']; ?></td>
                            <td><?php echo ucfirst($txn['type']); ?></td>
                            <td><?php echo ($txn['type'] == 'credit' ? '+' : '-') . '₹' . number_format($txn['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($txn['description']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="col-md-6">
            <h2>
                <span style="vertical-align:middle;">⭐ Star Coins</span>
                <button class="btn btn-outline-warning btn-sm ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#starCoinPanel" aria-expanded="false" aria-controls="starCoinPanel">
                    View & Convert
                </button>
            </h2>
            <div class="collapse show" id="starCoinPanel">
                <div class="card card-body">
                    <h4>
                        <span class="text-warning fw-bold"><?php echo $star_coins; ?></span> Star Coin<?php echo $star_coins == 1 ? '' : 's'; ?>
                    </h4>
                    <p>1 Star Coin = ₹100</p>
                    <p>
                        <strong>How to earn?</strong><br>
                        - Book a ticket below ₹1000: <b>1 Star Coin</b><br>
                        - ₹1000 to ₹1999: <b>2 Star Coins</b><br>
                        - ₹2000 to ₹2999: <b>3 Star Coins</b><br>
                        - And so on (every ₹1000 = +1 Star Coin)
                    </p>
                    <form method="post">
                        <input type="hidden" name="convert_star_coins" value="1">
                        <button type="submit" class="btn btn-warning" <?php if($star_coins == 0) echo 'disabled'; ?>>
                            Convert <?php echo $star_coins; ?> Star Coin<?php echo $star_coins == 1 ? '' : 's'; ?> to ₹<?php echo $star_coins * 100; ?>
                        </button>
                    </form>
                    <?php if (isset($_GET['converted'])): ?>
                        <div class="alert alert-success mt-2">Star coins converted to wallet successfully!</div>
                    <?php endif; ?>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger mt-2"><?php echo $error; ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($razorpayOrderId)): ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
var options = {
    "key": "<?php echo $keyId; ?>",
    "amount": "<?php echo $amount * 100; ?>",
    "currency": "INR",
    "name": "Bus Booking Wallet",
    "description": "Add Money to Wallet",
    "order_id": "<?php echo $razorpayOrderId; ?>",
    "handler": function (response){
        // AJAX to verify_wallet_payment.php
        fetch('verify_wallet_payment.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                razorpay_payment_id: response.razorpay_payment_id,
                razorpay_order_id: response.razorpay_order_id,
                razorpay_signature: response.razorpay_signature,
                amount: "<?php echo $amount; ?>"
            })
        }).then(res => res.text()).then(res => {
            if(res.trim() === "success") {
                window.location.href = "wallet.php";
            } else {
                alert("Wallet top-up failed: " + res);
            }
        });
    }
};
var rzp1 = new Razorpay(options);
rzp1.open();
</script>
<?php endif; ?>

<?php include('includes/footer.php'); ?>