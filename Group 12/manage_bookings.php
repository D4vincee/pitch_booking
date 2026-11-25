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

// 1. XỬ LÝ CẬP NHẬT TRẠNG THÁI QUA AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id']) && isset($_POST['new_status'])) {
    $booking_id = $_POST['booking_id'];
    $new_status = $_POST['new_status'];
    
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $booking_id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 2. XỬ LÝ XÓA LỊCH ĐẶT SÂN
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $booking_id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $message = "Đã xóa lịch đặt sân thành công!";
    } catch (PDOException $e) {
        $error = "Lỗi khi xóa lịch đặt: " . $e->getMessage();
    }
}

// 3. LẤY DANH SÁCH ĐẶT SÂN (Ưu tiên lấy tên từ bảng users, nếu không có lấy userbooking_name)
try {
    $sql = "SELECT b.*, p.name as pitch_name, u.fullname as user_name 
            FROM bookings b 
            LEFT JOIN pitches p ON b.pitch_id = p.id 
            LEFT JOIN users u ON b.user_id = u.id 
            ORDER BY b.id DESC";
    $stmt = $pdo->query($sql);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    try {
        $stmt = $pdo->query("SELECT * FROM bookings ORDER BY id DESC");
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $bookings = [];
        $error = "Không thể tải dữ liệu bảng bookings: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Đặt Sân - Admin Dashboard</title>
    <!-- Gọi file CSS tổng và file CSS riêng biệt -->
    <link rel="stylesheet" href="Style.css">
    <link rel="stylesheet" href="manage_bookings.css">
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
                <li><a href="manage_bookings.php" class="active"><i class="fa-solid fa-calendar-check"></i> <span>Quản lý Đặt sân</span></a></li>
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
                <h1>Quản Lý Đặt Sân</h1>
            </div>

            <?php if (!empty($error)): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px;"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($message)): ?>
                <div style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 20px;"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="card-container">
                <div class="card-title" style="margin-bottom: 15px; font-weight: 700; color: #1e293b;">Danh Sách Lịch Đặt Sân Từ Khách Hàng</div>
                <table class="modern-table">
                    <colgroup>
                        <col style="width: 7%;">
                        <col style="width: 22%;">
                        <col style="width: 18%;">
                        <col style="width: 12%;">
                        <col style="width: 14%;">
                        <col style="width: 17%;">
                        <col style="width: 10%;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th style="text-align: center;">ID</th>
                            <th>Khách hàng</th>
                            <th>Tên sân</th>
                            <th>Ngày đặt</th>
                            <th>Khung giờ</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr><td colspan="7" style="text-align: center; color: #64748b;">Chưa có lịch đặt sân nào trong hệ thống.</td></tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $b): ?>
                                <?php 
                                    $current_status = strtolower(trim($b['status'] ?? 'pending'));
                                    
                                    // Xác định tên khách: Nếu có user_name thì dùng, không thì lấy userbooking_name từ khách vãng lai
                                    if (!empty($b['user_name'])) {
                                        $display_name = $b['user_name'];
                                        $is_guest = false;
                                    } else {
                                        $display_name = !empty($b['userbooking_name']) ? $b['userbooking_name'] : 'Khách vãng lai';
                                        $is_guest = true;
                                    }
                                ?>
                                <tr>
                                    <td style="text-align: center;"><strong>#<?php echo $b['id']; ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($display_name); ?>
                                        <?php if ($is_guest): ?>
                                            <span class="badge-guest" title="Khách chưa đăng ký tài khoản">Vãng lai</span>
                                        <?php else: ?>
                                            <span class="badge-member" title="Khách có tài khoản thành viên">Thành viên</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($b['pitch_name'] ?? 'Sân ID: ' . ($b['pitch_id'] ?? 'N/A')); ?></td>
                                    <td><?php echo htmlspecialchars($b['booking_date'] ?? $b['date'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($b['time_slot'] ?? $b['time'] ?? 'N/A'); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 6px; align-items: center;">
                                            <select id="status_<?php echo $b['id']; ?>" class="status-select">
                                                <option value="pending" <?php echo ($current_status === 'pending' || $current_status === 'chờ duyệt') ? 'selected' : ''; ?>>Chờ duyệt</option>
                                                <option value="confirmed" <?php echo ($current_status === 'confirmed' || $current_status === 'đã xác nhận') ? 'selected' : ''; ?>>Đã xác nhận</option>
                                                <option value="cancelled" <?php echo ($current_status === 'cancelled' || $current_status === 'đã hủy') ? 'selected' : ''; ?>>Đã hủy</option>
                                            </select>
                                            <button type="button" onclick="updateStatus(<?php echo $b['id']; ?>)" class="btn-action" style="background: #e0f2fe; color: #0284c7; padding: 6px 8px;" title="Cập nhật trạng thái">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="manage_bookings.php?action=delete&id=<?php echo $b['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa lịch đặt này không?');" title="Xóa">
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

    <script>
    function updateStatus(bookingId) {
        const selectElement = document.getElementById('status_' + bookingId);
        const newStatus = selectElement.value;

        const formData = new FormData();
        formData.append('booking_id', bookingId);
        formData.append('new_status', newStatus);

        fetch('manage_bookings.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Lỗi cập nhật: ' + (data.error || 'Không xác định'));
            }
        })
        .catch(error => {
            console.error('Lỗi kết nối:', error);
            alert('Không thể kết nối đến máy chủ!');
        });
    }
    </script>
</body>
</html>