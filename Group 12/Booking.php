<?php
session_start();
$host = 'localhost';$dbname = 'pitch_booking_db';
$username = 'root';$password = '';
$pitch_id =$_POST['pitch_id'] ?? $_GET['pitch_id'] ?? '';$raw_base_price = $_POST['total_price'] ?? $_GET['total_price'] ?? 0;

// Làm tròn LÊN giá gốc ngay từ đầu đến hàng nghìn
$base_price = ceil($raw_base_price / 1000) * 1000;

// Kiểm tra xem người dùng đã đăng nhập hay chưa
$user_id =$_SESSION['user_id'] ?? null;
$user_name =$_SESSION['user_name'] ?? '';
$user_email =$_SESSION['user_email'] ?? '';

$error = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Lấy thông tin chi tiết của sân bóng
    $stmtPitch =$pdo->prepare("SELECT * FROM pitches WHERE id = ?");
    $stmtPitch->execute([$pitch_id]);
    $pitch =$stmtPitch->fetch(PDO::FETCH_ASSOC);

    // Xử lý khi người dùng bấm xác nhận đặt sân
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_booking'])) {
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $booking_date =$_POST['booking_date'];
        $start_time =$_POST['start_time']; 
        $duration =$_POST['duration'];     // 60, 90, hoặc 120 phút
        $note = trim($_POST['note'] ?? '');$status = 'pending';

        // Biểu thức chính quy (Regex) kiểm tra số điện thoại chuẩn Việt Nam (10 số, bắt đầu bằng 03, 05, 07, 08, 09)
        $vietnam_phone_pattern = '/^(03|05|07|08|09)[0-9]{8}$/';

        // Kiểm tra nếu đặt sân vào ngày hôm nay thì giờ bắt đầu phải cách hiện tại ít nhất 2 tiếng
        $current_date = date('Y-m-d');$current_timestamp = time(); // Thời gian hiện tại (Server time)
        $selected_timestamp = strtotime($booking_date . ' ' .$start_time); // Thời gian khách chọn

        if (empty($fullname) || empty($email) || empty($phone) || empty($booking_date) || empty($start_time) || empty($duration)) {$error = 'Vui lòng điền đầy đủ tất cả các thông tin bắt buộc trong form!';
        } elseif (!preg_match($vietnam_phone_pattern, $phone)) {$error = 'Số điện thoại không hợp lệ! Vui lòng nhập đúng số điện thoại di động Việt Nam gồm 10 chữ số (VD: 0912345678).';
        } elseif ($booking_date < $current_date) {$error = 'Không thể đặt sân cho ngày trong quá khứ!';
        } elseif ($booking_date === $current_date && ($selected_timestamp - $current_timestamp) < (2 * 3600)) {$error = 'Nếu đặt sân trong ngày hôm nay, bạn phải đặt trước ít nhất 2 tiếng so với giờ hiện tại!';
        } else {
            $time_slot = $start_time . ' (' .$duration . ' phút)';
            
            // Tính toán và làm tròn LÊN đến hàng nghìn dựa theo đúng thời lượng khách chọn
            $calculated_price =$base_price;
            if ($duration == '60') {$calculated_price = ceil(($base_price * (60 / 90)) / 1000) * 1000;
            } elseif ($duration == '120') {
                $calculated_price = ceil(($base_price * (120 / 90)) / 1000) * 1000;
            } else {
                $calculated_price = ceil($base_price / 1000) * 1000;
            }

            // Lấy giá trị chính xác từ input ẩn phía client gửi lên
            if (isset($_POST['total_price']) && is_numeric($_POST['total_price']) &&$_POST['total_price'] > 0) {
                $calculated_price = ceil(floatval($_POST['total_price']) / 1000) * 1000;
            }

            $db_user_id = $user_id ? $user_id : null; 

            $sql = "INSERT INTO bookings (user_id, userbooking_name, email, phone, pitch_id, booking_date, time_slot, total_price, note, status) 
                    VALUES (:user_id, :userbooking_name, :email, :phone, :pitch_id, :booking_date, :time_slot, :total_price, :note, :status)";
            
            $stmt =$pdo->prepare($sql);$stmt->execute([
                'user_id' => $db_user_id,
                'userbooking_name' => $fullname,
                'email' => $email,
                'phone' => $phone,
                'pitch_id' => $pitch_id,
                'booking_date' => $booking_date,
                'time_slot' => $time_slot,
                'total_price' => $calculated_price,
                'note' => $note,
                'status' => $status
            ]);

            echo "<script>alert('Đặt sân thành công! Dữ liệu đã lưu vào database.'); window.location.href='index.php';</script>";
            exit;
        }
    }
} catch (PDOException $e) {$error = "Lỗi kết nối cơ sở dữ liệu: " . $e->getMessage();
}

