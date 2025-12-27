<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Lấy danh sách kết quả, join 3 bảng để lấy tên Sinh viên và tên Đề thi
$sql = "SELECT r.*, u.fullname, u.username, e.title as exam_title 
        FROM results r
        JOIN users u ON r.user_id = u.id
        JOIN exams e ON r.exam_id = e.id
        ORDER BY r.submitted_at DESC";
$results = $conn->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Xem kết quả thi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/exam_web/assets/css/style.css">
    <style>
        /* Nút Back Style (Giống bên Questions) */
        .btn-back {
            background: rgba(255,255,255,0.05);
            width: 45px; height: 45px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            transition: 0.3s;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .btn-back:hover {
            background: var(--primary);
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.4);
            transform: translateX(-3px);
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin: 0;"><i class="fa-solid fa-trophy" style="color: #facc15;"></i> Bảng vàng thành tích</h3>
                    <?php if(file_exists('export_results.php')): ?>
                        <a href="export_results.php" class="btn" style="background: rgba(16, 185, 129, 0.2); color: #34d399; font-size: 0.85rem; border: 1px solid rgba(16, 185, 129, 0.3);">
                            <i class="fa-solid fa-download"></i> Xuất Excel
                        </a>
                    <?php endif; ?>
            </div>
            <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px;">
                <a href="dashboard.php" class="btn-back" title="Quay lại Dashboard">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <h1 style="margin: 0;">Bảng điểm sinh viên</h1>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>MSSV</th>
                            <th>Họ và Tên</th>
                            <th>Đề thi</th>
                            <th>Điểm số</th>
                            <th>Thời gian nộp</th>
                            <th>Đánh giá</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($results) > 0): ?>
                            <?php foreach($results as $row): ?>
                            <tr>
                                <td><?php echo $row['username']; ?></td>
                                <td style="font-weight: 500; color: #fff;"><?php echo $row['fullname']; ?></td>
                                <td><span class="status-badge status-active"><?php echo $row['exam_title']; ?></span></td>
                                <td>
                                    <span style="font-weight: bold; font-size: 1.1em; color: <?php echo ($row['score'] >= 5) ? '#34d399' : '#f43f5e'; ?>">
                                        <?php echo $row['score']; ?>
                                    </span>
                                </td>
                                <td style="color: var(--text-light);"><?php echo date("d/m/Y H:i", strtotime($row['submitted_at'])); ?></td>
                                <td>
                                    <?php if($row['score'] >= 8): ?>
                                        <span class="status-badge" style="background: rgba(16, 185, 129, 0.2); color: #34d399;">Giỏi</span>
                                    <?php elseif($row['score'] >= 5): ?>
                                        <span class="status-badge" style="background: rgba(251, 191, 36, 0.2); color: #fbbf24;">Đạt</span>
                                    <?php else: ?>
                                        <span class="status-badge" style="background: rgba(244, 63, 94, 0.2); color: #f43f5e;">Trượt</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center;">Chưa có dữ liệu thi.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>
</body>
</html>