<?php
session_start();

// Xử lý đăng xuất ngay trên trang chủ
if (isset($_GET['action']) &&$_GET['action'] === 'logout') {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_email']);
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PitchBooking - Đặt Sân Bóng Đá Cao Cấp</title>
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
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="user-welcome">
                        <i class="fa-solid fa-user-check" style="color: var(--primary);"></i> 
                        Xin chào, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                    <a href="index.php?action=logout" class="btn-logout-small">Đăng xuất</a>
                <?php else: ?>
                    <a href="auth.php?tab=login" class="auth-btn btn-login-outline">Đăng Nhập</a>
                    <a href="auth.php?tab=register" class="auth-btn btn-register-fill">Đăng Ký</a>
                <?php endif; ?>
                <a href="History.php" class="btn-history" style="background: #0284c7; color: white; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 600;">
                    <i class="fa-solid fa-history"></i> Lịch Sử Đặt Sân
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Hero Section -->
        <div class="hero-wrapper">
            <div class="hero">
                <h1>Tìm & Đặt Sân Bóng Đá Dễ Dàng</h1>
                <p>Hãy đến với chúng tôi để trải nghiệm dịch vụ đặt sân bóng đá tốt nhất!</p>
                
                <!-- Thay đổi form thành bắt sự kiện JavaScript, không dùng action sang file khác -->
                <form id="searchForm" class="search-box" onsubmit="event.preventDefault(); filterPitches();">
                    <input type="text" id="searchInput" placeholder="Nhập tên sân, khu vực hoặc quận bạn muốn tìm (VD: Tân Bình)...">
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-search"></i> Tìm sân</button>
                </form>
            </div>
        </div>

        <h2 class="section-title" id="sectionTitle"><i class="fa-solid fa-fire text-success" style="color: var(--primary);"></i> Sân Bóng Nổi Bật</h2>
        <div class="grid-cards">
            <!-- Dữ liệu sân sẽ được load động qua JavaScript -->
        </div>
    </div>

    <footer class="user-footer">
        <div class="footer-container">
            <div>
                <h3>Về PitchBooking</h3>
                <p style="color: #94a3b8; margin-top: 0.75rem; font-size: 0.95rem;">Hệ thống thông minh kết nối người chơi và chủ sân bóng đá chuyên nghiệp toàn quốc.</p>
            </div>
            <div>
                <h3>Tổng đài hỗ trợ</h3>
                <p style="color: #94a3b8; margin-top: 0.75rem; font-size: 0.95rem;">Hotline: 1900 6868 (8:00 - 21:00)</p>
                <p style="color: #94a3b8; font-size: 0.95rem;">Email: support@pitchbooking.vn</p>
            </div>
        </div>
        <div class="footer-bottom">© 2026 PitchBooking Platform. All rights reserved.</div>
    </footer>

<script>
let allPitches = []; // Biến lưu trữ toàn bộ danh sách sân tải về từ server

document.addEventListener("DOMContentLoaded", function() {
    // Tải toàn bộ dữ liệu sân 1 lần duy nhất khi vào trang
    fetch('get_pitches.php')
        .then(response => response.json())
        .then(data => {
            allPitches = data;
            displayPitches(allPitches); // Hiển thị toàn bộ ban đầu
        })
        .catch(error => console.error('Lỗi tải dữ liệu sân:', error));
});

// Hàm hiển thị danh sách sân ra màn hình
function displayPitches(pitches) {
    const gridCards = document.querySelector('.grid-cards');
    gridCards.innerHTML = ''; 

    if (pitches.length === 0) {
        gridCards.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #64748b; font-style: italic;">Không tìm thấy sân bóng nào phù hợp.</p>';
        return;
    }

    pitches.forEach(pitch => {
        const cardHTML = `
            <div class="card">
                <div class="card-img-wrap">
                    <img src="${pitch.image_url}" alt="Sân bóng">
                    <span class="card-badge">${pitch.category}</span>
                </div>
                <div class="card-body">
                    <div class="card-title">${pitch.name}</div>
                    <div class="card-location"><i class="fa-solid fa-location-dot"></i> ${pitch.location}</div>
                    
                    <form action="Booking.php" method="POST" class="card-footer-info">
                        <input type="hidden" name="pitch_id" value="${pitch.id}">
                        <input type="hidden" name="total_price" value="${pitch.price}">
                        <span class="price">
                            ${Number(pitch.price).toLocaleString('vi-VN')}đ 
                            <sub style="font-size:0.75rem; color:var(--text-muted); font-weight:normal;">/ trận</sub>
                        </span>
                        <button type="submit" class="btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Đặt ngay</button>
                    </form>
                </div>
            </div>
        `;
        gridCards.insertAdjacentHTML('beforeend', cardHTML);
    });
}

// Hàm lọc sân ngay lập tức trên giao diện khi người dùng bấm "Tìm sân"
function filterPitches() {
    const keyword = document.getElementById('searchInput').value.toLowerCase().trim();
    const sectionTitle = document.getElementById('sectionTitle');

    if (keyword === "") {
        sectionTitle.innerHTML = `<i class="fa-solid fa-fire text-success" style="color: var(--primary);"></i> Sân Bóng Nổi Bật`;
        displayPitches(allPitches);
        return;
    }

    // Lọc theo tên sân hoặc vị trí/khu vực (quận)
    const filtered = allPitches.filter(pitch => {
        const nameMatch = pitch.name.toLowerCase().includes(keyword);
        const locationMatch = pitch.location.toLowerCase().includes(keyword);
        return nameMatch || locationMatch;
    });

    sectionTitle.innerHTML = `<i class="fa-solid fa-search" style="color: var(--primary);"></i> Kết quả tìm kiếm cho: "${keyword}"`;
    displayPitches(filtered);
}
</script>
</body>
</html>