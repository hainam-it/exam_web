<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Kiểm tra mật khẩu khớp nhau
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Mật khẩu xác nhận không khớp!";
        header("Location: register.php");
        exit();
    }

    // 2. Kiểm tra tên đăng nhập đã tồn tại chưa
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Tên đăng nhập này đã được sử dụng!";
        header("Location: register.php");
        exit();
    }

    // 3. Thêm user mới (Role mặc định là 'student')
    try {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'student'; // CỐ ĐỊNH QUYỀN SINH VIÊN

        $sql = "INSERT INTO users (username, password, fullname, role) VALUES (?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql);
        $stmt_insert->execute([$username, $hashed_password, $fullname, $role]);

        $_SESSION['success'] = "Đăng ký thành công! Vui lòng đăng nhập.";
        header("Location: login.php");
        exit();

    } catch(PDOException $e) {
        $_SESSION['error'] = "Lỗi hệ thống: " . $e->getMessage();
        header("Location: register.php");
        exit();
    }
} else {
    header("Location: register.php");
}
?>