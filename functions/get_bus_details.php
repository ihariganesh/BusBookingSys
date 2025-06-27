<?php
require_once '../includes/config.php';

if (!isset($_GET['bus_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bus ID not provided']);
    exit;
}

$bus_id = (int)$_GET['bus_id'];

$stmt = $pdo->prepare("SELECT * FROM buses WHERE bus_id = :bus_id");
$stmt->execute([':bus_id' => $bus_id]);
$bus = $stmt->fetch(PDO::FETCH_ASSOC);

if ($bus) {
    echo json_encode(['success' => true, ...$bus]);
} else {
    echo json_encode(['success' => false, 'message' => 'Bus not found']);
}
?>