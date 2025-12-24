<?php
session_start();
require_once '../config/database.php';

$username = $_POST['username'];
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT * FROM users WHERE username = :u");
$stmt->execute(['u' => $username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['fullname'] = $user['fullname'];
    $_SESSION['username'] = $user['username'];
    
    // Điều hướng theo role
    if($user['role'] == 'admin') header("Location: ../admin/dashboard.php");
    elseif($user['role'] == 'teacher') header("Location: ../teacher/dashboard.php");
    else header("Location: ../student/dashboard.php");
} else {
    $_SESSION['error'] = "Sai thông tin đăng nhập!";
    header("Location: login.php");
}
?>