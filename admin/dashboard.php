<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// 1. Thống kê số lượng (Vẫn giữ để vẽ biểu đồ)
$stats = [
    'users'    => $conn->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn(),
    'exams'    => $conn->query("SELECT COUNT(*) FROM exams")->fetchColumn(),
    'questions'=> $conn->query("SELECT COUNT(*) FROM questions")->fetchColumn(),
    'results'  => $conn->query("SELECT COUNT(*) FROM results")->fetchColumn()
];

// 2. Thống kê Đậu/Rớt
$pass = $conn->query("SELECT COUNT(*) FROM results WHERE score >= 5")->fetchColumn();
$fail = $conn->query("SELECT COUNT(*) FROM results WHERE score < 5")->fetchColumn();

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
    <title>Admin Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/exam_web/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <h1 style="margin-bottom: 25px;">Tổng quan hệ thống</h1>

            <div class="grid-2" style="gap: 20px; margin-bottom: 25px;">
                
                <div class="glass-card">
                    <h3 style="margin-bottom: 15px; font-size: 1.1rem; color: #e2e8f0;">
                        <i class="fa-solid fa-chart-column"></i> Thống kê dữ liệu
                    </h3>
                    <div style="height: 250px;">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>

                <div class="glass-card">
                    <h3 style="margin-bottom: 15px; font-size: 1.1rem; color: #e2e8f0;">
                        <i class="fa-solid fa-chart-pie"></i> Tỉ lệ chất lượng
                    </h3>
                    <div style="height: 250px; display: flex; justify-content: center;">
                        <canvas id="pieChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="glass-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin: 0;"><i class="fa-solid fa-trophy" style="color: #facc15;"></i> Bảng vàng thành tích</h3>
                    <?php if(file_exists('export_results.php')): ?>
                        <a href="export_results.php" class="btn" style="background: rgba(16, 185, 129, 0.2); color: #34d399; font-size: 0.85rem; border: 1px solid rgba(16, 185, 129, 0.3);">
                            <i class="fa-solid fa-download"></i> Xuất Excel
                        </a>
                    <?php endif; ?>
                </div>
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

    <script>
        // Cấu hình chung cho Chart.js
        Chart.defaults.color = '#94a3b8';
        Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.05)';

        // 1. VẼ BIỂU ĐỒ CỘT (BAR CHART)
        new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: {
                labels: ['Sinh viên', 'Đề thi', 'Câu hỏi', 'Lượt thi'],
                datasets: [{
                    label: 'Số lượng',
                    data: [<?php echo $stats['users']; ?>, <?php echo $stats['exams']; ?>, <?php echo $stats['questions']; ?>, <?php echo $stats['results']; ?>],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.7)',
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(236, 72, 153, 0.7)'
                    ],
                    borderRadius: 6,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255, 255, 255, 0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // 2. VẼ BIỂU ĐỒ TRÒN (DOUGHNUT CHART)
        new Chart(document.getElementById('pieChart'), {
            type: 'doughnut',
            data: {
                labels: ['Đạt', 'Trượt'],
                datasets: [{
                    data: [<?php echo $pass; ?>, <?php echo $fail; ?>],
                    backgroundColor: ['#10b981', '#f43f5e'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
                },
                cutout: '70%'
            }
        });
    </script>
</body>
</html>