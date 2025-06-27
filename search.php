<?php
$page_title = "Search Buses";
include('includes/header.php');

require_once 'includes/config.php';

// Default values
$departure_city = isset($_GET['from']) ? $_GET['from'] : '';
$arrival_city = isset($_GET['to']) ? $_GET['to'] : '';
$travel_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$buses = [];

// Process search if form submitted
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!empty($departure_city) || !empty($arrival_city)) {
        // Filtered search
        $sql = "SELECT s.*, b.*, r.* 
                FROM schedules s
                JOIN buses b ON s.bus_id = b.bus_id
                JOIN routes r ON s.route_id = r.route_id
                WHERE r.departure_city LIKE :departure 
                AND r.arrival_city LIKE :arrival
                AND DATE(s.departure_time) = :travel_date
                AND s.available_seats > 0
                ORDER BY s.departure_time";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':departure' => '%' . $departure_city . '%',
            ':arrival' => '%' . $arrival_city . '%',
            ':travel_date' => $travel_date
        ]);
        $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Show all current & future available buses if search is empty
        $sql = "SELECT s.*, b.*, r.*
                FROM schedules s
                JOIN buses b ON s.bus_id = b.bus_id
                JOIN routes r ON s.route_id = r.route_id
                WHERE s.departure_time >= NOW()
                AND s.available_seats > 0
                ORDER BY s.departure_time";
        $stmt = $pdo->query($sql);
        $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<div class="container">
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Search Buses</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="search.php">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="from" class="form-label">From</label>
                        <input type="text" class="form-control" id="from" name="from" value="<?php echo htmlspecialchars($departure_city); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="to" class="form-label">To</label>
                        <input type="text" class="form-control" id="to" name="to" value="<?php echo htmlspecialchars($arrival_city); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="date" class="form-label">Travel Date</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars($travel_date); ?>" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Search Buses</button>
            </form>
        </div>
    </div>

    <?php if (!empty($buses)): ?>
        <h3 class="mb-4">
            <?php
            if (empty($departure_city) && empty($arrival_city)) {
                echo "All Available Buses (Current & Upcoming)";
            } else {
                echo "Available Buses";
            }
            ?>
        </h3>
        <?php foreach ($buses as $bus): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><?php echo htmlspecialchars($bus['bus_name']); ?></h4>
                        <small class="text-muted">
                            <?php echo htmlspecialchars($bus['departure_city']); ?> 
                            <i class="fas fa-arrow-right mx-2"></i> 
                            <?php echo htmlspecialchars($bus['arrival_city']); ?>
                            | <?php echo date('d M Y', strtotime($bus['departure_time'])); ?>
                        </small>
                    </div>
                    <span class="badge bg-<?php echo $bus['bus_type'] == 'Luxury' ? 'warning' : ($bus['bus_type'] == 'Sleeper' ? 'info' : 'primary'); ?>">
                        <?php echo htmlspecialchars($bus['bus_type']); ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h5>Timings</h5>
                            <p>
                                <i class="fas fa-clock"></i> Departure: <?php echo date('h:i A', strtotime($bus['departure_time'])); ?><br>
                                <i class="fas fa-clock"></i> Arrival: <?php echo date('h:i A', strtotime($bus['arrival_time'])); ?>
                            </p>
                            <p>
                                <i class="fas fa-road"></i> Duration: <?php 
                                    $departure = new DateTime($bus['departure_time']);
                                    $arrival = new DateTime($bus['arrival_time']);
                                    $interval = $departure->diff($arrival);
                                    echo $interval->format('%h hours %i minutes');
                                ?>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <h5>Amenities</h5>
                            <ul class="list-unstyled">
                                <?php 
                                $amenities = explode(',', $bus['amenities']);
                                foreach ($amenities as $amenity) {
                                    echo '<li><i class="fas fa-check-circle text-success"></i> ' . trim($amenity) . '</li>';
                                }
                                ?>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h5>Fare & Seats</h5>
                            <p>
                                <i class="fas fa-rupee-sign"></i> Price: ₹<?php echo $bus['price']; ?><br>
                                <i class="fas fa-chair"></i> Available Seats: <?php echo $bus['available_seats']; ?>
                            </p>
                            <a href="booking.php?schedule_id=<?php echo $bus['schedule_id']; ?>" class="btn btn-primary">Book Now</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && (!empty($departure_city) || !empty($arrival_city))): ?>
        <div class="alert alert-warning">No buses found for your search criteria. Please try different cities or date.</div>
    <?php elseif ($_SERVER['REQUEST_METHOD'] == 'GET'): ?>
        <div class="alert alert-info">No buses available at the moment.</div>
    <?php endif; ?>
</div>

<?php include('includes/footer.php'); ?>