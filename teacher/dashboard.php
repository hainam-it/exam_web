<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Check quyền Teacher
if ($_SESSION['role'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit();
}

// Thống kê dữ liệu
$my_questions = $conn->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$recent_results = $conn->query("SELECT COUNT(*) FROM results")->fetchColumn();


// 3. Top 5 sinh viên điểm cao nhất
$top_students = $conn->query("
    SELECT u.fullname, e.title, r.score 
    FROM results r 
    JOIN users u ON r.user_id = u.id 
    JOIN exams e ON r.exam_id = e.id 
    ORDER BY r.score DESC LIMIT 5
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/exam_web/assets/css/style.css">
    <style>
        .glass-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
        }
        
        /* Table Style */
        .leaderboard-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        .leaderboard-table td { padding: 12px; background: rgba(255,255,255,0.03); }
        .leaderboard-table tr:hover td { background: rgba(255,255,255,0.08); }
        .leaderboard-table td:first-child { border-top-left-radius: 8px; border-bottom-left-radius: 8px; }
        .leaderboard-table td:last-child { border-top-right-radius: 8px; border-bottom-right-radius: 8px; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <h1 style="margin-bottom: 0.5rem;">Khu vực Giáo viên</h1>
            <p style="color: var(--text-light); margin-bottom: 2rem;">Quản lý nội dung thi và theo dõi kết quả.</p>

            <div class="grid-3">
                <div class="card" style="grid-column: span 2; background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(139, 92, 246, 0.2)); border: 1px solid rgba(99, 102, 241, 0.3);">
                    <h2 style="margin-bottom: 10px;">Xin chào, <?php echo $_SESSION['fullname']; ?></h2>
                    <p style="margin-bottom: 20px; color: #e2e8f0;">Hệ thống đã sẵn sàng. Bạn muốn làm gì hôm nay?</p>
                    <div style="display: flex; gap: 15px;">
                        <a href="questions.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Soạn câu hỏi</a>
                        <a href="results.php" class="btn" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2);"><i class="fa-solid fa-list"></i> Xem bảng điểm</a>
                    </div>
                </div>

                <div class="card">
                    <h3 style="margin-bottom: 20px; font-size: 1.2rem;">Thống kê nhanh</h3>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <span style="color: var(--text-light);">Câu hỏi đã tạo</span>
                        <span style="font-weight: bold; font-size: 1.2rem;"><?php echo $my_questions; ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-light);">Lượt thi đã chấm</span>
                        <span style="font-weight: bold; font-size: 1.2rem;"><?php echo $recent_results; ?></span>
                    </div>
                </div>
            </div>

            <h3 style="margin-top: 2rem; margin-bottom: 1.5rem; font-weight: 700;">Quy trình tổ chức thi</h3>
            <div class="grid-3">
                <div class="card">
                    <div style="font-weight: 700; margin-bottom: 10px; color: var(--primary); font-size: 1.1rem;">Bước 1</div>
                    <p style="font-size: 0.9rem;">Liên hệ Admin để khởi tạo đề thi (Tên đề, thời gian làm bài).</p>
                </div>
                <div class="card">
                    <div style="font-weight: 700; margin-bottom: 10px; color: var(--primary); font-size: 1.1rem;">Bước 2</div>
                    <p style="font-size: 0.9rem;">Vào mục <b>Ngân hàng câu hỏi</b>, chọn đề thi và nhập câu hỏi trắc nghiệm.</p>
                </div>
                <div class="card">
                    <div style="font-weight: 700; margin-bottom: 10px; color: var(--primary); font-size: 1.1rem;">Bước 3</div>
                    <p style="font-size: 0.9rem;">Sinh viên thi xong, xem điểm tại mục <b>Chấm điểm</b>.</p>
                </div>
            </div>
            <br>
            <div class="glass-card">
                <table class="leaderboard-table">
                    <?php foreach($top_students as $idx => $s): ?>
                    <tr>
                        <td style="text-align: center; width: 50px;">
                            <?php 
                                if($idx==0) echo '🥇'; 
                                elseif($idx==1) echo '🥈'; 
                                elseif($idx==2) echo '🥉'; 
                                else echo '<span style="color:#64748b; font-weight:bold">'.($idx+1).'</span>'; 
                            ?>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #fff;"><?php echo $s['fullname']; ?></div>
                            <div style="font-size: 0.85rem; color: #94a3b8;">Đề: <?php echo $s['title']; ?></div>
                        </td>
                        <td style="text-align: right; width: 100px;">
                            <span style="font-weight: 800; font-size: 1.1rem; color: #34d399;"><?php echo $s['score']; ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </main>
    </div>
</body>
</html>