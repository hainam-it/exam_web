<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Chỉ Admin mới được vào
if ($_SESSION['role'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";

// --- 1. XỬ LÝ XÓA ĐỀ THI ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    try {
        // Xóa kết quả thi liên quan trước
        $conn->prepare("DELETE FROM results WHERE exam_id = ?")->execute([$id]);
        
        // Xóa đáp án của các câu hỏi trong đề này
        $conn->prepare("DELETE FROM answers WHERE question_id IN (SELECT id FROM questions WHERE exam_id = ?)")->execute([$id]);
        
        // Xóa câu hỏi trong đề này
        $conn->prepare("DELETE FROM questions WHERE exam_id = ?")->execute([$id]);
        
        // Cuối cùng xóa đề thi
        $conn->prepare("DELETE FROM exams WHERE id = ?")->execute([$id]);
        
        $message = "<div class='alert success'>Đã xóa đề thi và toàn bộ dữ liệu liên quan!</div>";
    } catch (Exception $e) {
        $message = "<div class='alert error'>Lỗi: " . $e->getMessage() . "</div>";
    }
}

// --- 2. XỬ LÝ THÊM / SỬA ĐỀ THI ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $duration = $_POST['duration'];
    $status = $_POST['status'];
    $exam_id = $_POST['exam_id'] ?? '';

    if ($exam_id) {
        // --- CẬP NHẬT ---
        $stmt = $conn->prepare("UPDATE exams SET title=?, description=?, duration=?, status=? WHERE id=?");
        $stmt->execute([$title, $description, $duration, $status, $exam_id]);
        $message = "<div class='alert success'>Cập nhật đề thi thành công!</div>";
    } else {
        // --- THÊM MỚI ---
        $stmt = $conn->prepare("INSERT INTO exams (title, description, duration, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $description, $duration, $status]);
        $message = "<div class='alert success'>Tạo đề thi mới thành công!</div>";
    }
}

// --- 3. LẤY DANH SÁCH ĐỀ THI ---
// Lấy thêm số lượng câu hỏi để hiển thị cho đẹp
$sql = "SELECT e.*, (SELECT COUNT(*) FROM questions WHERE exam_id = e.id) as q_count FROM exams e ORDER BY id DESC";
$exams = $conn->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Quản lý Đề thi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/exam_web/assets/css/style.css">
    <style>
        /* Modal Style */
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.7); backdrop-filter: blur(5px);
            justify-content: center; align-items: center;
        }
        .modal-content {
            background: #1e293b; border: 1px solid rgba(255,255,255,0.1);
            padding: 30px; border-radius: 16px; width: 100%; max-width: 600px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5); animation: slideDown 0.3s ease;
        }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 500; }
        .alert.success { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .alert.error { background: rgba(244, 63, 94, 0.2); color: #f43f5e; border: 1px solid rgba(244, 63, 94, 0.3); }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h1 style="margin: 0;">Quản lý Đề thi</h1>
                    <p style="color: var(--text-light); margin-top: 5px;">Tạo đề thi, thiết lập thời gian và trạng thái.</p>
                </div>
                <button onclick="openModal()" class="btn btn-primary">
                    <i class="fa-solid fa-plus-circle"></i> Tạo đề thi mới
                </button>
            </div>

            <?php echo $message; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên đề thi</th>
                            <th>Thời gian</th>
                            <th>Câu hỏi</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($exams) > 0): ?>
                            <?php foreach($exams as $ex): ?>
                            <tr>
                                <td>#<?php echo $ex['id']; ?></td>
                                <td>
                                    <div style="font-weight: 600; color: #fff; font-size: 1rem;"><?php echo $ex['title']; ?></div>
                                    <div style="font-size: 0.85rem; color: var(--text-light); margin-top: 4px;"><?php echo substr($ex['description'], 0, 50); ?>...</div>
                                </td>
                                <td><i class="fa-regular fa-clock"></i> <?php echo $ex['duration']; ?> phút</td>
                                <td><span class="status-badge" style="background: rgba(99, 102, 241, 0.2); color: #818cf8;"><?php echo $ex['q_count']; ?> câu</span></td>
                                <td>
                                    <?php if($ex['status'] == 'active'): ?>
                                        <span class="status-badge status-active">Đang mở</span>
                                    <?php else: ?>
                                        <span class="status-badge status-closed">Đã đóng</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button onclick='editExam(<?php echo json_encode($ex); ?>)' class="btn" style="padding: 6px 10px; background: rgba(59, 130, 246, 0.2); color: #3b82f6; margin-right: 5px;" title="Sửa">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    
                                    <a href="?delete_id=<?php echo $ex['id']; ?>" class="btn" style="padding: 6px 10px; background: rgba(244, 63, 94, 0.2); color: #f43f5e;" onclick="return confirm('CẢNH BÁO: Xóa đề thi sẽ xóa toàn bộ câu hỏi và kết quả thi của sinh viên liên quan. Bạn có chắc chắn muốn xóa?')" title="Xóa">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; padding: 30px;">Chưa có đề thi nào. Hãy tạo mới!</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div id="examModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 id="modalTitle" style="margin: 0;">Tạo đề thi mới</h2>
                <button onclick="closeModal()" style="background: none; border: none; color: #fff; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="exam_id" id="exam_id">
                
                <div class="form-group">
                    <label>Tên đề thi</label>
                    <input type="text" name="title" id="title" class="form-control" placeholder="VD: Kiểm tra giữa kỳ môn PHP" required>
                </div>
                
                <div class="form-group">
                    <label>Mô tả chi tiết</label>
                    <textarea name="description" id="description" rows="3" class="form-control" placeholder="Nhập ghi chú cho sinh viên..."></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Thời gian làm bài (Phút)</label>
                        <input type="number" name="duration" id="duration" class="form-control" min="1" value="60" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select name="status" id="status" class="form-control" style="background: rgba(0,0,0,0.3); color: #fff;">
                            <option value="active">Công khai (Active)</option>
                            <option value="inactive">Ẩn (Inactive)</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" onclick="closeModal()" class="btn" style="background: rgba(255,255,255,0.1); color: #fff;">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu lại</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('examModal');
        const modalTitle = document.getElementById('modalTitle');
        const examIdInput = document.getElementById('exam_id');
        const titleInput = document.getElementById('title');
        const descInput = document.getElementById('description');
        const durationInput = document.getElementById('duration');
        const statusInput = document.getElementById('status');

        function openModal() {
            modal.style.display = 'flex';
            // Reset form cho chế độ Thêm mới
            document.querySelector('form').reset();
            examIdInput.value = '';
            modalTitle.innerText = 'Tạo đề thi mới';
            statusInput.value = 'active'; // Mặc định là Active
        }

        function editExam(exam) {
            modal.style.display = 'flex';
            // Điền dữ liệu vào form để Sửa
            examIdInput.value = exam.id;
            titleInput.value = exam.title;
            descInput.value = exam.description;
            durationInput.value = exam.duration;
            statusInput.value = exam.status;
            
            modalTitle.innerText = 'Chỉnh sửa đề thi';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        // Đóng modal khi click ra ngoài vùng nội dung
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>