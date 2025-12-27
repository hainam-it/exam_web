<div class="sidebar">
    <div class="brand">
        <i class="fa-solid fa-graduation-cap"></i> ExamPro
    </div>

    <nav style="flex: 1; overflow-y: auto;">
        <?php 
            // 1. Lấy tên file hiện tại để highlight menu
            $current_page = basename($_SERVER['PHP_SELF']);

            // 2. Tạo đường dẫn Profile động dựa trên Role
            // Ví dụ: role là 'admin' -> link là '../admin/profile.php'
            $profile_url = "../" . $_SESSION['role'] . "/profile.php";
        ?>

        <?php if ($_SESSION['role'] == 'student'): ?>
            <a href="../student/dashboard.php" class="menu-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-house"></i> Trang chủ
            </a>
            <a href="../student/exam_list.php" class="menu-item <?php echo ($current_page == 'exam_list.php' || $current_page == 'do_exam.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-file-pen"></i> Bài thi
            </a>
            <a href="../student/results.php" class="menu-item <?php echo $current_page == 'results.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-simple"></i> Kết quả
            </a>
        <?php endif; ?>

        <?php if($_SESSION['role'] == 'teacher'): ?>
            <a href="../teacher/dashboard.php" class="menu-item"><i class="fa-solid fa-gauge"></i> Tổng quan</a>
            <a href="../teacher/exams.php" class="menu-item"><i class="fa-solid fa-file-lines"></i> Quản lý Đề thi</a>
            <a href="../teacher/questions.php" class="menu-item"><i class="fa-solid fa-circle-question"></i> Ngân hàng câu hỏi</a>
            <a href="../teacher/results.php" class="menu-item"><i class="fa-solid fa-chart-bar"></i> Kết quả thi</a>
        <?php endif; ?>

        <?php if ($_SESSION['role'] == 'admin'): ?>
            <a href="../admin/dashboard.php" class="menu-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-layer-group"></i> Tổng quan
            </a>
            <a href="../admin/users.php" class="menu-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-users"></i> Quản lý người dùng
            </a>
            <a href="../admin/exams.php" class="menu-item <?php echo $current_page == 'exams.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-folder-open"></i> Quản lý đề thi
            </a>
            <a href="../admin/results.php" class="menu-item"><i class="fa-solid fa-chart-bar"></i> Kết quả thi</a>
            <a href="../admin/settings.php" class="menu-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-gear"></i> Cài đặt hệ thống
            </a>
        <?php endif; ?>

        <div style="border-top: 1px solid rgba(255,255,255,0.1); margin: 10px 15px;"></div>

        <a href="<?php echo $profile_url; ?>" class="menu-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-user-gear"></i> Hồ sơ & Mật khẩu
        </a>

    </nav>

    <div class="user-info">
        <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
        
        <div style="flex: 1; overflow: hidden; cursor: pointer;" onclick="window.location.href='<?php echo $profile_url; ?>'">
            <div style="font-weight: 600; font-size: 0.9rem; white-space: nowrap; text-overflow: ellipsis;">
                <?php echo $_SESSION['fullname']; ?>
            </div>
            <div style="font-size: 0.8rem; color: #94a3b8; text-transform: capitalize;">
                <?php 
                    $role_name = '';
                    switch($_SESSION['role']) {
                        case 'admin': $role_name = 'Quản trị viên'; break;
                        case 'teacher': $role_name = 'Giảng viên'; break;
                        case 'student': $role_name = 'Sinh viên'; break;
                    }
                    echo $role_name;
                ?>
            </div>
        </div>

        <a href="../auth/logout.php" title="Đăng xuất" style="color: #f43f5e; padding: 5px;">
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>
    </div>
</div>