<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$page_title = "Home";
include('includes/header.php');

?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">

<div class="hero-section">
    <div class="hero-content text-center text-white">
        <h1 class="display-4 fw-bold">Book Your Seats</h1>
        <p class="lead">Easy, fast and secure bus ticket booking</p>
        <a href="search.php" class="btn btn-primary btn-lg mt-3">Book Now</a>
    </div>
</div>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card card-3d h-100">
                <div class="card-body text-center">
                    <i class="fas fa-bus fa-3x text-primary mb-3"></i>
                    <h3 class="card-title">Wide Network</h3>
                    <p class="card-text">Choose from hundreds of routes across the country with multiple operators.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card card-3d h-100">
                <div class="card-body text-center">
                    <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                    <h3 class="card-title">Safe Travel</h3>
                    <p class="card-text">Verified operators, sanitized buses, and emergency contacts for safe journeys.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card card-3d h-100">
                <div class="card-body text-center">
                    <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                    <h3 class="card-title">24/7 Support</h3>
                    <p class="card-text">Our customer support team is available round the clock to assist you.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-4">Popular Routes</h2>
        <div class="row">
            <?php
            // Fetch popular routes by booking count
            require_once 'includes/config.php';
            $stmt = $pdo->query("
                SELECT r.*, COUNT(b.booking_id) AS booking_count
                FROM routes r
                LEFT JOIN schedules s ON r.route_id = s.route_id
                LEFT JOIN bookings b ON s.schedule_id = b.schedule_id
                GROUP BY r.route_id
                ORDER BY booking_count DESC
                LIMIT 3
            ");
            while ($route = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '<div class="col-md-4 mb-4">';
                echo '<div class="card card-3d h-100">';
                echo '<div class="card-body">';
                echo '<h5 class="card-title">' . htmlspecialchars($route['departure_city']) . ' to ' . htmlspecialchars($route['arrival_city']) . '</h5>';
                echo '<p class="card-text">';
                echo '<i class="fas fa-road"></i> Distance: ' . $route['distance'] . ' km<br>';
                echo '<i class="fas fa-clock"></i> Duration: ' . $route['estimated_duration'] . '<br>';
                echo '<i class="fas fa-rupee-sign"></i> Starting from: ₹' . $route['base_price'];
                echo '</p>';
                echo '<a href="search.php?from=' . urlencode($route['departure_city']) . '&to=' . urlencode($route['arrival_city']) . '" class="btn btn-primary">Book Now</a>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>