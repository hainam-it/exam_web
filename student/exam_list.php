<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Xử lý tìm kiếm
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM exams WHERE status = 'active'";
$params = [];

if ($search) {
    $sql .= " AND title LIKE ?";
    $params[] = "%$search%";
}

$sql .= " ORDER BY id DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$exams = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Danh sách đề thi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/exam_web/assets/css/style.css">
    <style>
        /* CSS cho thanh tìm kiếm */
        .search-box {
            display: flex;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 5px;
            width: 100%;
            max-width: 400px;
        }
        .search-input {
            background: transparent;
            border: none;
            color: #fff;
            padding: 10px 15px;
            width: 100%;
            outline: none;
        }
        .search-btn {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 0 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }
        .search-btn:hover { filter: brightness(1.1); }

        /* Nút Back */
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
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px;">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <a href="dashboard.php" class="btn-back" title="Quay lại Dashboard">
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 style="margin: 0;">Danh sách đề thi</h1>
                        <p style="color: var(--text-light); margin-top: 5px;">Chọn đề thi để bắt đầu làm bài.</p>
                    </div>
                </div>

                <form method="GET" class="search-box">
                    <input type="text" name="search" class="search-input" placeholder="Tìm kiếm đề thi..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="search-btn"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>

            <?php if(count($exams) > 0): ?>
                <div class="grid-3">
                    <?php foreach($exams as $ex): ?>
                        <div class="card" style="display: flex; flex-direction: column; height: 100%;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                                <div style="width: 50px; height: 50px; background: rgba(99, 102, 241, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.5rem; border: 1px solid rgba(99, 102, 241, 0.2);">
                                    <i class="fa-solid fa-file-code"></i>
                                </div>
                                <span class="status-badge status-active">Đang mở</span>
                            </div>
                            
                            <h3 style="font-size: 1.2rem; margin-bottom: 10px; flex-grow: 1;"><?php echo $ex['title']; ?></h3>
                            
                            <div style="color: var(--text-light); font-size: 0.9rem; margin-bottom: 20px; min-height: 45px;">
                                <?php echo $ex['description'] ? substr($ex['description'], 0, 80) . '...' : 'Không có mô tả.'; ?>
                            </div>
                            
                            <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px; margin-top: auto;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; font-size: 0.9rem; color: #cbd5e1;">
                                    <span><i class="fa-regular fa-clock" style="color: var(--secondary);"></i> <?php echo $ex['duration']; ?> phút</span>
                                    
                                    <?php 
                                        $q_count = $conn->query("SELECT COUNT(*) FROM questions WHERE exam_id = " . $ex['id'])->fetchColumn(); 
                                    ?>
                                    <span><i class="fa-solid fa-list-ul" style="color: var(--accent);"></i> <?php echo $q_count; ?> câu</span>
                                </div>

                                <a href="do_exam.php?exam_id=<?php echo $ex['id']; ?>" class="btn btn-primary" style="width: 100%; justify-content: center;">
                                    BẮT ĐẦU THI <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card" style="text-align: center; padding: 50px;">
                    <i class="fa-solid fa-box-open" style="font-size: 4rem; color: var(--text-light); margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3 style="color: var(--text-light);">Không tìm thấy đề thi nào!</h3>
                    <p style="color: #64748b;">Vui lòng quay lại sau hoặc liên hệ giáo viên.</p>
                </div>
            <?php endif; ?>

        </main>
    </div>
</body>
</html>