<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HYP'n BOOK - <?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">HYP'n BOOK</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>search.php">Book Tickets</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>mybookings.php">My Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#sosModal">
                            <i class="fas fa-exclamation-triangle"></i> SOS
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php
                        // Fetch wallet balance for top bar
                        $wallet_balance = 0;
                        try {
                            $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE user_id = :user_id");
                            $stmt->execute([':user_id' => $_SESSION['user_id']]);
                            $wallet_balance = $stmt->fetchColumn();
                        } catch (Exception $e) {
                            $wallet_balance = 0;
                        }
                        ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>wallet.php">
                                <i class="fas fa-wallet"></i>
                                Wallet: ₹<?php echo number_format($wallet_balance, 2); ?>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> <?php echo $_SESSION['username']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/dashboard.php">Admin Panel</a></li>
                                <?php endif; ?>
                                <?php if(isset($_SESSION['is_conductor']) && $_SESSION['is_conductor']): ?>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>conductor_dashboard.php">Dashboard</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>mybookings.php">My Bookings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>register.php">Register</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>login.php">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- SOS Modal -->
    <div class="modal fade" id="sosModal" tabindex="-1" aria-labelledby="sosModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="sosModalLabel">Emergency Contacts</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>In case of emergency:</strong> Please contact the appropriate authorities immediately.
                    </div>
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Police
                            <span class="badge bg-primary rounded-pill">100</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Ambulance
                            <span class="badge bg-primary rounded-pill">108</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Fire Service
                            <span class="badge bg-primary rounded-pill">101</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Roadside Assistance
                            <span class="badge bg-primary rounded-pill">104</span>
                        </li>
                    </ul>
                    <div class="mt-3">
                        <h6>Route-Specific Contacts</h6>
                        <div id="routeContacts">
                            <p class="text-muted">Select a route to view specific contacts</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4"></div>