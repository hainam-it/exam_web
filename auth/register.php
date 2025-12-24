<?php
session_start();
require_once '../config/database.php';

$message_script = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $message_script = "Swal.fire('Lỗi', 'Mật khẩu xác nhận không khớp!', 'error');";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() > 0) {
            $message_script = "Swal.fire('Lỗi', 'Tên đăng nhập này đã có người dùng!', 'error');";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'student'; 

            $sql = "INSERT INTO users (fullname, username, password, role) VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql);
            
            if ($stmt_insert->execute([$fullname, $username, $hashed_password, $role])) {
                $message_script = "
                    Swal.fire({
                        title: 'Thành công!',
                        text: 'Đăng ký thành công!',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'login.php';
                    });
                ";
            } else {
                $message_script = "Swal.fire('Lỗi', 'Có lỗi xảy ra!', 'error');";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/exam_web/assets/css/style.css">
    
    <style>
        .login-wrapper { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        
        /* --- KHUNG FORM CỐ ĐỊNH KÍCH THƯỚC --- */
        .login-box-pro {
            position: relative;
            background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 24px; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); text-align: center;

            /* CỐ ĐỊNH KÍCH THƯỚC ĐỂ BẰNG FORM LOGIN */
            width: 420px;      /* Chiều rộng chuẩn */
            height: 660px;     /* Chiều cao cố định (bằng form login) */
            
            /* Căn giữa nội dung bên trong theo chiều dọc */
            display: flex;
            flex-direction: column;
            justify-content: center; 
            padding: 0 40px;   /* Padding 2 bên giữ nguyên, padding trên dưới bỏ để flex lo */
        }

        /* --- THU NHỎ NỘI DUNG ĐỂ VỪA KHUNG --- */
        .login-logo { 
            font-size: 2.2rem; /* Giảm size logo chút */
            margin-bottom: 0.5rem; 
            background: linear-gradient(135deg, #6366f1, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; 
        }
        .login-box-pro h2 { color: #fff; font-size: 1.6rem; font-weight: 800; margin-bottom: 0; }
        .sub-text { color: #94a3b8; margin-bottom: 1.2rem; font-size: 0.9rem; }
        
        /* Khoảng cách các ô input sít lại */
        .input-group-pro { position: relative; margin-bottom: 1rem; text-align: left; } 
        .input-group-pro label { display: block; color: #cbd5e1; font-size: 0.85rem; margin-bottom: 5px; font-weight: 500; }
        .input-wrapper { position: relative; }
        .input-icon-left { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 1rem; transition: 0.3s; z-index: 1; }
        
        .form-control-pro {
            width: 100%; padding: 12px 16px 12px 45px;
            background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px; color: #fff; font-size: 0.95rem; transition: all 0.3s ease;
            box-sizing: border-box;
        }
        .form-control-pro:focus { outline: none; border-color: #6366f1; background: rgba(15, 23, 42, 0.8); }
        .form-control-pro:focus ~ .input-icon-left { color: #6366f1; }
        
        .btn-login-pro {
            width: 100%; padding: 12px; border-radius: 12px; border: none; font-size: 1.1rem; font-weight: 700; color: white;
            background: linear-gradient(135deg, #6366f1, #8b5cf6); cursor: pointer; transition: all 0.3s ease; margin-top: 5px;
        }
        .btn-login-pro:hover { transform: translateY(-2px); box-shadow: 0 15px 30px -10px rgba(99, 102, 241, 0.7); }
        
        /* Nút quay lại (Góc trên trái) */
        .back-link {
            position: absolute;
            top: 25px;
            left: 25px;
            color: #64748b;
            text-decoration: none;
            font-size: 1.2rem;
            transition: 0.3s;
            display: flex; align-items: center; justify-content: center;
            width: 35px; height: 35px; border-radius: 50%;
            background: rgba(255,255,255,0.05);
        }
        .back-link:hover { color: #fff; background: rgba(255,255,255,0.1); }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-box-pro">
            
            <a href="login.php" class="back-link" title="Quay lại">
                <i class="fa-solid fa-arrow-left"></i>
            </a>

            <div style="margin-top: 10px;"> <div class="login-logo"><i class="fa-solid fa-user-plus"></i></div>
                <h2>Đăng Ký</h2>
                <p class="sub-text">Tạo tài khoản sinh viên mới</p>

                <form method="POST">
                    <div class="input-group-pro">
                        <label>Họ và Tên</label>
                        <div class="input-wrapper">
                            <input type="text" name="fullname" class="form-control-pro" placeholder="Nguyễn Văn A" required>
                            <i class="fa-solid fa-id-card input-icon-left"></i>
                        </div>
                    </div>

                    <div class="input-group-pro">
                        <label>Tên đăng nhập</label>
                        <div class="input-wrapper">
                            <input type="text" name="username" class="form-control-pro" placeholder="Username" required>
                            <i class="fa-solid fa-user input-icon-left"></i>
                        </div>
                    </div>
                    
                    <div class="input-group-pro">
                        <label>Mật khẩu</label>
                        <div class="input-wrapper">
                            <input type="password" name="password" class="form-control-pro" placeholder="••••••••" required>
                            <i class="fa-solid fa-lock input-icon-left"></i>
                        </div>
                    </div>

                    <div class="input-group-pro">
                        <label>Nhập lại mật khẩu</label>
                        <div class="input-wrapper">
                            <input type="password" name="confirm_password" class="form-control-pro" placeholder="••••••••" required>
                            <i class="fa-solid fa-check-double input-icon-left"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-login-pro">
                        Đăng Ký
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        <?php if($message_script) echo $message_script; ?>
    </script>
</body>
</html>