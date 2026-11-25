<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

// Kiểm tra xem admin đã đăng nhập chưa
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Phiên đăng nhập đã hết hạn!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');

    if (empty($name) || empty($location) || empty($category) || empty($price) || empty($image_url)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ tất cả các trường thông tin!']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO pitches (name, location, category, price, image_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $location, $category, $price, $image_url]);

        echo json_encode(['success' => true, 'message' => 'Thêm sân bóng thành công!']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ!']);
}