<?php
session_start();
require_once '../config/database.php';

$message_script = "";
$reset_link_debug = ""; // Biến này để hiện link lên màn hình (vì ko gửi mail được)

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    // 1. Kiểm tra email có tồn tại không
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // 2. Tạo Token ngẫu nhiên & Thời gian hết hạn (ví dụ 15 phút)
        $token = bin2hex(random_bytes(32)); // Tạo chuỗi ngẫu nhiên
        $expire = date("Y-m-d H:i:s", strtotime('+15 minutes'));

        // 3. Lưu Token vào DB
        $update = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expire = ? WHERE email = ?");
        if ($update->execute([$token, $expire, $email])) {
            
            // --- GIẢ LẬP GỬI EMAIL ---
            // Vì localhost không gửi mail được, ta tạo link và hiện luôn ra màn hình để bấm
            $link = "http://localhost/exam_web/auth/reset_password.php?email=".$email."&token=".$token;
            
            $message_script = "Swal.fire('Đã gửi yêu cầu!', 'Vui lòng kiểm tra Email (Link hiển thị bên dưới)', 'success');";
            $reset_link_debug = $link; // Lưu link để hiển thị
        } else {
            $message_script = "Swal.fire('Lỗi', 'Không thể tạo token!', 'error');";
        }
    } else {
        $message_script = "Swal.fire('Lỗi', 'Email này không tồn tại trong hệ thống!', 'error');";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quên mật khẩu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; background: #0f172a; flex-direction: column; }
        .auth-card { background: #1e293b; padding: 40px; border-radius: 16px; width: 100%; max-width: 400px; border: 1px solid #334155; }
        .form-control { width: 100%; padding: 12px; background: #334155; border: 1px solid #475569; border-radius: 8px; color: #fff; margin-bottom: 20px; }
        .btn-submit { width: 100%; padding: 12px; background: #6366f1; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        
        /* Style riêng cho cái link giả lập */
        .debug-link { margin-top: 20px; padding: 15px; background: #fefce8; border: 1px solid #facc15; color: #854d0e; border-radius: 8px; max-width: 600px; word-break: break-all; }
    </style>
</head>
<body>
    <div class="auth-card">
        <h2 style="color: #fff; text-align: center; margin-bottom: 20px;">Quên mật khẩu</h2>
        <p style="color: #94a3b8; text-align: center; margin-bottom: 20px; font-size: 0.9rem;">Nhập email của bạn để nhận link đặt lại mật khẩu.</p>
        
        <form method="POST">
            <input type="email" name="email" class="form-control" placeholder="Nhập Email đăng ký" required>
            <button type="submit" class="btn-submit">Gửi yêu cầu</button>
        </form>
        
        <div style="text-align: center; margin-top: 15px;">
            <a href="login.php" style="color: #6366f1; text-decoration: none;">Quay lại đăng nhập</a>
        </div>
    </div>

    <?php if($reset_link_debug): ?>
        <div class="debug-link">
            <strong>[MÔ PHỎNG EMAIL]</strong><br>
            Hệ thống đã gửi link xác nhận đến email của bạn<br>
            <a href="<?php echo $reset_link_debug; ?>"><strong>Bấm vào đây để đặt lại mật khẩu</strong></a>
        </div>
    <?php endif; ?>

    <script><?php echo $message_script; ?></script>
</body>
</html>