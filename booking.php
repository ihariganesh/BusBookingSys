<?php


session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!is_logged_in()) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

// Check if schedule_id is provided
if (!isset($_GET['schedule_id'])) {
    header('Location: search.php');
    exit;
}

$schedule_id = $_GET['schedule_id'];

// Auto-cancel expired pending bookings (older than 15 minutes)
$pdo->exec("UPDATE bookings SET status = 'Cancelled' WHERE status = 'Pending' AND booking_date < (NOW() - INTERVAL 15 MINUTE)");

// Get schedule details
$stmt = $pdo->prepare("SELECT s.*, b.*, r.* 
                      FROM schedules s
                      JOIN buses b ON s.bus_id = b.bus_id
                      JOIN routes r ON s.route_id = r.route_id
                      WHERE s.schedule_id = :schedule_id");
$stmt->execute([':schedule_id' => $schedule_id]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule) {
    header('Location: search.php');
    exit;
}

// Process booking if form submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['seats'])) {
    $seat_numbers = implode(',', $_POST['seats']);
    $total_amount = count($_POST['seats']) * $schedule['price'];

    $pdo->beginTransaction();

    try {
        // Create booking with status 'Pending'
        $stmt = $pdo->prepare("INSERT INTO bookings (user_id, schedule_id, seat_numbers, total_amount, status, booking_date) 
                              VALUES (:user_id, :schedule_id, :seat_numbers, :total_amount, 'Pending', NOW())");
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':schedule_id' => $schedule_id,
            ':seat_numbers' => $seat_numbers,
            ':total_amount' => $total_amount
        ]);

        $booking_id = $pdo->lastInsertId();

        // Update available seats
        $stmt = $pdo->prepare("UPDATE schedules SET available_seats = available_seats - :seat_count 
                              WHERE schedule_id = :schedule_id");
        $stmt->execute([
            ':seat_count' => count($_POST['seats']),
            ':schedule_id' => $schedule_id
        ]);

        $pdo->commit();

        header("Location: payment.php?booking_id=$booking_id");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Booking failed: " . $e->getMessage();
    }
}

