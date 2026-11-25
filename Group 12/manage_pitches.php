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

// 1. XỬ LÝ XÓA SÂN BÓNG
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $pitch_id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM pitches WHERE id = ?");
        $stmt->execute([$pitch_id]);
        $message = "Đã xóa sân bóng thành công!";
    } catch (PDOException $e) {
        $error = "Không thể xóa sân này vì đang có dữ liệu đặt sân liên quan!";
    }
}

// 2. XỬ LÝ THÊM HOẶC CẬP NHẬT SÂN BÓNG (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pitch_id = $_POST['pitch_id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');

    if (empty($name) || empty($location) || empty($category) || empty($price) || empty($image_url)) {
        $error = "Vui lòng điền đầy đủ tất cả các trường!";
    } else {
        if (empty($pitch_id)) {
            // Thêm mới
            try {
                $stmt = $pdo->prepare("INSERT INTO pitches (name, location, category, price, image_url) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $location, $category, $price, $image_url]);
                $message = "Thêm sân bóng mới thành công!";
            } catch (PDOException $e) {
                $error = "Lỗi CSDL: " . $e->getMessage();
            }
        } else {
            // Cập nhật (Sửa)
            try {
                $stmt = $pdo->prepare("UPDATE pitches SET name = ?, location = ?, category = ?, price = ?, image_url = ? WHERE id = ?");
                $stmt->execute([$name, $location, $category, $price, $image_url, $pitch_id]);
                $message = "Cập nhật thông tin sân bóng thành công!";
            } catch (PDOException $e) {
                $error = "Lỗi CSDL: " . $e->getMessage();
            }
        }
    }
}

// Lấy danh sách sân bóng từ CSDL
$stmtPitches = $pdo->query("SELECT * FROM pitches ORDER BY id DESC");
$pitches = $stmtPitches->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Sân Bóng - Admin Dashboard</title>
    <!-- Gọi file CSS tổng và file CSS riêng biệt -->
    <link rel="stylesheet" href="Style.css">
    <link rel="stylesheet" href="manage_pitches.css">
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
                <li><a href="manage_pitches.php" class="active"><i class="fa-solid fa-futbol"></i> <span>Quản lý Sân bóng</span></a></li>
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
                <h1>Quản Lý Sân Bóng</h1>
                <button type="button" class="btn-primary" onclick="openAddModal()"><i class="fa-solid fa-plus"></i> Thêm Sân Mới</button>
            </div>

            <?php if (!empty($error)): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px;"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($message)): ?>
                <div style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 20px;"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="card-container">
                <div class="card-title" style="margin-bottom: 15px; font-weight: 700; color: #1e293b;">Danh Sách Toàn Bộ Sân Bóng</div>
                <table class="modern-table">
                    <colgroup>
                        <col style="width: 7%;">
                        <col style="width: 12%;">
                        <col style="width: 23%;">
                        <col style="width: 22%;">
                        <col style="width: 13%;">
                        <col style="width: 13%;">
                        <col style="width: 10%;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Hình ảnh</th>
                            <th>Tên sân</th>
                            <th>Khu vực</th>
                            <th>Loại sân</th>
                            <th>Giá (VNĐ)</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pitches)): ?>
                            <tr><td colspan="7" style="text-align: center; color: #64748b;">Chưa có sân bóng nào trong hệ thống.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pitches as $p): ?>
                                <tr>
                                    <td><strong>#<?php echo $p['id']; ?></strong></td>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($p['image_url']); ?>" alt="Img" class="pitch-img-thumb" width="80" height="55" onerror="this.src='https://via.placeholder.com/80x55'">
                                    </td>
                                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                                    <td><?php echo htmlspecialchars($p['location']); ?></td>
                                    <td><?php echo htmlspecialchars($p['category']); ?></td>
                                    <td><span style="color: #10b981; font-weight: bold;"><?php echo number_format($p['price'], 0, ',', '.'); ?>đ</span></td>
                                    <td>
                                        <div class="action-btns">
                                            <button type="button" class="btn-action btn-edit" 
                                                onclick="openEditModal('<?php echo $p['id']; ?>', '<?php echo addslashes($p['name']); ?>', '<?php echo addslashes($p['location']); ?>', '<?php echo addslashes($p['category']); ?>', '<?php echo $p['price']; ?>', '<?php echo addslashes($p['image_url']); ?>')">
                                                <i class="fa-solid fa-pen"></i> Sửa
                                            </button>
                                            <a href="manage_pitches.php?action=delete&id=<?php echo $p['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa sân này không?');"><i class="fa-solid fa-trash"></i> Xóa</a>
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

    <!-- MODAL THÊM / SỬA SÂN BÓNG -->
    <div id="pitchModal" class="admin-modal" onclick="outsideClick(event)">
        <div class="admin-modal-content">
            <div class="admin-modal-header">
                <h3 id="modalTitle"><i class="fa-solid fa-plus-circle" style="color: #10b981;"></i> Thêm Sân Bóng Mới</h3>
                <button type="button" class="admin-close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form action="manage_pitches.php" method="POST" id="pitchForm">
                <input type="hidden" name="pitch_id" id="pitch_id">
                <div class="admin-form-group">
                    <label>Tên sân bóng</label>
                    <input type="text" name="name" id="name" required placeholder="VD: Sân Bóng Chảo Lửa">
                </div>
                <div class="admin-form-group">
                    <label>Địa điểm / Khu vực</label>
                    <input type="text" name="location" id="location" required placeholder="VD: Q. Tân Bình, TP.HCM">
                </div>
                <div class="admin-form-group">
                    <label>Loại sân</label>
                    <select name="category" id="category" required>
                        <option value="Sân 5 người">Sân 5 người</option>
                        <option value="Sân 7 người">Sân 7 người</option>
                        <option value="Sân 11 người">Sân 11 người</option>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label>Đơn giá cơ bản (VNĐ)</label>
                    <input type="number" name="price" id="price" required placeholder="VD: 350000">
                </div>
                <div class="admin-form-group">
                    <label>Đường dẫn hình ảnh (Image URL)</label>
                    <input type="text" name="image_url" id="image_url" required placeholder="VD: https://...">
                </div>
                <div class="admin-modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Hủy bỏ</button>
                    <button type="submit" class="btn-primary">Lưu Lại</button>
                </div>
            </form>
        </div>
    </div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-plus-circle" style="color: #10b981;"></i> Thêm Sân Bóng Mới';
    document.getElementById('pitchForm').reset();
    document.getElementById('pitch_id').value = '';
    document.getElementById('pitchModal').style.display = 'flex';
}

function openEditModal(id, name, location, category, price, image_url) {
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square" style="color: #0284c7;"></i> Chỉnh Sửa Sân Bóng';
    
    document.getElementById('pitch_id').value = id;
    document.getElementById('name').value = name;
    document.getElementById('location').value = location;
    document.getElementById('category').value = category;
    document.getElementById('price').value = price;
    document.getElementById('image_url').value = image_url;
    
    document.getElementById('pitchModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('pitchModal').style.display = 'none';
    document.getElementById('pitchForm').reset();
}

function outsideClick(event) {
    const modal = document.getElementById('pitchModal');
    if (event.target === modal) {
        closeModal();
    }
}
</script>
</body>
</html>