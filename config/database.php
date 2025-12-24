<?php
$host = 'localhost';
$dbname = 'exam_web';
$username = 'root';
$password = ''; // Mặc định XAMPP là rỗng

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Kết nối thất bại: " . $e->getMessage());
}
?>