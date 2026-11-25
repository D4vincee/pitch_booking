<?php
session_start();
require_once 'db.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

$message = '';
$error = '';

// 1. XỬ LÝ XÓA TÀI KHOẢN NGƯỜI DÙNG
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    // Ngăn admin tự xóa chính tài khoản mình đang đăng nhập (nếu cần thiết, hoặc cho phép tùy ý)
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $message = "Đã xóa tài khoản người dùng thành công!";
    } catch (PDOException $e) {
        $error = "Không thể xóa tài khoản này vì đang có dữ liệu liên quan!";
    }
}

// Lấy danh sách người dùng chuẩn từ bảng users trong CSDL
try {
    $stmtUsers = $pdo->query("SELECT id, fullname, email, role FROM users ORDER BY id DESC");
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
    $error = "Lỗi truy vấn cơ sở dữ liệu: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Người Dùng - Admin Dashboard</title>
    <!-- Gọi file CSS tổng và file CSS riêng biệt -->
    <link rel="stylesheet" href="Style.css">
    <link rel="stylesheet" href="manage_users.css">
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
                <li><a href="manage_users.php" class="active"><i class="fa-solid fa-users"></i> <span>Quản lý Người dùng</span></a></li>
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
                <h1>Quản Lý Người Dùng</h1>
            </div>

            <?php if (!empty($error)): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px;"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($message)): ?>
                <div style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 20px;"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="card-container">
                <div class="card-title" style="margin-bottom: 15px; font-weight: 700; color: #1e293b;">Danh Sách Tài Khoản Trong Hệ Thống</div>
                <table class="modern-table">
                    <colgroup>
                        <col style="width: 10%;">
                        <col style="width: 30%;">
                        <col style="width: 35%;">
                        <col style="width: 15%;">
                        <col style="width: 10%;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th style="text-align: center;">ID</th>
                            <th>Họ và tên</th>
                            <th>Email đăng nhập</th>
                            <th>Vai trò (Role)</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="5" style="text-align: center; color: #64748b;">Chưa có tài khoản người dùng nào trong hệ thống.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td style="text-align: center;"><strong>#<?php echo $u['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($u['fullname']); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td>
                                        <?php if ($u['role'] === 'admin'): ?>
                                            <span class="badge-role badge-admin">Quản trị (Admin)</span>
                                        <?php else: ?>
                                            <span class="badge-role badge-customer">Khách hàng</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="manage_users.php?action=delete&id=<?php echo $u['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa tài khoản này không?');">
                                                <i class="fa-solid fa-trash"></i> Xóa
                                            </a>
                                        </div>
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