<?php
session_start();
$host = 'localhost';
$dbname = 'pitch_booking_db';
$username = 'root';
$password = '';

$message = '';
$error = '';
$bookings = [];

// Kiểm tra xem khách đã đăng nhập hay chưa
$is_logged_in = isset($_SESSION['user_id']);
$logged_user_id = $_SESSION['user_id'] ?? null;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Xử lý hủy sân
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking_id'])) {
        $booking_id = $_POST['cancel_booking_id'];

        // Lấy thông tin đơn đặt sân để kiểm tra thời gian
        $stmtCheck = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
        $stmtCheck->execute([$booking_id]);
        $targetBooking = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($targetBooking) {
            // Phân tích thời gian đặt sân: booking_date (YYYY-MM-DD) và time_slot (ví dụ: "16:30 (90 phút)")
            $booking_date = $targetBooking['booking_date'];
            preg_match('/^(\d{2}:\d{2})/', $targetBooking['time_slot'], $matches);
            $start_time = $matches[1] ?? '00:00';

            $booking_timestamp = strtotime("$booking_date $start_time");
            $current_timestamp = time();

            // Kiểm tra điều kiện: thời gian hủy phải cách giờ đá ít nhất 2 tiếng (7200 giây)
            if (($booking_timestamp - $current_timestamp) < 7200) {
                $error = 'Không thể hủy sân! Bạn chỉ được phép hủy lịch trước giờ đá ít nhất 2 tiếng.';
            } else {
                // Cập nhật trạng thái thành 'cancelled'
                $stmtCancel = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
                $stmtCancel->execute([$booking_id]);
                $message = 'Đã hủy lịch đặt sân thành công!';
            }
        }
    }

    // Xử lý hiển thị danh sách đặt sân
    if ($is_logged_in) {
        $stmt = $pdo->prepare("SELECT b.*, p.name as pitch_name FROM bookings b JOIN pitches p ON b.pitch_id = p.id WHERE b.user_id = ? ORDER BY b.id DESC");
        $stmt->execute([$logged_user_id]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_history'])) {
            $search_name = trim($_POST['search_name']);
            $search_phone = trim($_POST['search_phone']);
            $search_email = trim($_POST['search_email']);

            if (empty($search_name) || empty($search_phone) || empty($search_email)) {
                $error = 'Vui lòng điền đầy đủ Họ tên, Số điện thoại và Email để tra cứu lịch sử!';
            } else {
                $stmt = $pdo->prepare("SELECT b.*, p.name as pitch_name FROM bookings b JOIN pitches p ON b.pitch_id = p.id WHERE b.userbooking_name = ? AND b.phone = ? AND b.email = ? ORDER BY b.id DESC");
                $stmt->execute([$search_name, $search_phone, $search_email]);
                $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($bookings)) {
                    $error = 'Không tìm thấy lịch sử đặt sân nào khớp với thông tin bạn cung cấp!';
                }
            }
        }
    }

} catch (PDOException $e) {
    $error = "Lỗi kết nối cơ sở dữ liệu: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Sử Đặt Sân - PitchBooking</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="history-container">
        <div class="history-header">
            <h2><i class="fa-solid fa-clock-rotate-left icon-green"></i> Lịch Sử Đặt Sân</h2>
            <a href="index.php" class="back-home"><i class="fa-solid fa-arrow-left"></i> Về trang chủ</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- NẾU CHƯA ĐĂNG NHẬP: HIỆN FORM TRA CỨU CHO KHÁCH VÃNG LAI -->
        <?php if (!$is_logged_in): ?>
            <div class="search-box">
                <p class="search-title">Tra cứu lịch sử đặt sân</p>
                <form action="History.php" method="POST">
                    <div class="search-grid">
                        <input type="text" name="search_name" placeholder="Họ và tên..." required class="form-input">
                        <input type="text" name="search_phone" placeholder="Số điện thoại..." required class="form-input">
                    </div>
                    <div class="search-full">
                        <input type="email" name="search_email" placeholder="Email liên hệ..." required class="form-input">
                    </div>
                    <button type="submit" name="search_history" class="btn-search">Tra Cứu Ngay</button>
                </form>
            </div>
        <?php else: ?>
            <p class="welcome-text">Dưới đây là toàn bộ các sân bóng mà tài khoản của bạn đã đặt:</p>
        <?php endif; ?>

        <!-- HIỂN THỊ DANH SÁCH ĐẶT SÂN -->
        <?php if (!empty($bookings)): ?>
            <div>
                <?php foreach ($bookings as $item): ?>
                    <div class="booking-card-item">
                        <div class="booking-info">
                            <p><strong>Sân:</strong> <span class="pitch-name"><?php echo htmlspecialchars($item['pitch_name']); ?></span></p>
                            <p><strong>Ngày đá:</strong> <?php echo htmlspecialchars($item['booking_date']); ?> | <strong>Khung giờ:</strong> <?php echo htmlspecialchars($item['time_slot']); ?></p>
                            <p><strong>Người đặt:</strong> <?php echo htmlspecialchars($item['userbooking_name']); ?> (SĐT: <?php echo htmlspecialchars($item['phone']); ?>)</p>
                            <p><strong>Tổng tiền:</strong> <span class="total-price"><?php echo number_format($item['total_price'], 0, ',', '.'); ?>đ</span></p>
                            <?php if (!empty($item['note'])): ?>
                                <p><strong>Ghi chú:</strong> <?php echo htmlspecialchars($item['note']); ?></p>
                            <?php endif; ?>
                            <p><strong>Trạng thái:</strong> 
                                <?php if ($item['status'] === 'cancelled'): ?>
                                    <span class="status-cancelled">Đã hủy</span>
                                <?php else: ?>
                                    <span class="status-pending">Đã xác nhận (Chờ đá)</span>
                                <?php endif; ?>
                            </p>
                        </div>

                        <div>
                            <?php if ($item['status'] !== 'cancelled'): ?>
                                <form action="History.php" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn hủy lịch đặt sân này không?');">
                                    <input type="hidden" name="cancel_booking_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn-cancel"><i class="fa-solid fa-ban"></i> Hủy Sân</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (($is_logged_in) || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_history']))): ?>
            <p class="no-result">Không có lịch sử đặt sân nào được tìm thấy.</p>
        <?php endif; ?>
    </div>
</body>
</html>