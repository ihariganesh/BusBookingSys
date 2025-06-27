<?php
$page_title = "Booking Confirmation";
include('includes/header.php');

require_once 'includes/config.php';
require_once 'includes/auth.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Check if booking_id is provided
if (!isset($_GET['booking_id'])) {
    header('Location: my_bookings.php');
    exit;
}

$booking_id = $_GET['booking_id'];

// Get booking details
$stmt = $pdo->prepare("SELECT b.*, s.*, r.*, bu.*, p.payment_date 
                      FROM bookings b
                      JOIN schedules s ON b.schedule_id = s.schedule_id
                      JOIN routes r ON s.route_id = r.route_id
                      JOIN buses bu ON s.bus_id = bu.bus_id
                      LEFT JOIN payments p ON b.booking_id = p.booking_id
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
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card text-center" id="ticket-container">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0">Booking Confirmed!</h3>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <i class="fas fa-check-circle fa-5x text-success"></i>
                    </div>
                    <h4 class="card-title">Thank you for your booking!</h4>
                    <p class="card-text">Your booking has been confirmed and your e-ticket has been sent to your email.</p>
                    
                    <div class="booking-details mt-4 text-start">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Booking Details</h5>
                                <p><strong>Booking ID:</strong> <?php echo $booking['booking_id']; ?></p>
                                <p><strong>Booking Date:</strong> <?php echo date('M d, Y h:i A', strtotime($booking['booking_date'])); ?></p>
                                <p><strong>Bus:</strong> <?php echo htmlspecialchars($booking['bus_name']); ?></p>
                                <p><strong>Type:</strong> <?php echo htmlspecialchars($booking['bus_type']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5>Journey Details</h5>
                                <p><strong>Route:</strong> <?php echo htmlspecialchars($booking['departure_city']); ?> to <?php echo htmlspecialchars($booking['arrival_city']); ?></p>
                                <p><strong>Departure:</strong> <?php echo date('M d, Y h:i A', strtotime($booking['departure_time'])); ?></p>
                                <p><strong>Seats:</strong> <?php echo htmlspecialchars($booking['seat_numbers']); ?></p>
                                <p><strong>Total Amount:</strong> ₹<?php echo $booking['total_amount']; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-4 text-start">
                        <h5><i class="fas fa-info-circle"></i> Important Instructions</h5>
                        <ul>
                            <li>Please arrive at least 30 minutes before departure time.</li>
                            <li>Carry a printed or digital copy of this ticket along with valid photo ID proof.</li>
                            <li>Boarding point: <?php echo htmlspecialchars($booking['departure_city']); ?> Bus Stand</li>
                            <li>For any queries, contact our customer support at +91 9876543210.</li>
                        </ul>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    Happy Journey! <i class="fas fa-smile"></i>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="mybookings.php" class="btn btn-primary">
                    <i class="fas fa-list"></i> View All Bookings
                </a>
                <button id="print-ticket" class="btn btn-success">
                    <i class="fas fa-print"></i> Print Ticket
                </button>
                <button id="download-ticket" class="btn btn-info">
                    <i class="fas fa-download"></i> Download Ticket
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Include html2canvas and jsPDF libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
$(document).ready(function() {
    // Print only the ticket content
    $('#print-ticket').click(function() {
        var printContents = document.getElementById('ticket-container').outerHTML;
        var originalContents = document.body.innerHTML;
        
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        window.location.reload();
    });

    // Download ticket as PDF
    $('#download-ticket').click(function() {
        const { jsPDF } = window.jspdf;
        
        html2canvas(document.getElementById('ticket-container')).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF({
                orientation: 'portrait',
                unit: 'mm'
            });
            
            const imgProps = pdf.getImageProperties(imgData);
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
            
            pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
            pdf.save('ticket_<?php echo $booking['booking_id']; ?>.pdf');
        });
    });
});
</script>

<style>
/* Print-specific styles */
@media print {
    body * {
        visibility: hidden;
    }
    #ticket-container, #ticket-container * {
        visibility: visible;
    }
    #ticket-container {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
    }
    .no-print, .no-print * {
        display: none !important;
    }
}
</style>

<?php include('includes/footer.php'); ?>