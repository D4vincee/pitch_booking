<?php
session_start();
require_once 'db.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// Xử lý đăng xuất
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['admin_logged_in']);
    session_destroy();
    header('Location: admin_login.php');
    exit;
}

// 1. TÍNH TỔNG DOANH THU (CHỈ TÍNH CÁC ĐƠN ĐÃ XÁC NHẬN / ĐÃ DUYỆT)
try {
    $stmt_rev = $pdo->query("SELECT SUM(total_price) as total FROM bookings WHERE status = 'confirmed' OR status = 'Đã xác nhận'");
    $row_rev = $stmt_rev->fetch(PDO::FETCH_ASSOC);
    $total_revenue = $row_rev['total'] ?? 0;
} catch (PDOException $e) {
    $total_revenue = 0;
}

// 2. TỔNG LƯỢT ĐẶT SÂN
try {
    $stmt_bookings = $pdo->query("SELECT COUNT(*) as total FROM bookings");
    $row_bookings = $stmt_bookings->fetch(PDO::FETCH_ASSOC);
    $total_bookings = $row_bookings['total'] ?? 0;
} catch (PDOException $e) {
    $total_bookings = 0;
}

// 3. SÂN ĐANG HOẠT ĐỘNG
try {
    $stmt_pitches = $pdo->query("SELECT COUNT(*) as total FROM pitches");
    $row_pitches = $stmt_pitches->fetch(PDO::FETCH_ASSOC);
    $total_pitches = $row_pitches['total'] ?? 0;
} catch (PDOException $e) {
    $total_pitches = 0;
}

// 4. LẤY DANH SÁCH ĐẶT SÂN GẦN ĐÂY (HIỂN THỊ TẤT CẢ TRẠNG THÁI, KHÔNG CHỈNH SỬA)
try {
    $sql = "SELECT b.*, p.name as pitch_name, u.fullname as user_name 
            FROM bookings b 
            LEFT JOIN pitches p ON b.pitch_id = p.id 
            LEFT JOIN users u ON b.user_id = u.id 
            ORDER BY b.id DESC LIMIT 10";
    $stmt = $pdo->query($sql);
    $recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_bookings = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Hệ Thống Quản Trị</title>
    <!-- Gọi file CSS tổng và file CSS riêng biệt của module dashboard -->
    <link rel="stylesheet" href="Style.css">
    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="sidebar-brand">
                <i class="fa-solid fa-futbol"></i>
                <span>Trang Quản Trị - Admin</span>
            </div>
            <ul class="sidebar-menu">
                <li><a href="Admin.php" class="active"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a></li>
                <li><a href="manage_pitches.php"><i class="fa-solid fa-futbol"></i> <span>Quản lý Sân bóng</span></a></li>
                <li><a href="manage_users.php"><i class="fa-solid fa-users"></i> <span>Quản lý Người dùng</span></a></li>
                <li><a href="manage_bookings.php"><i class="fa-solid fa-calendar-check"></i> <span>Quản lý Đặt sân</span></a></li>
                <li><a href="revenue_report.php"><i class="fa-solid fa-chart-pie"></i> <span>Báo cáo doanh thu</span></a></li>
            </ul>
            <div class="sidebar-footer">
                <li><a href="Admin.php?action=logout"><i class="fa-solid fa-right-from-bracket"></i> <span>Đăng Xuất</span></a></li>
                <a href="index.php" class="back-link">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Về trang Khách hàng</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="admin-main">
            <div class="admin-header">
                <h1>Bảng Điều Khiển (Dashboard) – Xin chào, Quản Trị Viên</h1>
            </div>

            <!-- 3 Thẻ Thống Kê -->
            <div class="stats-grid">
                <!-- Tổng doanh thu (Chỉ tính đơn đã duyệt) -->
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Tổng Doanh Thu</h3>
                        <div class="stat-value"><?php echo number_format($total_revenue, 0, ',', '.'); ?>đ</div>
                    </div>
                    <div class="stat-icon" style="background: #d1fae5; color: #059669;">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                </div>

                <!-- Lượt đặt sân -->
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Lượt Đặt Sân</h3>
                        <div class="stat-value"><?php echo $total_bookings; ?></div>
                    </div>
                    <div class="stat-icon" style="background: #e0f2fe; color: #0284c7;">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                </div>

                <!-- Sân hoạt động -->
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Sân Hoạt Động</h3>
                        <div class="stat-value"><?php echo $total_pitches; ?></div>
                    </div>
                    <div class="stat-icon" style="background: #f1f5f9; color: #475569;">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                </div>
            </div>

            <!-- Danh Sách Đặt Sân Gần Đây (Hiển thị tĩnh, đủ trạng thái, không chỉnh sửa) -->
            <div class="card-container" style="background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
                <div class="card-title" style="margin-bottom: 20px; font-weight: 700; color: #1e293b; font-size: 18px;">Danh Sách Lịch Đặt Sân Gần Đây</div>
                <table class="modern-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid #f1f5f9; text-align: left;">
                            <th style="padding: 12px; text-align: center;">Mã Đơn</th>
                            <th style="padding: 12px;">Khách Hàng</th>
                            <th style="padding: 12px;">Tên Sân</th>
                            <th style="padding: 12px;">Ngày Đặt</th>
                            <th style="padding: 12px;">Khung Giờ</th>
                            <th style="padding: 12px; text-align: center;">Trạng Thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_bookings)): ?>
                            <tr><td colspan="6" style="text-align: center; padding: 20px; color: #64748b;">Chưa có dữ liệu đặt sân nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_bookings as $b): ?>
                                <?php 
                                    $st = strtolower(trim($b['status'] ?? 'pending'));
                                    if ($st === 'confirmed' || $st === 'đã xác nhận') {
                                        $status_text = 'Đã duyệt';
                                        $status_class = 'status-confirmed';
                                    } elseif ($st === 'cancelled' || $st === 'đã hủy') {
                                        $status_text = 'Đã hủy';
                                        $status_class = 'status-cancelled';
                                    } else {
                                        $status_text = 'Chờ duyệt';
                                        $status_class = 'status-pending';
                                    }

                                    $customer_name = !empty($b['user_name']) ? $b['user_name'] : (!empty($b['userbooking_name']) ? $b['userbooking_name'] : 'Khách vãng lai');
                                ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 14px 12px; text-align: center;"><strong>#<?php echo $b['id']; ?></strong></td>
                                    <td style="padding: 14px 12px;"><?php echo htmlspecialchars($customer_name); ?></td>
                                    <td style="padding: 14px 12px;"><?php echo htmlspecialchars($b['pitch_name'] ?? 'Sân ID: ' . $b['pitch_id']); ?></td>
                                    <td style="padding: 14px 12px;"><?php echo htmlspecialchars($b['booking_date'] ?? $b['date'] ?? 'N/A'); ?></td>
                                    <td style="padding: 14px 12px;"><?php echo htmlspecialchars($b['time_slot'] ?? $b['time'] ?? 'N/A'); ?></td>
                                    <td style="padding: 14px 12px; text-align: center;">
                                        <span class="badge-status <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>