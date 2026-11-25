<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

try {
    $stmt = $pdo->query("SELECT * FROM pitches");
    $pitches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($pitches);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>