$initial_display_price = ceil($base_price / 1000) * 1000;
$min_date = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác Nhận Đặt Sân - PitchBooking</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-card" style="max-width: 500px;">
        <h2><i class="fa-solid fa-calendar-check" style="color: #10b981;"></i> Xác Nhận Đặt Sân</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($pitch): ?>
            <p style="margin-bottom: 15px; font-size: 14px; color: var(--text-muted);">
                Đang đặt sân: <strong style="color: var(--accent);"><?php echo htmlspecialchars($pitch['name']); ?></strong>
            </p>
        <?php endif; ?>

        <form action="Booking.php" method="POST" id="bookingForm" onsubmit="return validateForm()">
            <input type="hidden" name="pitch_id" value="<?php echo htmlspecialchars($pitch_id); ?>">
            <input type="hidden" id="base_price" value="<?php echo htmlspecialchars($base_price); ?>">
            <input type="hidden" name="total_price" id="real_total_price" value="<?php echo htmlspecialchars($initial_display_price); ?>">

            <div class="form-group">
                <label>Họ và Tên</label>
                <input type="text" name="fullname" required 
                       value="<?php echo htmlspecialchars($user_name); ?>" 
                       placeholder="Nhập họ tên của bạn...">
            </div>

            <div class="form-group">
                <label>Email liên hệ</label>
                <input type="email" name="email" required 
                       value="<?php echo htmlspecialchars($user_email); ?>" 
                       placeholder="nguyenvanA@gmail.com">
            </div>

            <div class="form-group">
                <label>Số Điện Thoại</label>
                <input type="text" name="phone" id="phoneInput" required placeholder="Nhập số điện thoại (VD: 0912345678)..." maxlength="10">
            </div>

            <div class="form-group">
                <label>Ngày Đặt Sân</label>
                <!-- Thêm thuộc tính min để chặn ngày quá khứ -->
                <input type="date" name="booking_date" id="bookingDateInput" required min="<?php echo $min_date; ?>" value="<?php echo $min_date; ?>">
            </div>

            <div class="form-group">
                <label>Thời Lượng Thuê</label>
                <select name="duration" id="durationSelect" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; background: #fff;" onchange="updatePrice()">
                    <option value="60">60 phút (1 tiếng)</option>
                    <option value="90" selected>90 phút (1.5 tiếng - Tiêu chuẩn)</option>
                    <option value="120">120 phút (2 tiếng)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Giờ Bắt Đầu</label>
                <select name="start_time" id="startTimeSelect" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; background: #fff;">
                    <option value="07:00">07:00</option>
                    <option value="08:30">08:30</option>
                    <option value="15:00">15:00</option>
                    <option value="16:30" selected>16:30</option>
                    <option value="18:00">18:00</option>
                    <option value="19:30">19:30</option>
                    <option value="21:00">21:00</option>
                </select>
            </div>

            <div class="form-group">
                <label>Ghi Chú / Yêu Cầu Thêm <span style="font-weight: normal; color: #888; font-size: 13px;">(Không bắt buộc)</span></label>
                <textarea name="note" rows="3" placeholder="Ví dụ: Thuê thêm bóng, chuẩn bị nước uống..." style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; background: #fff; font-family: inherit; resize: vertical;"></textarea>
            </div>

            <div style="margin-bottom: 20px; font-size: 16px; font-weight: bold; color: var(--primary-dark);">
                Tổng tiền: <span id="priceDisplay"><?php echo number_format($initial_display_price, 0, ',', '.'); ?></span>đ
            </div>

            <button type="submit" name="confirm_booking" class="btn-login">Xác Nhận Đặt Sân</button>
        </form>
        
        <div class="register-link" style="margin-top: 15px;">
            <a href="index.php"><i class="fa-solid fa-arrow-left"></i> Quay lại trang chủ</a>
        </div>
    </div>

    <script>
    function updatePrice() {
        const basePrice = parseFloat(document.getElementById('base_price').value) || 0;
        const duration = document.getElementById('durationSelect').value;
        let finalPrice = basePrice;

        if (duration === '60') {
            finalPrice = basePrice * (60 / 90);
        } else if (duration === '120') {
            finalPrice = basePrice * (120 / 90);
        }

        const roundedPrice = Math.ceil(finalPrice / 1000) * 1000;

        document.getElementById('real_total_price').value = roundedPrice;
        document.getElementById('priceDisplay').innerText = roundedPrice.toLocaleString('vi-VN');
    }

    // Kiểm tra toàn bộ điều kiện trước khi submit form bằng JavaScript
    function validateForm() {
        // 1. Kiểm tra SĐT Việt Nam
        const phoneInput = document.getElementById('phoneInput').value.trim();
        const vnpRegex = /^(03|05|07|08|09)[0-9]{8}$/;

        if (!vnpRegex.test(phoneInput)) {
            alert('Số điện thoại không hợp lệ! Vui lòng nhập đúng 10 chữ số bắt đầu bằng 03, 05, 07, 08 hoặc 09.');
            document.getElementById('phoneInput').focus();
            return false;
        }

        const bookingDate = document.getElementById('bookingDateInput').value;
        const startTime = document.getElementById('startTimeSelect').value;

        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const todayStr = `${year}-${month}-${day}`;

        if (bookingDate === todayStr) {
            const [startHour, startMinute] = startTime.split(':').map(Number);
            const selectedTimeMinutes = startHour * 60 + startMinute;
            const currentTotalMinutes = now.getHours() * 60 + now.getMinutes();

            if ((selectedTimeMinutes - currentTotalMinutes) < 120) {
                alert('Nếu đặt sân trong ngày hôm nay, giờ bắt đầu phải cách thời điểm hiện tại ít nhất 2 tiếng!');
                document.getElementById('startTimeSelect').focus();
                return false;
            }
        }

        return true;
    }

    window.onload = function() {
        updatePrice();
    };
    </script>
</body>
</html>