<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!is_logged_in()) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        http_response_code(401);
        echo 'Not logged in';
        exit;
    }
    header('Location: login.php');
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        http_response_code(403);
        echo 'Invalid CSRF token';
        exit;
    }
    $_SESSION['error'] = "Invalid security token.";
    header('Location: mybookings.php');
    exit;
}

if (!isset($_POST['booking_ids']) || !is_array($_POST['booking_ids'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        http_response_code(400);
        echo 'Invalid request';
        exit;
    }
    $_SESSION['error'] = "No bookings selected for deletion.";
    header('Location: mybookings.php');
    exit;
}

$ids = array_map('intval', $_POST['booking_ids']);
if (empty($ids)) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        http_response_code(400);
        echo 'No valid booking IDs';
        exit;
    }
    $_SESSION['error'] = "No valid bookings selected.";
    header('Location: mybookings.php');
    exit;
}

try {
    // First verify all bookings belong to the user
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bookings WHERE booking_id IN ($in) AND user_id = ?");
    $params = array_merge($ids, [$_SESSION['user_id']]);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] != count($ids)) {
        throw new Exception("Some bookings don't belong to you or don't exist.");
    }

    // Delete the bookings
    $stmt = $pdo->prepare("DELETE FROM bookings WHERE booking_id IN ($in) AND user_id = ?");
    $params = array_merge($ids, [$_SESSION['user_id']]);
    $stmt->execute($params);

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo 'success';
        exit;
    }
    
    $_SESSION['success'] = "Selected bookings deleted successfully.";
    header('Location: mybookings.php');
    exit;

} catch (Exception $e) {
    error_log("Delete booking error: " . $e->getMessage());
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        http_response_code(500);
        echo 'Error: ' . $e->getMessage();
        exit;
    }
    
    $_SESSION['error'] = "Failed to delete bookings: " . $e->getMessage();
    header('Location: mybookings.php');
    exit;
}
?>