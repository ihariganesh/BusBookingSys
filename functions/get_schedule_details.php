<?php
require_once '../includes/config.php';

if (!isset($_GET['schedule_id'])) {
    echo json_encode(['success' => false, 'message' => 'Schedule ID not provided']);
    exit;
}

$schedule_id = (int)$_GET['schedule_id'];

$stmt = $pdo->prepare("SELECT * FROM schedules WHERE schedule_id = :schedule_id");
$stmt->execute([':schedule_id' => $schedule_id]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if ($schedule) {
    echo json_encode(['success' => true, ...$schedule]);
} else {
    echo json_encode(['success' => false, 'message' => 'Schedule not found']);
}
?>