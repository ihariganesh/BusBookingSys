<?php
require_once '../includes/config.php';

if (!isset($_GET['route_id'])) {
    echo json_encode(['success' => false, 'message' => 'Route ID not provided']);
    exit;
}

$route_id = (int)$_GET['route_id'];

$stmt = $pdo->prepare("SELECT * FROM routes WHERE route_id = :route_id");
$stmt->execute([':route_id' => $route_id]);
$route = $stmt->fetch(PDO::FETCH_ASSOC);

if ($route) {
    echo json_encode(['success' => true, ...$route]);
} else {
    echo json_encode(['success' => false, 'message' => 'Route not found']);
}
?>