<?php
session_start();
require_once 'db.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// Xử lý bộ lọc theo tháng hoặc ngày nếu có
$filter_month = $_GET['month'] ?? '';
$where_clause = "WHERE (status = 'confirmed' OR status = 'Đã xác nhận')";
$params = [];

if (!empty($filter_month)) {
    $where_clause .= " AND DATE_FORMAT(booking_date, '%Y-%m') = ?";
    $params[] = $filter_month;
}

// 1. TÍNH TỔNG DOANH THU & SỐ LƯỢNG ĐƠN ĐÃ DUYỆT
try {
    $stmt_stats = $pdo->prepare("SELECT SUM(total_price) as total_rev, COUNT(*) as total_orders FROM bookings $where_clause");
    $stmt_stats->execute($params);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    $total_revenue = $stats['total_rev'] ?? 0;
    $total_confirmed_orders = $stats['total_orders'] ?? 0;
} catch (PDOException $e) {
    $total_revenue = 0;
    $total_confirmed_orders = 0;
}

// 2. TỔNG SỐ ĐƠN BỊ HỦY (để quản lý nắm tổng quan)
try {
    $stmt_cancelled = $pdo->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'cancelled' OR status = 'Đã hủy'");
    $row_can = $stmt_cancelled->fetch(PDO::FETCH_ASSOC);
    $total_cancelled_orders = $row_can['total'] ?? 0;
} catch (PDOException $e) {
    $total_cancelled_orders = 0;
}

// 3. LẤY DANH SÁCH CHI TIẾT CÁC GIAO DỊCH ĐÃ DUYỆT
try {
    $sql = "SELECT b.*, p.name as pitch_name, u.fullname as user_name 
            FROM bookings b 
            LEFT JOIN pitches p ON b.pitch_id = p.id 
            LEFT JOIN users u ON b.user_id = u.id 
            $where_clause 
            ORDER BY b.id DESC";
    $stmt_list = $pdo->prepare($sql);
    $stmt_list->execute($params);
    $revenue_bookings = $stmt_list->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $revenue_bookings = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo Cáo Doanh Thu - Admin Dashboard</title>
    <!-- Gọi file CSS tổng, file CSS của trang Admin và file CSS riêng biệt -->
    <link rel="stylesheet" href="Style.css">
    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="stylesheet" href="revenue_report.css">
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
                <li><a href="Admin.php"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a></li>
                <li><a href="manage_pitches.php"><i class="fa-solid fa-futbol"></i> <span>Quản lý Sân bóng</span></a></li>
                <li><a href="manage_users.php"><i class="fa-solid fa-users"></i> <span>Quản lý Người dùng</span></a></li>
                <li><a href="manage_bookings.php"><i class="fa-solid fa-calendar-check"></i> <span>Quản lý Đặt sân</span></a></li>
                <li><a href="revenue_report.php" class="active"><i class="fa-solid fa-chart-pie"></i> <span>Báo cáo doanh thu</span></a></li>
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
                <h1>Báo Cáo Doanh Thu Hệ Thống</h1>
            </div>

            <!-- Thẻ thống kê tổng quan -->
            <div class="revenue-stats-grid">
                <div class="revenue-card">
                    <div class="revenue-card-info">
                        <h3>Tổng Doanh Thu (Đã duyệt)</h3>
                        <div class="revenue-value" style="color: #059669;"><?php echo number_format($total_revenue, 0, ',', '.'); ?>đ</div>
                    </div>
                    <div class="revenue-card-icon" style="background: #d1fae5; color: #059669;">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                </div>

                <div class="revenue-card">
                    <div class="revenue-card-info">
                        <h3>Đơn Đặt Sân Thành Công</h3>
                        <div class="revenue-value"><?php echo $total_confirmed_orders; ?></div>
                    </div>
                    <div class="revenue-card-icon" style="background: #e0f2fe; color: #0284c7;">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>

                <div class="revenue-card">
                    <div class="revenue-card-info">
                        <h3>Đơn Đã Hủy</h3>
                        <div class="revenue-value" style="color: #dc2626;"><?php echo $total_cancelled_orders; ?></div>
                    </div>
                    <div class="revenue-card-icon" style="background: #fee2e2; color: #dc2626;">
                        <i class="fa-solid fa-ban"></i>
                    </div>
                </div>
            </div>

            <!-- Bộ lọc theo tháng -->
            <form action="revenue_report.php" method="GET" class="filter-container">
                <div class="filter-group">
                    <label for="month">Lọc theo tháng:</label>
                    <input type="month" id="month" name="month" value="<?php echo htmlspecialchars($filter_month); ?>" class="filter-input">
                </div>
                <button type="submit" class="btn-filter">
                    <i class="fa-solid fa-filter"></i> Lọc dữ liệu
                </button>
                <?php if (!empty($filter_month)): ?>
                    <a href="revenue_report.php" style="padding: 10px 15px; background: #f1f5f9; color: #475569; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;">Bỏ lọc</a>
                <?php endif; ?>
            </form>

            <!-- Bảng danh sách doanh thu chi tiết -->
            <div class="card-container">
                <div class="card-title">Chi Tiết Giao Dịch Doanh Thu (Đã Duyệt)</div>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th style="text-align: center;">Mã Đơn</th>
                            <th>Khách Hàng</th>
                            <th>Tên Sân</th>
                            <th>Ngày Đặt</th>
                            <th>Khung Giờ</th>
                            <th style="text-align: right;">Số Tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($revenue_bookings)): ?>
                            <tr><td colspan="6" style="text-align: center; color: #64748b; padding: 20px;">Không có dữ liệu doanh thu trong khoảng thời gian này.</td></tr>
                        <?php else: ?>
                            <?php foreach ($revenue_bookings as $b): ?>
                                <?php 
                                    $customer_name = !empty($b['user_name']) ? $b['user_name'] : (!empty($b['userbooking_name']) ? $b['userbooking_name'] : 'Khách vãng lai');
                                ?>
                                <tr>
                                    <td style="text-align: center;"><strong>#<?php echo $b['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($customer_name); ?></td>
                                    <td><?php echo htmlspecialchars($b['pitch_name'] ?? 'Sân ID: ' . $b['pitch_id']); ?></td>
                                    <td><?php echo htmlspecialchars($b['booking_date'] ?? $b['date'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($b['time_slot'] ?? $b['time'] ?? 'N/A'); ?></td>
                                    <td style="text-align: right;" class="money-text">
                                        +<?php echo number_format($b['total_price'] ?? 0, 0, ',', '.'); ?>đ
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