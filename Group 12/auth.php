<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';

// Xác định xem người dùng đang muốn ở form nào (login hoặc register)
// Mặc định nếu không truyền gì thì hiển thị form đăng nhập
$tab = isset($_GET['tab']) && $_GET['tab'] === 'register' ? 'register' : 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- XỬ LÝ ĐĂNG NHẬP ---
    if ($action === 'login') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $password === $user['password']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['fullname'];
                $_SESSION['user_email'] = $user['email'];
                
                header('Location: Index.php');
                exit;
            } else {
                $error = 'Email hoặc mật khẩu không chính xác!';
                $tab = 'login';
            }
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
            $tab = 'login';
        }
    } 
    // --- XỬ LÝ ĐĂNG KÝ ---
    elseif ($action === 'register') {
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $tab = 'register'; // Giữ lại tab đăng ký nếu có lỗi

        if (empty($fullname) || empty($email) || empty($password)) {
            $error = 'Vui lòng điền đầy đủ tất cả các trường!';
        } elseif ($password !== $confirm_password) {
            $error = 'Mật khẩu nhập lại không khớp!';
        } else {
            try {
                $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmtCheck->execute([$email]);
                
                if ($stmtCheck->rowCount() > 0) {
                    $error = 'Email này đã được đăng ký cho tài khoản khác!';
                } else {
                    $stmtInsert = $pdo->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
                    $stmtInsert->execute([$fullname, $email, $password]);

                    $success = 'Đăng ký tài khoản thành công! Bạn có thể chuyển sang đăng nhập.';
                    // Sau khi đăng ký thành công có thể chuyển hướng về tab login
                    $tab = 'login'; 
                }
            } catch (PDOException $e) {
                $error = 'Lỗi hệ thống: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác Thực Tài Khoản - PitchBooking</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .auth-forms-container { display: none; }
        .auth-forms-container.active { display: block; }
    </style>
</head>
<body class="login-body">
    <div class="login-card">
        
        <!-- Hiển thị thông báo lỗi chung -->
        <?php if (!empty($error)): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Hiển thị thông báo thành công -->
        <?php if (!empty($success)): ?>
            <div style="background: #ecfdf5; color: #059669; padding: 10px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; text-align: center; font-weight: 600;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- ================= FORM ĐĂNG NHẬP ================= -->
        <div id="login-form-box" class="auth-forms-container <?php echo $tab === 'login' ? 'active' : ''; ?>">
            <h2><i class="fa-solid fa-user-circle" style="color: #10b981;"></i> Đăng Nhập</h2>
            <form action="auth.php" method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label>Email của bạn</label>
                    <input type="email" name="email" required placeholder="nguyenvanA@gmail.com">
                </div>
                <div class="form-group">
                    <label>Mật Khẩu</label>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn-login">Đăng Nhập</button>
            </form>
            <div class="register-link">
                Chưa có tài khoản? <a href="#" onclick="switchTab('register'); return false;">Đăng ký ngay</a>
            </div>
        </div>

        <!-- ================= FORM ĐĂNG KÝ ================= -->
        <div id="register-form-box" class="auth-forms-container <?php echo $tab === 'register' ? 'active' : ''; ?>">
            <h2><i class="fa-solid fa-user-plus" style="color: #10b981;"></i> Đăng Ký</h2>
            <form action="auth.php?tab=register" method="POST">
                <input type="hidden" name="action" value="register">
                <div class="form-group">
                    <label>Họ và Tên</label>
                    <input type="text" name="fullname" required placeholder="Nguyễn Văn A">
                </div>
                <div class="form-group">
                    <label>Email của bạn</label>
                    <input type="email" name="email" required placeholder="nguyenvanA@gmail.com">
                </div>
                <div class="form-group">
                    <label>Mật Khẩu</label>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label>Nhập Lại Mật Khẩu</label>
                    <input type="password" name="confirm_password" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn-login">Đăng Ký</button>
            </form>
            <div class="register-link">
                Đã có tài khoản? <a href="#" onclick="switchTab('login'); return false;">Đăng nhập ngay</a>
            </div>
        </div>

    </div>

    <script>
        // Hàm chuyển đổi qua lại giữa form Đăng nhập và Đăng ký ngay trên cùng một trang
        function switchTab(targetTab) {
            const loginBox = document.getElementById('login-form-box');
            const registerBox = document.getElementById('register-form-box');

            if (targetTab === 'register') {
                loginBox.classList.remove('active');
                registerBox.classList.add('active');
            } else {
                registerBox.classList.remove('active');
                loginBox.classList.add('active');
            }
        }
    </script>
</body>
</html>