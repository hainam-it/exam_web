<?php
session_start();
require_once '../config/database.php';

$message_script = "";
$show_form = false;
$error_msg = "";

// 1. Kiểm tra Token trên URL
if (isset($_GET['token']) && isset($_GET['email'])) {
    $token = $_GET['token'];
    $email = $_GET['email'];
    $current_time = date("Y-m-d H:i:s");

    // Check DB xem token có khớp và còn hạn không
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND reset_token = ? AND reset_token_expire > ?");
    $stmt->execute([$email, $token, $current_time]);
    $user = $stmt->fetch();

    if ($user) {
        $show_form = true; // Token đúng -> Hiện form đổi pass
    } else {
        $error_msg = "Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn!";
    }
} else {
    $error_msg = "Đường dẫn không hợp lệ!";
}

// 2. Xử lý khi bấm nút Đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $show_form) {
    $new_pass = $_POST['new_pass'];
    $confirm_pass = $_POST['confirm_pass'];
    
    if ($new_pass === $confirm_pass) {
        // Mã hóa mật khẩu
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
        
        // Update mật khẩu mới và XÓA token để không dùng lại được nữa
        $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expire = NULL WHERE email = ?");
        if ($update->execute([$hashed_pass, $email])) {
            $message_script = "Swal.fire('Thành công', 'Mật khẩu đã được thay đổi!', 'success').then(() => { window.location.href = 'login.php'; });";
        } else {
            $message_script = "Swal.fire('Lỗi', 'Không thể cập nhật DB', 'error');";
        }
    } else {
        $message_script = "Swal.fire('Lỗi', 'Mật khẩu xác nhận không khớp', 'error');";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đặt lại mật khẩu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; background: #0f172a; color: #fff; font-family: sans-serif; }
        .auth-card { background: #1e293b; padding: 40px; border-radius: 16px; width: 100%; max-width: 400px; border: 1px solid #334155; }
        
        /* Cập nhật CSS input để không bị lệch */
        .form-control { 
            width: 100%; 
            padding: 12px; 
            background: #334155; 
            border: 1px solid #475569; 
            border-radius: 8px; 
            color: #fff; 
            margin-bottom: 20px; 
            box-sizing: border-box; /* Quan trọng: để padding không làm vỡ khung */
        }
        
        .btn-submit { width: 100%; padding: 12px; background: #6366f1; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background: #4f46e5; }
        .error-box { background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; text-align: center; }
        
        /* Style cho nút quay lại */
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.9rem;
            transition: 0.3s;
        }
        .back-link:hover { color: #fff; }
    </style>
</head>
<body>

    <div class="auth-card">
        <h2 style="text-align: center; margin-bottom: 20px;">Đặt lại mật khẩu mới</h2>

        <?php if ($show_form): ?>
            <form method="POST">
                <label style="display:block; margin-bottom:5px; color:#cbd5e1;">Mật khẩu mới:</label>
                <input type="password" name="new_pass" class="form-control" required placeholder="Nhập mật khẩu mới">
                
                <label style="display:block; margin-bottom:5px; color:#cbd5e1;">Xác nhận mật khẩu:</label>
                <input type="password" name="confirm_pass" class="form-control" required placeholder="Nhập lại mật khẩu">
                
                <button type="submit" class="btn-submit">Lưu mật khẩu</button>
            </form>

            <a href="login.php" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> Quay lại đăng nhập
            </a>

        <?php else: ?>
            <div class="error-box">
                <?php echo $error_msg; ?>
                <br><br>
                <a href="forgot_password.php" style="color: #b91c1c; font-weight: bold;">Thử lại</a>
                <br><br>
                <a href="login.php" style="color: #b91c1c; text-decoration: underline; font-size: 0.9rem;">Về trang đăng nhập</a>
            </div>
        <?php endif; ?>
    </div>

    <script><?php echo $message_script; ?></script>
</body>
</html>