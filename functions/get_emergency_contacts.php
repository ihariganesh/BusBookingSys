<?php
require_once '../includes/config.php';

$stmt = $pdo->query("SELECT ec.*, r.departure_city, r.arrival_city 
                     FROM emergency_contacts ec
                     JOIN routes r ON ec.route_id = r.route_id");
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($contacts)) {
    echo '<ul class="list-group">';
    foreach ($contacts as $contact) {
        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
        echo htmlspecialchars($contact['contact_name']) . ' (' . htmlspecialchars($contact['contact_type']) . ') - ';
        echo htmlspecialchars($contact['departure_city']) . ' to ' . htmlspecialchars($contact['arrival_city']);
        echo '<span class="badge bg-primary rounded-pill">' . htmlspecialchars($contact['contact_number']) . '</span>';
        echo '</li>';
    }
    echo '</ul>';
} else {
    echo '<p class="text-muted">No route-specific contacts available</p>';
}
?>