// Generate seat layout
$total_seats = $schedule['total_seats'];
$booked_seats = [];
$reserved_seats = [];
$stmt = $pdo->prepare("SELECT seat_numbers, status FROM bookings WHERE schedule_id = :schedule_id AND status IN ('Pending', 'Confirmed')");
$stmt->execute([':schedule_id' => $schedule_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $seats = explode(',', $row['seat_numbers']);
    if ($row['status'] == 'Confirmed') {
        $booked_seats = array_merge($booked_seats, $seats);
    } elseif ($row['status'] == 'Pending') {
        $reserved_seats = array_merge($reserved_seats, $seats);
    }
}
$page_title = "Book Bus";
include('includes/header.php');
?>

<div class="container">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Select Seats</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <h4><?php echo htmlspecialchars($schedule['bus_name']); ?></h4>
                    <p>
                        <strong>Route:</strong> <?php echo htmlspecialchars($schedule['departure_city']); ?> to <?php echo htmlspecialchars($schedule['arrival_city']); ?><br>
                        <strong>Departure:</strong> <?php echo date('M d, Y h:i A', strtotime($schedule['departure_time'])); ?><br>
                        <strong>Price per seat:</strong> ₹<span id="price-per-seat"><?php echo $schedule['price']; ?></span>
                    </p>
                    
                    <form method="POST" action="booking.php?schedule_id=<?php echo $schedule_id; ?>" id="booking-form">
                        <div class="seat-layout">
                            <div class="bus-driver text-center mb-3">
                                <i class="fas fa-bus fa-2x"></i>
                                <div>Driver</div>
                            </div>
                            
                            <div class="seats-container">
                                <?php
                                $rows = ceil($total_seats / 4);
                                $seat_counter = 1;

                                for ($i = 1; $i <= $rows; $i++) {
                                    echo '<div class="seat-row">';
                                    for ($j = 1; $j <= 4; $j++) {
                                        if ($seat_counter > $total_seats) break;

                                        $seat_number = $seat_counter;
                                        $is_booked = in_array($seat_number, $booked_seats);
                                        $is_reserved = in_array($seat_number, $reserved_seats);

                                        echo '<div class="seat">';
                                        if ($is_booked) {
                                            echo '<span class="booked">' . $seat_number . '</span>';
                                        } elseif ($is_reserved) {
                                            echo '<span class="reserved">' . $seat_number . '</span>';
                                        } else {
                                            echo '<input type="checkbox" name="seats[]" id="seat-' . $seat_number . '" value="' . $seat_number . '" class="seat-checkbox">';
                                            echo '<label for="seat-' . $seat_number . '">' . $seat_number . '</label>';
                                        }
                                        echo '</div>';

                                        $seat_counter++;
                                    }
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="seat-legend mt-3">
                            <div class="legend-item">
                                <span class="seat-available"></span> Available
                            </div>
                            <div class="legend-item">
                                <span class="seat-selected"></span> Selected
                            </div>
                            <div class="legend-item">
                                <span class="seat-reserved"></span> Reserved
                            </div>
                            <div class="legend-item">
                                <span class="seat-booked"></span> Booked
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-lg" id="proceed-btn" disabled>Proceed to Payment</button>
                        </div>
                    </form>
                    <?php
                    if (count($reserved_seats) + count($booked_seats) >= $total_seats) {
                        echo '<div class="alert alert-warning mt-3">All seats are currently reserved or booked. Please check back in a few minutes.</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">Booking Summary</h4>
                </div>
                <div class="card-body">
                    <h5><?php echo htmlspecialchars($schedule['bus_name']); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($schedule['bus_type']); ?></p>
                    
                    <div class="booking-details">
                        <div class="detail-item">
                            <span class="detail-label">From:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($schedule['departure_city']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">To:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($schedule['arrival_city']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Departure:</span>
                            <span class="detail-value"><?php echo date('M d, Y h:i A', strtotime($schedule['departure_time'])); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Arrival:</span>
                            <span class="detail-value"><?php echo date('M d, Y h:i A', strtotime($schedule['arrival_time'])); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Duration:</span>
                            <span class="detail-value">
                                <?php 
                                $departure = new DateTime($schedule['departure_time']);
                                $arrival = new DateTime($schedule['arrival_time']);
                                $interval = $departure->diff($arrival);
                                echo $interval->format('%h hours %i minutes');
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="price-summary">
                        <div class="price-item">
                            <span class="price-label">Price per seat:</span>
                            <span class="price-value">₹<span id="display-price"><?php echo $schedule['price']; ?></span></span>
                        </div>
                        <div class="price-item">
                            <span class="price-label">Selected seats:</span>
                            <span class="price-value"><span id="selected-seats-count">0</span></span>
                        </div>
                        <div class="price-item total">
                            <span class="price-label">Total Amount:</span>
                            <span class="price-value">₹<span id="total-amount">0.00</span></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-warning">
                    <h4 class="mb-0">Travel Advisory</h4>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check-circle text-success"></i> Wear mask at all times</li>
                        <li><i class="fas fa-check-circle text-success"></i> Carry sanitizer</li>
                        <li><i class="fas fa-check-circle text-success"></i> Maintain social distancing</li>
                        <li><i class="fas fa-check-circle text-success"></i> Arrive at least 30 mins before departure</li>
                        <li><i class="fas fa-check-circle text-success"></i> Carry valid ID proof</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize variables
    const pricePerSeat = parseFloat($('#price-per-seat').text());
    let selectedSeats = 0;
    let totalAmount = 0;
    
    // Update booking summary when seats are selected
    $('.seat-checkbox').change(function() {
        // Count selected seats
        selectedSeats = $('.seat-checkbox:checked').length;
        
        // Calculate total amount
        totalAmount = selectedSeats * pricePerSeat;
        
        // Update the display
        $('#selected-seats-count').text(selectedSeats);
        $('#total-amount').text(totalAmount.toFixed(2));
        
        // Enable/disable proceed button
        $('#proceed-btn').prop('disabled', selectedSeats === 0);
        
        // Update selected seats highlight
        if ($(this).is(':checked')) {
            $(this).next('label').addClass('selected');
        } else {
            $(this).next('label').removeClass('selected');
        }
    });
    
    // Form submission validation
    $('#booking-form').submit(function(e) {
        if (selectedSeats === 0) {
            e.preventDefault();
            alert('Please select at least one seat');
        }
    });
});
</script>

<style>
.seat label {
    display: inline-block;
    width: 40px;
    height: 40px;
    line-height: 40px;
    text-align: center;
    background-color: #28a745;
    color: white;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.seat input[type="checkbox"]:checked + label {
    background-color: #007bff;
}

.seat .booked {
    display: inline-block;
    width: 40px;
    height: 40px;
    line-height: 40px;
    text-align: center;
    background-color: #dc3545;
    color: white;
    border-radius: 5px;
    cursor: not-allowed;
}

.seat .reserved {
    display: inline-block;
    width: 40px;
    height: 40px;
    line-height: 40px;
    text-align: center;
    background-color: #ffc107;
    color: white;
    border-radius: 5px;
    cursor: not-allowed;
}

.seat label.selected {
    background-color: #007bff;
    transform: scale(1.1);
}

#proceed-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.seat-legend .seat-available {
    display: inline-block;
    width: 20px;
    height: 20px;
    background-color: #28a745;
    border-radius: 5px;
    margin-right: 5px;
    vertical-align: middle;
}
.seat-legend .seat-selected {
    display: inline-block;
    width: 20px;
    height: 20px;
    background-color: #007bff;
    border-radius: 5px;
    margin-right: 5px;
    vertical-align: middle;
}
.seat-legend .seat-reserved {
    display: inline-block;
    width: 20px;
    height: 20px;
    background-color: #ffc107;
    border-radius: 5px;
    margin-right: 5px;
    vertical-align: middle;
}
.seat-legend .seat-booked {
    display: inline-block;
    width: 20px;
    height: 20px;
    background-color: #dc3545;
    border-radius: 5px;
    margin-right: 5px;
    vertical-align: middle;
}
</style>

<?php include('includes/footer.php'); ?>