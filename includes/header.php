<header>
    <div class="container nav">
        <div style="display: flex; align-items: center; gap: 10px;">
            <img src="../assets/images/logo.png" alt="Logo" style="height: 40px; display: none;"> <h2 style="color: var(--primary); margin: 0;">ExamSystem</h2>
        </div>
        
        <div style="display: flex; align-items: center; gap: 20px;">
            <span style="font-weight: 500;">Xin chào, <b><?php echo $_SESSION['fullname']; ?></b></span>
            <a href="../auth/logout.php" class="btn" style="background: #fee2e2; color: #991b1b; padding: 8px 15px; font-size: 0.9rem;">Đăng xuất</a>
        </div>
    </div>
</header>