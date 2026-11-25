<?php
session_start();
require_once 'db.php'; // File kết nối PDO của bạn

$keyword = trim($_GET['keyword'] ?? '');
$pitches = [];

try {
    if ($keyword !== '') {
        // Tìm kiếm theo tên sân (name) hoặc khu vực/địa điểm (location) sử dụng toán tử LIKE
        $stmt = $pdo->prepare("SELECT * FROM pitches WHERE name LIKE ? OR location ? ORDER BY id DESC");
        // Sửa lại đoạn execute bên dưới với ký tự đại diện %
        $searchTerm = "%" . $keyword . "%";
        $stmt = $pdo->prepare("SELECT * FROM pitches WHERE name LIKE ? OR location LIKE ? ORDER BY id DESC");
        $stmt->execute([$searchTerm, $searchTerm]);
        $pitches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Nếu không nhập gì mà bấm tìm kiếm thì lấy toàn bộ
        $stmt = $pdo->query("SELECT * FROM pitches ORDER BY id DESC");
        $pitches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = "Lỗi cơ sở dữ liệu: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết Quả Tìm Kiếm - PitchBooking</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <header class="user-header">
        <div class="user-navbar" style="display: flex; justify-content: space-between; align-items: center;">
            <div class="user-logo">
                <i class="fa-solid fa-futbol"></i> PitchBooking
            </div>
            <ul class="user-nav-links" style="display: flex; align-items: center; gap: 20px; list-style: none; margin: 0;">
                <li><a href="index.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">Trang Chủ</a></li>
            </ul>
            <div class="user-nav-right">
                <a href="History.php" class="btn-history" style="background: #0284c7; color: white; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 600;">
                    <i class="fa-solid fa-history"></i> Lịch Sử Đặt Sân
                </a>
            </div>
        </div>
    </header>

    <div class="container" style="margin-top: 40px; margin-bottom: 40px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h2>Kết quả tìm kiếm cho: "<span style="color: #10b981;"><?php echo htmlspecialchars($keyword); ?></span>"</h2>
            <a href="index.php" style="text-decoration: none; color: #0284c7; font-weight: 600;"><i class="fa-solid fa-arrow-left"></i> Về trang chủ</a>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px;"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (empty($pitches)): ?>
            <div style="text-align: center; padding: 50px; background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <p style="color: #64748b; font-size: 16px;">Không tìm thấy sân bóng nào phù hợp với từ khóa của bạn.</p>
                <a href="index.php" class="btn-primary" style="display: inline-block; margin-top: 15px; text-decoration: none; padding: 10px 20px; border-radius: 6px;">Quay lại trang chủ</a>
            </div>
        <?php else: ?>
            <div class="grid-cards">
                <?php foreach ($pitches as $pitch): ?>
                    <div class="card">
                        <div class="card-img-wrap">
                            <img src="<?php echo htmlspecialchars($pitch['image_url']); ?>" alt="Sân bóng">
                            <span class="card-badge"><?php echo htmlspecialchars($pitch['category']); ?></span>
                        </div>
                        <div class="card-body">
                            <div class="card-title"><?php echo htmlspecialchars($pitch['name']); ?></div>
                            <div class="card-location"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($pitch['location']); ?></div>
                            
                            <form action="Booking.php" method="POST" class="card-footer-info">
                                <input type="hidden" name="pitch_id" value="<?php echo $pitch['id']; ?>">
                                <input type="hidden" name="total_price" value="<?php echo $pitch['price']; ?>">
                                <span class="price">
                                    <?php echo number_format($pitch['price'], 0, ',', '.'); ?>đ 
                                    <sub style="font-size:0.75rem; color:var(--text-muted); font-weight:normal;">/ trận</sub>
                                </span>
                                <button type="submit" class="btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Đặt ngay</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>