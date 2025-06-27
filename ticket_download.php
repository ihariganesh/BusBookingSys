<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Include mPDF (for v6.x)
require_once __DIR__ . '/vendor/autoload.php';

if (!is_logged_in() || !isset($_GET['booking_id'])) {
    header('Location: login.php');
    exit;
}

$booking_id = $_GET['booking_id'];

// Fetch booking details
$stmt = $pdo->prepare("SELECT b.*, s.*, r.*, bu.* FROM bookings b
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
    echo "Invalid booking.";
    exit;
}

// Prepare HTML for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bus Ticket #' . htmlspecialchars($booking_id) . '</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .ticket { border: 1px solid #333; padding: 20px; width: 400px; margin: 0 auto; }
        h2 { text-align: center; }
        .details { margin-bottom: 10px; }
    </style>
</head>
<body>
<div class="ticket">
    <h2>Bus Ticket</h2>
    <div class="details"><strong>Booking ID:</strong> ' . htmlspecialchars($booking['booking_id']) . '</div>
    <div class="details"><strong>Booking Date:</strong> ' . date('M d, Y h:i A', strtotime($booking['booking_date'])) . '</div>
    <div class="details"><strong>Bus:</strong> ' . htmlspecialchars($booking['bus_name']) . ' (' . htmlspecialchars($booking['bus_type']) . ')</div>
    <div class="details"><strong>Route:</strong> ' . htmlspecialchars($booking['departure_city']) . ' to ' . htmlspecialchars($booking['arrival_city']) . '</div>
    <div class="details"><strong>Departure:</strong> ' . date('M d, Y h:i A', strtotime($booking['departure_time'])) . '</div>
    <div class="details"><strong>Seats:</strong> ' . htmlspecialchars($booking['seat_numbers']) . '</div>
    <div class="details"><strong>Total Amount:</strong> ₹' . number_format($booking['total_amount'], 2) . '</div>
    <div class="details"><strong>Status:</strong> ' . htmlspecialchars($booking['payment_status']) . '</div>
    <hr>
    <div>
        <strong>Instructions:</strong>
        <ul>
            <li>Please arrive at least 30 minutes before departure time.</li>
            <li>Carry a printed or digital copy of this ticket along with valid photo ID proof.</li>
            <li>Boarding point: ' . htmlspecialchars($booking['departure_city']) . ' Bus Stand</li>
            <li>For any queries, contact our customer support at +91 9876543210.</li>
        </ul>
    </div>
</div>
</body>
</html>
';

// Generate PDF (for mPDF v6.x)
$mpdf = new mPDF();
$mpdf->WriteHTML($html);
$mpdf->Output('ticket_' . $booking_id . '.pdf', 'D'); // Force download
exit;