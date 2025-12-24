<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập hệ thống</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="/exam_web/assets/css/style.css">
    <link rel="stylesheet" href="/exam_web/assets/css/login.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-box-pro">
            <div class="login-logo"><i class="fa-solid fa-graduation-cap"></i></div>
            <h2>Đăng Nhập</h2>
            <p class="sub-text">Vui lòng đăng nhập để tiếp tục vào hệ thống.</p>
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <form action="check_login.php" method="POST">
                <div class="input-group-pro">
                    <label>Tên đăng nhập</label>
                    <div class="input-wrapper">
                        <input type="text" name="username" class="form-control-pro" placeholder="Ví dụ: sv_nam" required>
                        <i class="fa-solid fa-user input-icon-left"></i>
                    </div>
                </div>
                
                <div class="input-group-pro">
                    <label>Mật khẩu</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="password" class="form-control-pro" placeholder="••••••••" required>
                        <i class="fa-solid fa-lock input-icon-left"></i>
                        <i class="fa-solid fa-eye toggle-password" id="togglePassword"></i>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 0.9rem; color: #cbd5e1;">
                    <label><input type="checkbox"> Ghi nhớ tôi</label>
                    <a href="forgot_password.php" style="color: #818cf8;">Quên mật khẩu?</a>
                </div>

                <button type="submit" class="btn-login-pro">
                    Đăng Nhập <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </button>
            </form>

            <div class="login-footer">
                Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
            </div>
        </div>
    </div>

    <script>
        // Script ẩn hiện mật khẩu
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            // Toggle type
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            // Toggle icon
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>