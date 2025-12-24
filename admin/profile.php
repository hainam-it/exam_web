<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Lấy thông tin chi tiết user
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$alertScript = "";

// XỬ LÝ ĐỔI MẬT KHẨU NGAY TẠI TRANG NÀY
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $old_pass = trim($_POST['old_pass']);
    $new_pass = trim($_POST['new_pass']);
    $confirm_pass = trim($_POST['confirm_pass']);

    if ($new_pass !== $confirm_pass) {
        $alertScript = "Swal.fire('Lỗi', 'Mật khẩu xác nhận không khớp!', 'error');";
    } else {
        if (password_verify($old_pass, $user['password'])) {
            $new_pass_hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($update->execute([$new_pass_hashed, $user_id])) {
                $alertScript = "Swal.fire('Thành công', 'Đổi mật khẩu thành công!', 'success');";
            } else {
                $alertScript = "Swal.fire('Lỗi', 'Lỗi hệ thống!', 'error');";
            }
        } else {
            $alertScript = "Swal.fire('Sai mật khẩu', 'Mật khẩu cũ không đúng!', 'error');";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Hồ sơ cá nhân</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link rel="stylesheet" href="/exam_web/assets/css/style.css">
    <link rel="stylesheet" href="/exam_web/assets/css/profile.css"> </head>
<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <h1 style="margin-bottom: 30px;">Hồ sơ cá nhân</h1>

            <div class="profile-grid">
                
                <div class="profile-card">
                    <div class="user-avatar-large">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                    <h2 style="text-align: center; color: #fff; margin-bottom: 5px;"><?php echo $user['fullname']; ?></h2>
                    <p style="text-align: center; color: #94a3b8; margin-bottom: 25px;">
                        <span class="status-badge status-active"><?php echo ucfirst($user['role']); ?></span>
                    </p>

                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label"><i class="fa-solid fa-user"></i> Username</span>
                            <span class="info-value"><?php echo $user['username']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label"><i class="fa-solid fa-envelope"></i> Email</span>
                            <span class="info-value"><?php echo $user['email'] ?? 'Chưa cập nhật'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label"><i class="fa-solid fa-calendar"></i> Ngày tạo</span>
                            <span class="info-value">
                            <?php echo !empty($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : 'Không xác định'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="profile-card">
                    <div class="card-title">
                        <i class="fa-solid fa-lock" style="color: #6366f1;"></i> Đổi mật khẩu
                    </div>
                    
                    <form method="POST">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="color: #cbd5e1; display: block; margin-bottom: 8px;">Mật khẩu hiện tại</label>
                            <input type="password" name="old_pass" class="form-control" style="background: #0f172a; border-color: #334155; color: #fff; padding: 12px; width: 100%; border-radius: 8px;" placeholder="••••••••" required>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div class="form-group">
                                <label style="color: #cbd5e1; display: block; margin-bottom: 8px;">Mật khẩu mới</label>
                                <input type="password" name="new_pass" class="form-control" style="background: #0f172a; border-color: #334155; color: #fff; padding: 12px; width: 100%; border-radius: 8px;" placeholder="••••••••" required>
                            </div>
                            <div class="form-group">
                                <label style="color: #cbd5e1; display: block; margin-bottom: 8px;">Xác nhận mật khẩu</label>
                                <input type="password" name="confirm_pass" class="form-control" style="background: #0f172a; border-color: #334155; color: #fff; padding: 12px; width: 100%; border-radius: 8px;" placeholder="••••••••" required>
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <button type="submit" class="btn btn-primary" style="padding: 12px 25px;">
                                <i class="fa-solid fa-floppy-disk"></i> Lưu thay đổi
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </main>
    </div>

    <script>
        <?php if($alertScript) echo $alertScript; ?>
    </script>
</body>
</html>