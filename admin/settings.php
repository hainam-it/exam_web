<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';
if ($_SESSION['role'] !== 'admin') exit();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST as $key => $value) {
        $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE key_name = ?");
        $stmt->execute([$value, $key]);
    }
    echo "<script>alert('Đã lưu cấu hình!');</script>";
}

// Lấy settings hiện tại
$settings = [];
$rows = $conn->query("SELECT * FROM settings")->fetchAll();
foreach($rows as $r) $settings[$r['key_name']] = $r['value'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Cài đặt hệ thống</title>
    <link rel="stylesheet" href="/exam_web/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <h1>Cấu hình hệ thống</h1>
            <div class="card" style="max-width: 600px;">
                <form method="POST">
                    <div class="form-group">
                        <label>Tên trường / Tên Website</label>
                        <input type="text" name="school_name" class="form-control" value="<?php echo $settings['school_name'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Thông tin Footer</label>
                        <input type="text" name="footer_info" class="form-control" value="<?php echo $settings['footer_info'] ?? ''; ?>">
                    </div>
                    <button class="btn btn-primary">Lưu thay đổi</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>