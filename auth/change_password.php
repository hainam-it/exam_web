<?php
session_start();
require_once '../config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message_script = "";

// Xử lý khi nhấn nút Đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $old_pass = trim($_POST['old_pass']);
    $new_pass = trim($_POST['new_pass']);
    $confirm_pass = trim($_POST['confirm_pass']);
    $user_id = $_SESSION['user_id'];

    if ($new_pass !== $confirm_pass) {
        $message_script = "Swal.fire('Lỗi', 'Mật khẩu xác nhận không khớp!', 'error');";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($old_pass, $user['password'])) {
            $new_pass_hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($update->execute([$new_pass_hashed, $user_id])) {
                $message_script = "Swal.fire('Thành công', 'Đổi mật khẩu thành công!', 'success');";
            } else {
                $message_script = "Swal.fire('Lỗi', 'Không thể cập nhật CSDL!', 'error');";
            }
        } else {
            $message_script = "Swal.fire('Sai mật khẩu', 'Mật khẩu cũ không chính xác!', 'error');";
        }
    }
}

// Xác định đường dẫn nút "Quay lại"
$back_url = "../index.php";
if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin': $back_url = "../admin/dashboard.php"; break;
        case 'teacher': $back_url = "../teacher/dashboard.php"; break;
        case 'student': $back_url = "../student/dashboard.php"; break;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đổi mật khẩu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/change_password.css">
</head>
<body>

    <div class="auth-card">
        <h2 class="auth-title"><i class="fa-solid fa-lock"></i> Đổi mật khẩu</h2>
        
        <form method="POST">
            <div class="form-group">
                <label>Mật khẩu cũ:</label>
                <input type="password" name="old_pass" class="form-control" required placeholder="Nhập mật khẩu hiện tại">
            </div>
            
            <div class="form-group">
                <label>Mật khẩu mới:</label>
                <input type="password" name="new_pass" class="form-control" required placeholder="Nhập mật khẩu mới">
            </div>

            <div class="form-group">
                <label>Nhập lại mật khẩu mới:</label>
                <input type="password" name="confirm_pass" class="form-control" required placeholder="Xác nhận mật khẩu mới">
            </div>

            <button type="submit" class="btn-submit">Lưu thay đổi</button>
        </form>

        <a href="<?php echo $back_url; ?>" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Quay lại Dashboard
        </a>
    </div>

    <script>
        <?php if($message_script) echo $message_script; ?>
    </script>

</body>
</html>