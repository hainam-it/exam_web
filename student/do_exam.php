<?php
session_start();
require_once '../config/database.php';
if (!isset($_SESSION['user_id'])) header("Location: ../auth/login.php");

$exam_id = $_GET['exam_id'] ?? 1;

// Lấy thông tin đề thi
$stmt = $conn->prepare("SELECT * FROM exams WHERE id = :id");
$stmt->execute(['id' => $exam_id]);
$exam = $stmt->fetch();

// Lấy câu hỏi
$q_stmt = $conn->prepare("SELECT * FROM questions WHERE exam_id = :eid");
$q_stmt->execute(['eid' => $exam_id]);
$questions = $q_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Làm bài: <?php echo $exam['title']; ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/exam_web/assets/css/style.css">
    <link rel="stylesheet" href="/exam_web/assets/css/exam.css">
</head>
<body>
    <header>
        <div class="container nav" style="display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center;">
                <a href="exam_list.php" class="btn-back-nav" onclick="return confirm('Bạn có chắc muốn thoát? Bài làm sẽ không được lưu!');">
                    <i class="fa-solid fa-arrow-left"></i> Quay lại
                </a>
                <div style="display: flex; align-items: center; gap: 10px; border-left: 1px solid rgba(255,255,255,0.2); padding-left: 15px;">
                    <i class="fa-solid fa-graduation-cap" style="font-size: 1.5rem; color: #6366f1;"></i>
                    <h3 style="margin: 0; color: #fff;">Hệ thống thi</h3>
                </div>
            </div>
            <div style="color: #fff;">Xin chào, <b><?php echo $_SESSION['fullname']; ?></b></div>
        </div>
    </header>

    <div class="container" style="margin-top: 30px; margin-bottom: 50px;">
        <form id="examForm" action="results.php" method="POST">
            <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
            
            <div class="exam-layout">
                <div class="card" style="transform: none !important; transition: none !important; box-shadow: none !important;">
                    <h2 style="margin-bottom: 10px; color: #fff;"><?php echo $exam['title']; ?></h2>
                    <div style="color: #94a3b8; margin-bottom: 20px; font-size: 0.9rem;">
                        <i class="fa-solid fa-clock"></i> Thời gian: <?php echo $exam['duration']; ?> phút | 
                        <i class="fa-solid fa-list"></i> Tổng: <?php echo count($questions); ?> câu
                    </div>
                    
                    <div id="questions-container">
                        <?php foreach($questions as $index => $q): ?>
                            <div class="question-block" data-index="<?php echo $index; ?>" id="q-<?php echo $q['id']; ?>">
                                <div class="question-title">
                                    <i class="fa-solid fa-flag btn-flag" id="flag-btn-<?php echo $index; ?>" onclick="toggleFlag(<?php echo $index; ?>)" title="Đánh dấu câu hỏi này"></i>
                                    <span style="color: #6366f1;">Câu <?php echo $index + 1; ?>:</span> 
                                    <?php echo $q['content']; ?>
                                </div>
                                <?php if (!empty($q['attachment'])): ?>
                                    <div style="margin-bottom: 15px;">
                                        <a href="../uploads/<?php echo $q['attachment']; ?>" target="_blank" class="btn" style="background: rgba(255,255,255,0.1); color: #6366f1; font-size: 0.85rem; padding: 5px 10px; border: 1px solid #6366f1;">
                                            <i class="fa-solid fa-paperclip"></i> Xem tài liệu
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <?php 
                                $a_stmt = $conn->prepare("SELECT * FROM answers WHERE question_id = :qid");
                                $a_stmt->execute(['qid' => $q['id']]);
                                $answers = $a_stmt->fetchAll();
                                foreach($answers as $ans):
                                ?>
                                    <label class="answer-option">
                                        <input type="radio" name="question[<?php echo $q['id']; ?>]" value="<?php echo $ans['id']; ?>" onchange="markDone(<?php echo $index; ?>)">
                                        <span><?php echo $ans['content']; ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="pagination-controls">
                        <button type="button" id="btn-prev" class="btn" style="background: #334155; color: white; display: none;" onclick="changePage(-1)"><i class="fa-solid fa-arrow-left"></i> Quay lại</button>
                        <span id="page-indicator" style="color: #94a3b8; font-weight: 600; align-self: center;">Trang 1</span>
                        <button type="button" id="btn-next" class="btn btn-primary" onclick="changePage(1)">Trang tiếp <i class="fa-solid fa-arrow-right"></i></button>
                        <button type="button" id="btn-submit" class="btn" style="background: #10b981; color: white; display: none;" onclick="checkSubmit()">
                            Nộp bài <i class="fa-solid fa-check"></i>
                        </button>
                    </div>
                </div>

                <div class="exam-sidebar">
                    <div class="timer-box" id="timer">00:00</div>
                    <div class="card" style="margin-top: 0; transform: none !important; transition: none !important; box-shadow: none !important;">
                        <p style="color: #fff; font-weight: 600; margin-bottom: 10px;">Danh sách câu hỏi</p>
                        <div class="question-map">
                            <?php foreach($questions as $index => $q): ?>
                                <div class="q-dot" id="dot-<?php echo $index; ?>" onclick="jumpToPage(<?php echo $index; ?>)">
                                    <?php echo $index + 1; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="margin-top: 15px; font-size: 0.8rem; color: #94a3b8; display: flex; gap: 15px; justify-content: center;">
                            <div style="display: flex; align-items: center; gap: 5px;"><div style="width: 10px; height: 10px; background: #6366f1; border-radius: 2px;"></div> Đã làm</div>
                            <div style="display: flex; align-items: center; gap: 5px;"><div style="width: 10px; height: 10px; background: #ef4444; border-radius: 2px;"></div> Đánh dấu</div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="modal-overlay" id="submitModal">
        <div class="custom-modal">
            <div class="modal-icon" id="modalIcon">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <div class="modal-title" id="modalTitle">Xác nhận nộp bài?</div>
            <div class="modal-text" id="modalMessage">
                Bạn vẫn còn câu hỏi chưa làm.
            </div>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeModal()">Xem lại</button>
                <button class="btn-confirm" onclick="confirmSubmit()">Nộp ngay</button>
            </div>
        </div>
    </div>

    <script>
        // --- 1. LOGIC MODAL POPUP ---
        const modal = document.getElementById('submitModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalMsg = document.getElementById('modalMessage');
        const modalIcon = document.getElementById('modalIcon');

        function checkSubmit() {
            const totalQuestions = document.querySelectorAll('.question-block').length;
            const answeredCount = document.querySelectorAll('input[type="radio"]:checked').length;
            const remaining = totalQuestions - answeredCount;

            if (remaining > 0) {
                // Nếu chưa làm hết -> Cảnh báo Vàng
                modalIcon.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i>';
                modalIcon.style.color = '#f59e0b'; // Vàng
                modalTitle.innerText = "Chưa hoàn thành!";
                modalMsg.innerText = `Bạn còn ${remaining} câu hỏi chưa chọn đáp án.\nBạn có chắc chắn muốn nộp bài không?`;
            } else {
                // Nếu làm hết -> Xác nhận Xanh
                modalIcon.innerHTML = '<i class="fa-solid fa-circle-check"></i>';
                modalIcon.style.color = '#10b981'; // Xanh
                modalTitle.innerText = "Xác nhận nộp bài";
                modalMsg.innerText = "Bạn đã hoàn thành tất cả câu hỏi.\nChúc bạn đạt điểm cao!";
            }
            
            // Hiện Popup
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        function confirmSubmit() {
            document.getElementById('examForm').submit();
        }

        // Đóng modal khi bấm ra ngoài
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }

        // --- 2. LOGIC CŨ (Cờ, Đồng hồ, Phân trang) ---
        function toggleFlag(index) {
            document.getElementById(`flag-btn-${index}`).classList.toggle('active');
            document.getElementById(`dot-${index}`).classList.toggle('flagged');
        }

        let duration = <?php echo $exam['duration']; ?> * 60;
        const display = document.querySelector('#timer');
        
        function formatTime(seconds) {
            let m = Math.floor(seconds / 60);
            let s = seconds % 60;
            return `${m < 10 ? '0'+m : m}:${s < 10 ? '0'+s : s}`;
        }
        display.textContent = formatTime(duration);

        const timer = setInterval(() => {
            duration--;
            display.textContent = formatTime(duration);
            if (duration < 300) { display.style.color = '#ef4444'; display.style.borderColor = '#ef4444'; }
            if (duration < 0) {
                clearInterval(timer);
                alert("Hết giờ làm bài! Hệ thống sẽ tự động nộp."); // Hết giờ thì vẫn alert vì cần force
                document.getElementById('examForm').submit();
            }
        }, 1000);

        const questions = document.querySelectorAll('.question-block');
        const itemsPerPage = 10;
        let currentPage = 1;
        const totalPages = Math.ceil(questions.length / itemsPerPage);

        function showPage(page) {
            if (page < 1) page = 1;
            if (page > totalPages) page = totalPages;
            currentPage = page;
            questions.forEach(q => q.classList.remove('active-q'));
            const start = (page - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            for (let i = start; i < end; i++) {
                if (questions[i]) questions[i].classList.add('active-q');
            }
            document.getElementById('btn-prev').style.display = (page === 1) ? 'none' : 'inline-flex';
            
            if (page === totalPages) {
                document.getElementById('btn-next').style.display = 'none';
                document.getElementById('btn-submit').style.display = 'inline-flex';
            } else {
                document.getElementById('btn-next').style.display = 'inline-flex';
                document.getElementById('btn-submit').style.display = 'none';
            }
            document.getElementById('page-indicator').textContent = `Trang ${page} / ${totalPages}`;
            document.querySelector('.exam-layout').scrollIntoView({ behavior: 'smooth' });
        }

        function changePage(step) { showPage(currentPage + step); }
        function jumpToPage(questionIndex) {
            const targetPage = Math.floor(questionIndex / itemsPerPage) + 1;
            showPage(targetPage);
        }
        function markDone(index) { document.getElementById('dot-' + index).classList.add('done'); }
        showPage(1);
    </script>
</body>
</html>