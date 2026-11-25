<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ($password === $user['password']) {
                if ($user['role'] === 'admin') {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_name'] = $user['fullname'];
                    
                    header('Location: Admin.php');
                    exit;
                } else {
                    $error = 'Tài khoản này không có quyền Admin (role của bạn đang là: "' . $user['role'] . '")!';
                }
            } else {
                $error = 'Mật khẩu không chính xác!';
            }
        } else {
            $error = 'Không tìm thấy tài khoản với email này trong cơ sở dữ liệu!';
        }
    } catch (PDOException $e) {
        $error = 'Lỗi CSDL: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập Quản Trị - PitchBooking</title>
    <link rel="stylesheet" href="Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-card">
        <h2><i class="fa-solid fa-lock login-icon"></i> Đăng Nhập Admin</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Email Quản Trị</label>
                <input type="email" name="email" required placeholder="admin1@pitchbooking.vn">
            </div>
            <div class="form-group">
                <label>Mật Khẩu</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn-login">Đăng Nhập</button>
        </form>
    </div>
</body>
</html>