<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Chỉ Admin mới được vào
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";

// --- 1. XỬ LÝ XÓA USER ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    if ($id != $_SESSION['user_id']) { // Không cho tự xóa chính mình
        try {
            // Xóa kết quả thi trước (Ràng buộc khóa ngoại)
            $conn->prepare("DELETE FROM results WHERE user_id = ?")->execute([$id]);
            // Xóa user
            $conn->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            $message = "<div class='alert success'>Đã xóa thành viên!</div>";
        } catch (Exception $e) {
            $message = "<div class='alert error'>Lỗi: " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div class='alert error'>Không thể tự xóa tài khoản đang đăng nhập!</div>";
    }
}

// --- 2. XỬ LÝ THÊM / SỬA USER ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $fullname = $_POST['fullname'];
    $role = $_POST['role'];
    $password = $_POST['password'];
    $user_id = $_POST['user_id'] ?? ''; // Nếu có ID là Sửa, không là Thêm

    if ($user_id) {
        // --- CẬP NHẬT (EDIT) ---
        if (!empty($password)) {
            // Nếu nhập pass mới -> Update cả pass
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET fullname=?, role=?, password=? WHERE id=?");
            $stmt->execute([$fullname, $role, $hash, $user_id]);
        } else {
            // Nếu bỏ trống pass -> Giữ nguyên pass cũ
            $stmt = $conn->prepare("UPDATE users SET fullname=?, role=? WHERE id=?");
            $stmt->execute([$fullname, $role, $user_id]);
        }
        $message = "<div class='alert success'>Cập nhật thành công!</div>";
    } else {
        // --- THÊM MỚI (ADD) ---
        // Check trùng username
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        if ($check->rowCount() > 0) {
            $message = "<div class='alert error'>Tên đăng nhập đã tồn tại!</div>";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, fullname, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hash, $fullname, $role]);
            $message = "<div class='alert success'>Thêm thành viên mới thành công!</div>";
        }
    }
}

// --- 3. LẤY DANH SÁCH USER ---
$users = $conn->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Quản lý người dùng</title>
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
            padding: 30px; border-radius: 16px; width: 100%; max-width: 500px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5); animation: slideDown 0.3s ease;
        }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        /* Avatar Circle */
        .user-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; color: #fff; text-transform: uppercase;
        }
        
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
                    <h1 style="margin: 0;">Quản lý người dùng</h1>
                    <p style="color: var(--text-light); margin-top: 5px;">Thêm, sửa, xóa tài khoản hệ thống.</p>
                </div>
                <button onclick="openModal()" class="btn btn-primary">
                    <i class="fa-solid fa-user-plus"></i> Thêm mới
                </button>
            </div>

            <?php echo $message; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Thành viên</th>
                            <th>Tên đăng nhập</th>
                            <th>Vai trò</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                        <tr>
                            <td>#<?php echo $u['id']; ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php 
                                        // Tạo màu nền ngẫu nhiên cho avatar dựa trên ID
                                        $colors = ['#6366f1', '#ec4899', '#10b981', '#f59e0b', '#3b82f6'];
                                        $bg = $colors[$u['id'] % count($colors)];
                                    ?>
                                    <div class="user-avatar" style="background: <?php echo $bg; ?>">
                                        <?php echo substr($u['fullname'], 0, 1); ?>
                                    </div>
                                    <span style="font-weight: 500; color: #fff;"><?php echo $u['fullname']; ?></span>
                                </div>
                            </td>
                            <td style="color: var(--text-light);"><?php echo $u['username']; ?></td>
                            <td>
                                <?php if($u['role'] == 'admin'): ?>
                                    <span class="status-badge" style="background: rgba(244, 63, 94, 0.2); color: #f43f5e;">Admin</span>
                                <?php elseif($u['role'] == 'teacher'): ?>
                                    <span class="status-badge" style="background: rgba(168, 85, 247, 0.2); color: #a855f7;">Giáo viên</span>
                                <?php else: ?>
                                    <span class="status-badge" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6;">Sinh viên</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button onclick='editUser(<?php echo json_encode($u); ?>)' class="btn" style="padding: 6px 10px; background: rgba(59, 130, 246, 0.2); color: #3b82f6; margin-right: 5px;">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                
                                <?php if($u['id'] != $_SESSION['user_id']): ?>
                                <a href="?delete_id=<?php echo $u['id']; ?>" class="btn" style="padding: 6px 10px; background: rgba(244, 63, 94, 0.2); color: #f43f5e;" onclick="return confirm('Xóa vĩnh viễn user này?')">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div id="userModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 id="modalTitle" style="margin: 0;">Thêm thành viên</h2>
                <button onclick="closeModal()" style="background: none; border: none; color: #fff; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="user_id" id="user_id">
                
                <div class="form-group">
                    <label>Tên đăng nhập</label>
                    <input type="text" name="username" id="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Họ và Tên</label>
                    <input type="text" name="fullname" id="fullname" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Mật khẩu <span id="passNote" style="font-size: 0.8rem; color: #94a3b8; font-weight: normal;"></span></label>
                    <input type="password" name="password" id="password" class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Vai trò</label>
                    <select name="role" id="role" class="form-control" style="background: rgba(0,0,0,0.3); color: #fff;">
                        <option value="student">Sinh viên</option>
                        <option value="teacher">Giáo viên</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" onclick="closeModal()" class="btn" style="background: rgba(255,255,255,0.1); color: #fff;">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu lại</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('userModal');
        const modalTitle = document.getElementById('modalTitle');
        const userIdInput = document.getElementById('user_id');
        const usernameInput = document.getElementById('username');
        const fullnameInput = document.getElementById('fullname');
        const roleInput = document.getElementById('role');
        const passNote = document.getElementById('passNote');
        const passInput = document.getElementById('password');

        function openModal() {
            modal.style.display = 'flex';
            // Reset form for Add mode
            document.querySelector('form').reset();
            userIdInput.value = '';
            modalTitle.innerText = 'Thêm thành viên mới';
            usernameInput.removeAttribute('readonly');
            passNote.innerText = '(Bắt buộc)';
            passInput.setAttribute('required', 'required');
        }

        function editUser(user) {
            modal.style.display = 'flex';
            // Fill data for Edit mode
            userIdInput.value = user.id;
            usernameInput.value = user.username;
            usernameInput.setAttribute('readonly', 'readonly'); // Không cho sửa username
            fullnameInput.value = user.fullname;
            roleInput.value = user.role;
            
            modalTitle.innerText = 'Cập nhật thông tin';
            passNote.innerText = '(Để trống nếu không đổi)';
            passInput.removeAttribute('required');
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        // Đóng modal khi click ra ngoài
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>