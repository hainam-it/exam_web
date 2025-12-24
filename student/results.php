<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$is_grading = false; // Biến cờ để kiểm tra xem đang chấm điểm hay xem lịch sử

// --- TRƯỜNG HỢP 1: NỘP BÀI & CHẤM ĐIỂM (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['exam_id'])) {
    $is_grading = true;
    $exam_id = $_POST['exam_id'];
    $user_answers = $_POST['question'] ?? [];

    // 1. Lấy thông tin đề thi & Câu hỏi
    $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch();

    $questions = $conn->prepare("SELECT * FROM questions WHERE exam_id = ?");
    $questions->execute([$exam_id]);
    $questions_data = $questions->fetchAll();

    // 2. Tính điểm
    $total_questions = count($questions_data);
    $correct_count = 0;
    $detailed_result = [];

    foreach ($questions_data as $q) {
        // Lấy đáp án đúng
        $stmt_correct = $conn->prepare("SELECT id FROM answers WHERE question_id = ? AND is_correct = 1");
        $stmt_correct->execute([$q['id']]);
        $correct_ans = $stmt_correct->fetch();
        $correct_id = $correct_ans['id'] ?? null;
        
        $selected = $user_answers[$q['id']] ?? null;
        $is_correct = ($selected == $correct_id);
        
        if ($is_correct) $correct_count++;

        $detailed_result[] = [
            'question' => $q,
            'selected' => $selected,
            'correct_id' => $correct_id,
            'is_correct' => $is_correct
        ];
    }

    $score = ($total_questions > 0) ? round(($correct_count / $total_questions) * 10, 2) : 0;

    // 3. Lưu kết quả
    try {
        $stmt_save = $conn->prepare("INSERT INTO results (user_id, exam_id, score, submitted_at) VALUES (?, ?, ?, NOW())");
        $stmt_save->execute([$user_id, $exam_id, $score]);
    } catch (Exception $e) { }
} 

// --- TRƯỜNG HỢP 2: XEM LỊCH SỬ THI (GET) ---
else {
    $stmt_history = $conn->prepare("
        SELECT r.*, e.title as exam_title 
        FROM results r
        JOIN exams e ON r.exam_id = e.id
        WHERE r.user_id = ?
        ORDER BY r.submitted_at DESC
    ");
    $stmt_history->execute([$user_id]);
    $history = $stmt_history->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <title><?php echo $is_grading ? 'Kết quả bài thi' : 'Lịch sử thi'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/exam_web/assets/css/style.css">
    <style>
        /* CSS Vòng tròn điểm */
        .score-circle {
            width: 120px; height: 120px; border-radius: 50%; border: 6px solid;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            margin: 0 auto 15px; font-size: 2.5rem; font-weight: 800;
        }
        .pass { border-color: #34d399; color: #34d399; box-shadow: 0 0 20px rgba(52, 211, 153, 0.2); }
        .fail { border-color: #f43f5e; color: #f43f5e; box-shadow: 0 0 20px rgba(244, 63, 94, 0.2); }
        
        /* CSS Review */
        .review-item { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 15px; margin-bottom: 15px; border-left: 4px solid transparent; }
        .review-item.correct { border-left-color: #34d399; }
        .review-item.wrong { border-left-color: #f43f5e; }
        
        .opt { padding: 8px 12px; margin-top: 5px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); font-size: 0.9rem; display: flex; justify-content: space-between; color: var(--text-light); }
        .opt-correct { background: rgba(16, 185, 129, 0.15) !important; border-color: #34d399 !important; color: #fff !important; }
        .opt-wrong { background: rgba(244, 63, 94, 0.15); border-color: #f43f5e; color: #fff; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            
            <?php if ($is_grading): ?>
                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px;">
                    <a href="exam_list.php" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
                    <h1 style="margin: 0;">Kết quả: <?php echo $exam['title']; ?></h1>
                </div>

                <div class="card" style="text-align: center; margin-bottom: 30px;">
                    <div class="score-circle <?php echo ($score >= 5) ? 'pass' : 'fail'; ?>">
                        <?php echo $score; ?>
                    </div>
                    <h2><?php echo ($score >= 5) ? 'Chúc mừng! Bạn đã đạt.' : 'Rất tiếc, chưa đạt yêu cầu.'; ?></h2>
                    <p style="color: var(--text-light);">Đúng <?php echo $correct_count; ?>/<?php echo $total_questions; ?> câu</p>
                </div>

                <h3 style="margin-bottom: 20px;">Chi tiết bài làm</h3>
                <?php foreach($detailed_result as $idx => $detail): ?>
                    <?php $q = $detail['question']; ?>
                    <div class="review-item <?php echo $detail['is_correct'] ? 'correct' : 'wrong'; ?>">
                        <div style="font-weight: 600; margin-bottom: 10px; color: #fff;">
                            Câu <?php echo $idx + 1; ?>: <?php echo $q['content']; ?>
                        </div>
                        <?php 
                        $stmt_ans = $conn->prepare("SELECT * FROM answers WHERE question_id = ?");
                        $stmt_ans->execute([$q['id']]);
                        $answers = $stmt_ans->fetchAll();
                        
                        foreach($answers as $ans):
                            $cls = ""; $icon = "";
                            if ($ans['is_correct']) { $cls = "opt-correct"; $icon = '<i class="fa-solid fa-check"></i>'; }
                            if ($ans['id'] == $detail['selected'] && !$ans['is_correct']) { $cls = "opt-wrong"; $icon = '<i class="fa-solid fa-xmark"></i> (Bạn chọn)'; }
                        ?>
                            <div class="opt <?php echo $cls; ?>">
                                <span><?php echo $ans['content']; ?></span>
                                <span><?php echo $icon; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>
                <h1 style="margin-bottom: 5px;">Lịch sử làm bài</h1>
                <p style="color: var(--text-light); margin-bottom: 30px;">Danh sách các bài thi bạn đã hoàn thành.</p>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Tên đề thi</th>
                                <th>Thời gian nộp</th>
                                <th>Điểm số</th>
                                <th>Đánh giá</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($history) > 0): ?>
                                <?php foreach($history as $row): ?>
                                <tr>
                                    <td style="font-weight: 600; color: #fff;"><?php echo $row['exam_title']; ?></td>
                                    <td style="color: var(--text-light);"><?php echo date("d/m/Y H:i", strtotime($row['submitted_at'])); ?></td>
                                    <td>
                                        <span style="font-weight: bold; color: <?php echo ($row['score'] >= 5) ? '#34d399' : '#f43f5e'; ?>">
                                            <?php echo $row['score']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($row['score'] >= 8): ?>
                                            <span class="status-badge status-active">Giỏi</span>
                                        <?php elseif($row['score'] >= 5): ?>
                                            <span class="status-badge" style="background: rgba(251, 191, 36, 0.2); color: #fbbf24;">Đạt</span>
                                        <?php else: ?>
                                            <span class="status-badge status-closed">Trượt</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align: center; padding: 30px;">Bạn chưa làm bài thi nào.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </main>
    </div>
</body>
</html>