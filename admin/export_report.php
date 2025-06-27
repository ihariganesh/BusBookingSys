<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

$type = $_GET['type'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Set headers for Excel export with UTF-8 charset
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="report_' . $type . '_' . date('Ymd_His') . '.xls"');

// Output UTF-8 BOM for Excel compatibility
echo "\xEF\xBB\xBF";

echo "<table border='1'>";

if ($type == 'bookings') {
    echo "<tr>
        <th>Booking ID</th>
        <th>User</th>
        <th>Bus</th>
        <th>Route</th>
        <th>Departure</th>
        <th>Seats</th>
        <th>Amount (INR)</th>
        <th>Status</th>
        <th>Payment</th>
    </tr>";
    $stmt = $pdo->prepare("SELECT b.booking_id, u.username, u.email, 
        bu.bus_name, bu.bus_number, 
        r.departure_city, r.arrival_city,
        s.departure_time,
        b.seat_numbers,
        b.total_amount,
        b.status,
        b.payment_status
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN schedules s ON b.schedule_id = s.schedule_id
        JOIN buses bu ON s.bus_id = bu.bus_id
        JOIN routes r ON s.route_id = r.route_id
        WHERE DATE(b.booking_date) BETWEEN :start AND :end
        ORDER BY b.booking_id DESC");
    $stmt->execute([':start' => $start_date, ':end' => $end_date]);
    foreach ($stmt as $row) {
        // Payment status logic
        $status = strtolower(trim($row['payment_status']));
        if ($status == 'paid') {
            $payment = 'Paid';
        } elseif ($status == 'refunded') {
            $payment = 'Refunded';
        } elseif ($status == 'failed') {
            $payment = 'Failed';
        } elseif ($status == '' || $status == 'pending') {
            $payment = 'Unpaid';
        } else {
            $payment = $row['payment_status'];
        }

        // Amount formatting (remove ₹ and use plain number)
        $amount = number_format($row['total_amount'], 2, '.', '');

        echo "<tr>
            <td>{$row['booking_id']}</td>
            <td>{$row['username']}<br><small>{$row['email']}</small></td>
            <td>{$row['bus_name']} ({$row['bus_number']})</td>
            <td>{$row['departure_city']} → {$row['arrival_city']}</td>
            <td>" . date('M d, Y h:i A', strtotime($row['departure_time'])) . "</td>
            <td>{$row['seat_numbers']}</td>
            <td>{$amount}</td>
            <td>{$row['status']}</td>
            <td>{$payment}</td>
        </tr>";
    }
} elseif ($type == 'routes') {
    echo "<tr>
        <th>Route ID</th>
        <th>Departure City</th>
        <th>Arrival City</th>
        <th>Total Bookings</th>
        <th>Total Revenue (INR)</th>
    </tr>";
    $stmt = $pdo->prepare("SELECT 
            r.route_id, 
            r.departure_city, 
            r.arrival_city, 
            COUNT(b.booking_id) AS total_bookings,
            SUM(b.total_amount) AS total_revenue
        FROM routes r
        JOIN schedules s ON r.route_id = s.route_id
        JOIN bookings b ON s.schedule_id = b.schedule_id
        WHERE DATE(b.booking_date) BETWEEN :start AND :end
        GROUP BY r.route_id, r.departure_city, r.arrival_city
        ORDER BY total_bookings DESC");
    $stmt->execute([':start' => $start_date, ':end' => $end_date]);
    foreach ($stmt as $row) {
        $revenue = number_format($row['total_revenue'], 2, '.', '');
        echo "<tr>
            <td>{$row['route_id']}</td>
            <td>{$row['departure_city']}</td>
            <td>{$row['arrival_city']}</td>
            <td>{$row['total_bookings']}</td>
            <td>{$revenue}</td>
        </tr>";
    }
} elseif ($type == 'buses') {
    echo "<tr>
        <th>Bus Name</th>
        <th>Bus Number</th>
        <th>Total Trips</th>
        <th>Completed Trips</th>
        <th>Total Bookings</th>
        <th>Average Occupancy</th>
    </tr>";
    $stmt = $pdo->prepare("SELECT 
            b.bus_name, 
            b.bus_number, 
            COUNT(s.schedule_id) AS total_trips,
            SUM(CASE WHEN s.status = 'Arrived' THEN 1 ELSE 0 END) AS completed_trips,
            SUM(b2.seat_count) AS total_bookings,
            AVG(b2.seat_count) AS avg_occupancy
        FROM buses b
        JOIN schedules s ON b.bus_id = s.bus_id
        LEFT JOIN (
            SELECT schedule_id, COUNT(*) AS seat_count 
            FROM bookings 
            GROUP BY schedule_id
        ) b2 ON s.schedule_id = b2.schedule_id
        WHERE DATE(s.departure_time) BETWEEN :start AND :end
        GROUP BY b.bus_id
        ORDER BY total_trips DESC");
    $stmt->execute([':start' => $start_date, ':end' => $end_date]);
    foreach ($stmt as $row) {
        $avg_occupancy = is_null($row['avg_occupancy']) ? 0 : number_format($row['avg_occupancy'], 2, '.', '');
        echo "<tr>
            <td>{$row['bus_name']}</td>
            <td>{$row['bus_number']}</td>
            <td>{$row['total_trips']}</td>
            <td>{$row['completed_trips']}</td>
            <td>{$row['total_bookings']}</td>
            <td>{$avg_occupancy}</td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='9'>Report type not implemented.</td></tr>";
}

echo "</table>";
exit;