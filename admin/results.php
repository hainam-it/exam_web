<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Chỉ Admin mới được vào
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// --- 1. XỬ LÝ AJAX CẬP NHẬT ĐIỂM ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'update_score') {
    header('Content-Type: application/json');
    $result_id = $_POST['result_id'];
    $new_score = (float)$_POST['score']; // Ép kiểu số thực
    
    // Validate điểm
    if ($new_score < 0 || $new_score > 10) {
        echo json_encode(['status' => 'error', 'message' => 'Điểm số phải từ 0 đến 10!']);
        exit;
    }

    try {
        $stmt = $conn->prepare("UPDATE results SET score = ? WHERE id = ?");
        $stmt->execute([$new_score, $result_id]);
        echo json_encode(['status' => 'success', 'message' => 'Cập nhật điểm thành công!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
    exit;
}

// --- 2. XỬ LÝ XÓA KẾT QUẢ ---
if (isset($_GET['delete_id'])) {
    $del_id = $_GET['delete_id'];
    try {
        $conn->prepare("DELETE FROM results WHERE id = ?")->execute([$del_id]);
        echo "<script>alert('Đã xóa kết quả thi!'); window.location.href='results.php';</script>";
    } catch (Exception $e) {
        echo "<script>alert('Lỗi xóa: ".$e->getMessage()."');</script>";
    }
}

// --- 3. LẤY DANH SÁCH KẾT QUẢ ---
// SỬA LỖI: Dùng u.fullname thay vì u.full_name và r.user_id thay vì r.student_id
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
    <title>Quản lý Kết quả thi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/exam_web/assets/css/style.css">
    <style>
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: 500; }
        .score-good { color: #34d399; }
        .score-bad { color: #f43f5e; }
        
        /* Modal Style */
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.7); backdrop-filter: blur(5px);
            justify-content: center; align-items: center;
        }
        .modal-content {
            background: #1e293b; border: 1px solid rgba(255,255,255,0.1);
            padding: 30px; border-radius: 16px; width: 100%; max-width: 400px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5); animation: slideDown 0.3s ease;
        }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h1 style="margin: 0;">Quản lý Kết quả thi</h1>
                    <p style="color: var(--text-light); margin-top: 5px;">Xem và chỉnh sửa điểm số của sinh viên.</p>
                </div>
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
                            <th style="text-align: center;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($results) > 0): ?>
                            <?php foreach($results as $row): ?>
                            <tr id="row-<?php echo $row['id']; ?>">
                                <td><?php echo $row['username']; ?></td>
                                <td style="font-weight: 500; color: #fff;"><?php echo $row['fullname']; ?></td>
                                <td><span class="status-badge status-active"><?php echo $row['exam_title']; ?></span></td>
                                <td>
                                    <span id="score-display-<?php echo $row['id']; ?>" style="font-weight: bold; font-size: 1.1em; color: <?php echo ($row['score'] >= 5) ? '#34d399' : '#f43f5e'; ?>">
                                        <?php echo $row['score']; ?>
                                    </span>
                                </td>
                                <td style="color: var(--text-light);"><?php echo date("d/m/Y H:i", strtotime($row['submitted_at'])); ?></td>
                                <td style="text-align: center;">
                                    <button onclick="openEditModal(<?php echo $row['id']; ?>, <?php echo $row['score']; ?>)" class="btn" style="padding: 6px 10px; background: rgba(59, 130, 246, 0.2); color: #3b82f6; margin-right: 5px;" title="Sửa điểm">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <a href="?delete_id=<?php echo $row['id']; ?>" onclick="return confirm('Xóa kết quả này?')" class="btn" style="padding: 6px 10px; background: rgba(244, 63, 94, 0.2); color: #f43f5e;" title="Xóa">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; padding: 30px;">Chưa có dữ liệu thi.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <!-- MODAL CHỈNH SỬA ĐIỂM -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">Cập nhật điểm số</h3>
                <span onclick="closeModal()" style="cursor: pointer; font-size: 1.5rem;">&times;</span>
            </div>
            <form id="editForm" onsubmit="submitUpdateScore(event)">
                <input type="hidden" name="ajax_action" value="update_score">
                <input type="hidden" name="result_id" id="edit_result_id">
                
                <div class="form-group">
                    <label>Điểm mới (Thang 10):</label>
                    <input type="number" name="score" id="edit_score" class="form-control" step="0.1" min="0" max="10" required>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" onclick="closeModal()" class="btn" style="background: rgba(255,255,255,0.1); color: #fff;">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('editModal');
        const resultIdInput = document.getElementById('edit_result_id');
        const scoreInput = document.getElementById('edit_score');

        function openEditModal(id, currentScore) {
            modal.style.display = 'flex';
            resultIdInput.value = id;
            scoreInput.value = currentScore;
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        function submitUpdateScore(e) {
            e.preventDefault();
            const form = document.getElementById('editForm');
            const formData = new FormData(form);

            fetch('', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    Swal.fire('Thành công', data.message, 'success').then(() => {
                        // Cập nhật giao diện mà không cần reload
                        const newScore = scoreInput.value;
                        const scoreDisplay = document.getElementById('score-display-' + resultIdInput.value);
                        scoreDisplay.innerText = newScore;
                        
                        // Đổi màu nếu điểm thấp/cao
                        if(newScore >= 5) scoreDisplay.style.color = '#34d399';
                        else scoreDisplay.style.color = '#f43f5e';
                        
                        closeModal();
                    });
                } else {
                    Swal.fire('Lỗi', data.message, 'error');
                }
            })
            .catch(err => Swal.fire('Lỗi', 'Không thể kết nối server', 'error'));
        }

        window.onclick = function(event) {
            if (event.target == modal) closeModal();
        }
    </script>
</body>
</html>