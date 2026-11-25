<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

try {
    $revStmt = $pdo->query("SELECT SUM(total_price) AS total FROM bookings");
    $totalRevenue = $revStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $countStmt = $pdo->query("SELECT COUNT(*) AS count FROM bookings");
    $totalBookings = $countStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    $listStmt = $pdo->query("
        SELECT b.id, u.fullname, p.name AS pitch_name, b.booking_date, b.time_slot, b.total_price, b.status 
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN pitches p ON b.pitch_id = p.id
        ORDER BY b.id DESC
    ");
    $bookingsList = $listStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'total_revenue' => $totalRevenue,
        'total_bookings' => $totalBookings,
        'bookings_list' => $bookingsList
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>