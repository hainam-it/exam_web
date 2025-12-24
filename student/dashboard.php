<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Lấy danh sách đề thi đang mở
$stmt = $conn->prepare("SELECT * FROM exams WHERE status = 'active'");
$stmt->execute();
$exams = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Dashboard Sinh viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/exam_web/assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div style="margin-bottom: 2rem;">
                <h1 style="margin: 0; font-size: 1.8rem;">Chào, <?php echo $_SESSION['fullname']; ?>! 👋</h1>
                <p style="color: var(--text-light); margin-top: 5px;">Chúc bạn một ngày học tập hiệu quả.</p>
            </div>

            <div class="grid-3" style="margin-bottom: 2rem;">
                <div class="card" style="border-left: 4px solid var(--primary); display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <p style="color: var(--text-light); font-size: 0.9rem;">Đề thi khả dụng</p>
                        <h2 style="margin: 0; color: #fff;"><?php echo count($exams); ?></h2>
                    </div>
                    <i class="fa-solid fa-book-open" style="font-size: 2rem; color: var(--primary); opacity: 0.5;"></i>
                </div>
            </div>

            <h3 style="margin-bottom: 1.5rem; font-weight: 700;">Đề thi nổi bật</h3>
            
            <?php if(count($exams) > 0): ?>
                <div class="grid-3">
                    <?php foreach($exams as $ex): ?>
                        <div class="card">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                                <div style="width: 50px; height: 50px; background: rgba(255,255,255,0.05); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.5rem;">
                                    <i class="fa-solid fa-code"></i>
                                </div>
                                <span class="status-badge status-active">Đang mở</span>
                            </div>
                            
                            <h3 style="font-size: 1.1rem; margin-bottom: 10px;"><?php echo $ex['title']; ?></h3>
                            <p style="font-size: 0.9rem; margin-bottom: 20px; line-height: 1.5; color: var(--text-light);">
                                <?php echo $ex['description']; ?>
                            </p>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
                                <span style="font-size: 0.9rem; color: var(--text-light);"><i class="fa-regular fa-clock"></i> <?php echo $ex['duration']; ?> phút</span>
                                <a href="do_exam.php?exam_id=<?php echo $ex['id']; ?>" class="btn btn-primary">
                                    VÀO THI <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card">
                    <p>Hiện tại chưa có đề thi nào được mở.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>