<?php
session_start();

// Kiểm tra login để đổi nút
$is_logged_in = isset($_SESSION['user_id']);
$dashboard_link = "auth/login.php"; 

if ($is_logged_in) {
    switch ($_SESSION['role']) {
        case 'admin': $dashboard_link = "admin/dashboard.php"; break;
        case 'teacher': $dashboard_link = "teacher/dashboard.php"; break;
        case 'student': $dashboard_link = "student/dashboard.php"; break;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamPro - Hệ thống thi trực tuyến</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- RESET & BASE --- */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #0f172a; color: #fff; overflow-x: hidden; scroll-behavior: smooth; }
        a { text-decoration: none; }
        
        :root {
            --primary: #6366f1; --secondary: #ec4899; --accent: #8b5cf6;
            --dark: #1e293b; --darker: #0f172a;
        }

        /* --- NAVBAR (ĐÃ SỬA CHUẨN) --- */
        .navbar {
            display: flex; 
            justify-content: space-between; /* Đẩy 2 bên ra xa nhất có thể */
            align-items: center;
            padding: 15px 50px; 
            position: fixed; 
            width: 100%; 
            top: 0; 
            z-index: 1000;
            background: rgba(15, 23, 42, 0.9); 
            backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        .brand { font-size: 1.5rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 10px; min-width: 150px; }
        .brand i { color: var(--primary); }
        
        /* Menu ở giữa */
        .nav-links { display: flex; gap: 30px; }
        .nav-links a { color: #cbd5e1; font-weight: 500; transition: 0.3s; font-size: 0.95rem; }
        .nav-links a:hover { color: #fff; text-shadow: 0 0 10px rgba(99, 102, 241, 0.5); }
        
        /* Nút Auth ở GÓC PHẢI */
        .auth-buttons { display: flex; gap: 15px; align-items: center; min-width: 200px; justify-content: flex-end; }
        .btn-nav { padding: 8px 20px; border-radius: 50px; font-weight: 600; transition: 0.3s; font-size: 0.9rem; white-space: nowrap; }
        .btn-outline { border: 1px solid rgba(255,255,255,0.2); color: #fff; }
        .btn-outline:hover { border-color: var(--primary); background: rgba(99, 102, 241, 0.1); }
        .btn-fill { background: linear-gradient(90deg, var(--primary), var(--secondary)); color: #fff; border: none; }
        .btn-fill:hover { box-shadow: 0 0 15px rgba(99, 102, 241, 0.5); transform: translateY(-2px); }

        /* --- HERO --- */
        .hero {
            min-height: 100vh; display: flex; align-items: center; justify-content: space-between;
            padding: 80px 50px 0; position: relative;
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.15), transparent 40%),
                        radial-gradient(circle at bottom left, rgba(236, 72, 153, 0.1), transparent 40%);
        }
        .hero-content { max-width: 600px; z-index: 2; animation: slideUp 1s ease; }
        .hero-title {
            font-size: 3.5rem; line-height: 1.1; font-weight: 800; margin-bottom: 25px;
            background: linear-gradient(to right, #fff, #a5b4fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .hero-desc { color: #94a3b8; font-size: 1.1rem; margin-bottom: 35px; line-height: 1.6; }
        .hero-image { position: relative; z-index: 2; animation: float 6s ease-in-out infinite; }
        .hero-image img { width: 550px; border-radius: 24px; box-shadow: 0 25px 50px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); }

        /* --- FEATURES --- */
        .features { padding: 100px 50px; background: var(--darker); }
        .section-title { text-align: center; margin-bottom: 70px; }
        .section-title h2 { font-size: 2.5rem; margin-bottom: 10px; color: #fff; }
        .section-title span { color: var(--secondary); font-weight: 700; text-transform: uppercase; letter-spacing: 2px; font-size: 0.85rem; display: block; margin-bottom: 10px; }
        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; }
        .feature-card {
            background: var(--dark); padding: 40px; border-radius: 24px;
            border: 1px solid rgba(255,255,255,0.05); transition: 0.3s; position: relative; overflow: hidden;
        }
        .feature-card:hover { transform: translateY(-10px); border-color: rgba(99, 102, 241, 0.3); }
        .feature-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, var(--primary), var(--secondary)); }
        .icon-box { width: 70px; height: 70px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 25px; }
        .icon-1 { background: rgba(99, 102, 241, 0.1); color: var(--primary); }
        .icon-2 { background: rgba(236, 72, 153, 0.1); color: var(--secondary); }
        .icon-3 { background: rgba(139, 92, 246, 0.1); color: var(--accent); }

        /* --- STATS --- */
        .stats {
            padding: 80px 50px; background: linear-gradient(135deg, var(--dark), var(--darker));
            border-top: 1px solid rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.05);
            display: flex; justify-content: center; flex-wrap: wrap; gap: 80px; text-align: center;
        }
        .stat-item h3 { font-size: 3.5rem; font-weight: 800; margin-bottom: 5px; background: linear-gradient(135deg, var(--primary), var(--secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-item p { color: #cbd5e1; font-size: 1.1rem; }

        /* --- ROLES --- */
        .roles { padding: 100px 50px; }
        .role-container { display: flex; align-items: center; gap: 80px; margin-bottom: 100px; }
        .role-container.reverse { flex-direction: row-reverse; }
        .role-img img { width: 100%; border-radius: 24px; box-shadow: -20px 20px 0 rgba(99, 102, 241, 0.1); border: 1px solid rgba(255,255,255,0.1); }
        .role-info h3 { font-size: 2.5rem; margin-bottom: 20px; color: #fff; }
        .role-list li { list-style: none; margin-bottom: 15px; display: flex; align-items: center; gap: 15px; color: #cbd5e1; font-size: 1.1rem; }
        .role-list i { color: var(--secondary); font-size: 1.2rem; }

        /* --- CONTACT (ĐÃ GIỮ NGUYÊN STYLE ĐẸP) --- */
        .contact { padding: 100px 50px; position: relative; background: var(--darker); }
        .contact-container { display: grid; grid-template-columns: 1fr 1.5fr; gap: 50px; max-width: 1200px; margin: 0 auto; }
        .contact-info-box {
            background: linear-gradient(135deg, var(--primary), var(--accent)); padding: 50px 40px; border-radius: 24px;
            color: #fff; box-shadow: 0 20px 50px rgba(99, 102, 241, 0.3); display: flex; flex-direction: column; justify-content: space-between;
        }
        .contact-info-box h3 { font-size: 2rem; margin-bottom: 10px; }
        .info-row { display: flex; align-items: center; gap: 20px; margin-bottom: 25px; }
        .info-icon { width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .social-icons { display: flex; gap: 15px; margin-top: 40px; }
        .social-btn { width: 45px; height: 45px; border-radius: 50%; background: rgba(0,0,0,0.2); color: #fff; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
        .social-btn:hover { background: #fff; color: var(--primary); }

        .contact-form-box { background: var(--dark); padding: 50px; border-radius: 24px; border: 1px solid rgba(255,255,255,0.05); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .c-input { width: 100%; padding: 15px; background: #0f172a; border: 1px solid #334155; border-radius: 12px; color: #fff; outline: none; transition: 0.3s; }
        .c-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2); }
        .c-textarea { width: 100%; height: 150px; resize: none; margin-bottom: 20px; }
        .btn-send { width: 100%; padding: 15px; border-radius: 12px; border: none; font-weight: 700; font-size: 1.1rem; background: linear-gradient(90deg, var(--primary), var(--secondary)); color: #fff; cursor: pointer; transition: 0.3s; }
        .btn-send:hover { opacity: 0.9; transform: translateY(-2px); }

        /* CTA & Footer */
        .cta { padding: 80px 20px; text-align: center; background: linear-gradient(135deg, var(--primary), var(--accent)); margin: 0 50px 50px; border-radius: 30px; }
        .cta .btn-white { background: #fff; color: var(--primary); padding: 15px 40px; border-radius: 50px; font-weight: bold; font-size: 1.1rem; transition: 0.3s; display: inline-block; margin-top: 20px;}
        footer { padding: 40px 50px; text-align: center; border-top: 1px solid rgba(255,255,255,0.05); color: #64748b; font-size: 0.9rem; background: var(--darker); }

        /* Animations */
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(50px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 1000px) {
            .navbar { padding: 20px; }
            .nav-links { display: none; } /* Ẩn menu giữa trên mobile */
            .hero, .contact-container, .role-container { flex-direction: column; text-align: center; height: auto; padding: 100px 20px; }
            .hero-image img, .role-img img { width: 100%; margin-top: 30px; }
            .contact-container { grid-template-columns: 1fr; }
            .auth-buttons { min-width: auto; } /* Mobile thì auth button tự co */
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="brand">
            <i class="fa-solid fa-graduation-cap"></i> ExamPro
        </div>
        
        <div class="nav-links">
            <a href="#features">Tính năng</a>
            <a href="#roles">Giải pháp</a>
            <a href="#contact">Liên hệ</a>
        </div>
        
        <div class="auth-buttons">
            <?php if(!$is_logged_in): ?>
                <a href="auth/login.php" class="btn-nav btn-outline">Đăng nhập</a>
                <a href="auth/register.php" class="btn-nav btn-fill">Đăng ký</a>
            <?php else: ?>
                <a href="<?php echo $dashboard_link; ?>" class="btn-nav btn-fill">
                    <i class="fa-solid fa-chalkboard-user"></i> Dashboard
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <header class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Nền tảng thi trắc nghiệm <br> thông minh & <span style="color: #8b5cf6;">hiệu quả</span></h1>
            <p class="hero-desc">
                Giải pháp toàn diện cho việc tổ chức thi, kiểm tra và đánh giá năng lực. 
                Bảo mật cao, chấm điểm tức thì, quản lý dễ dàng dành cho trường học và doanh nghiệp.
            </p>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <?php if(!$is_logged_in): ?>
                    <a href="auth/register.php" class="btn-nav btn-fill" style="padding: 15px 40px; font-size: 1.1rem;">
                        Bắt đầu miễn phí <i class="fa-solid fa-arrow-right"></i>
                    </a>
                <?php else: ?>
                    <a href="<?php echo $dashboard_link; ?>" class="btn-nav btn-fill" style="padding: 15px 40px; font-size: 1.1rem;">
                        Tiếp tục làm việc
                    </a>
                <?php endif; ?>
                <a href="#features" class="btn-nav btn-outline" style="padding: 15px 30px;">Tìm hiểu thêm</a>
            </div>
        </div>
        <div class="hero-image">
            <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Dashboard Demo">
        </div>
    </header>

    <section id="features" class="features">
        <div class="section-title">
            <span>Tại sao chọn chúng tôi?</span>
            <h2>Tính năng vượt trội</h2>
        </div>
        <div class="grid-3">
            <div class="feature-card">
                <div class="icon-box icon-1"><i class="fa-solid fa-shield-halved"></i></div>
                <h3>Bảo mật tuyệt đối</h3>
                <p>Hệ thống phân quyền chặt chẽ, ngăn chặn truy cập trái phép. Đảm bảo tính công bằng và an toàn dữ liệu cho mọi kỳ thi.</p>
            </div>
            <div class="feature-card">
                <div class="icon-box icon-2"><i class="fa-solid fa-bolt"></i></div>
                <h3>Chấm điểm tức thì</h3>
                <p>Hệ thống tự động chấm điểm ngay sau khi nộp bài. Tiết kiệm 90% thời gian cho giáo viên và cung cấp kết quả ngay lập tức.</p>
            </div>
            <div class="feature-card">
                <div class="icon-box icon-3"><i class="fa-solid fa-laptop-code"></i></div>
                <h3>Giao diện thân thiện</h3>
                <p>Thiết kế hiện đại, tối ưu trải nghiệm người dùng (UX/UI). Dễ dàng sử dụng trên cả máy tính, máy tính bảng và điện thoại.</p>
            </div>
        </div>
    </section>

    <section class="stats">
        <div class="stat-item">
            <h3>5,000+</h3>
            <p>Sinh viên tham gia</p>
        </div>
        <div class="stat-item">
            <h3>1,200+</h3>
            <p>Đề thi được tạo</p>
        </div>
        <div class="stat-item">
            <h3>99.9%</h3>
            <p>Thời gian hoạt động</p>
        </div>
    </section>

    <section id="roles" class="roles">
        <div class="role-container">
            <div class="role-img">
                <img src="https://images.unsplash.com/photo-1544717305-2782549b5136?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Teacher">
            </div>
            <div class="role-info">
                <h3>Dành cho Giảng viên</h3>
                <p style="color: #94a3b8; margin-bottom: 20px; font-size: 1.1rem;">Công cụ đắc lực hỗ trợ soạn đề và quản lý lớp học hiệu quả.</p>
                <ul class="role-list">
                    <li><i class="fa-solid fa-circle-check"></i> Tạo ngân hàng câu hỏi phong phú</li>
                    <li><i class="fa-solid fa-circle-check"></i> Trộn đề ngẫu nhiên</li>
                    <li><i class="fa-solid fa-circle-check"></i> Xuất báo cáo điểm chi tiết</li>
                </ul>
            </div>
        </div>
        <div class="role-container reverse">
            <div class="role-img">
                <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Student">
            </div>
            <div class="role-info">
                <h3>Dành cho Sinh viên</h3>
                <p style="color: #94a3b8; margin-bottom: 20px; font-size: 1.1rem;">Trải nghiệm làm bài thi mượt mà, công bằng và minh bạch.</p>
                <ul class="role-list">
                    <li><i class="fa-solid fa-circle-check"></i> Làm bài thi trắc nghiệm với timer</li>
                    <li><i class="fa-solid fa-circle-check"></i> Xem lại lịch sử bài thi</li>
                    <li><i class="fa-solid fa-circle-check"></i> Hỗ trợ mọi thiết bị</li>
                </ul>
            </div>
        </div>
    </section>

    <section id="contact" class="contact">
        <div class="section-title">
            <span>Kết nối ngay</span>
            <h2>Liên hệ với chúng tôi</h2>
        </div>
        <div class="contact-container">
            <div class="contact-info-box">
                <div>
                    <h3>Thông tin liên hệ</h3>
                    <p>Chúng tôi luôn sẵn sàng hỗ trợ bạn 24/7. Hãy để lại tin nhắn hoặc liên hệ trực tiếp qua các kênh sau:</p>
                    <div class="info-row"><div class="info-icon"><i class="fa-solid fa-location-dot"></i></div><span>123 Đường ABC, Hà Nội</span></div>
                    <div class="info-row"><div class="info-icon"><i class="fa-solid fa-phone"></i></div><span>+84 987 654 321</span></div>
                    <div class="info-row"><div class="info-icon"><i class="fa-solid fa-envelope"></i></div><span>support@exampro.vn</span></div>
                </div>
                <div class="social-icons">
                    <a href="#" class="social-btn"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="social-btn"><i class="fa-brands fa-twitter"></i></a>
                    <a href="#" class="social-btn"><i class="fa-brands fa-instagram"></i></a>
                </div>
            </div>
            <div class="contact-form-box">
                <form action="#">
                    <div class="form-grid">
                        <input type="text" class="c-input" placeholder="Họ tên của bạn">
                        <input type="email" class="c-input" placeholder="Địa chỉ Email">
                    </div>
                    <input type="text" class="c-input" placeholder="Tiêu đề tin nhắn" style="margin-bottom: 20px;">
                    <textarea class="c-input c-textarea" placeholder="Nội dung tin nhắn..."></textarea>
                    <button type="button" class="btn-send">Gửi tin nhắn <i class="fa-solid fa-paper-plane"></i></button>
                </form>
            </div>
        </div>
    </section>

    <section class="cta">
        <h2>Sẵn sàng chuyển đổi số giáo dục?</h2>
        <p>Tham gia cùng hàng ngàn giáo viên và sinh viên ngay.</p>
        <a href="auth/register.php" class="btn-white">Đăng ký tài khoản ngay</a>
    </section>

    <footer>
        <p>&copy; 2024 ExamPro System. Developed with <i class="fa-solid fa-heart" style="color: #ec4899;"></i> and PHP.</p>
    </footer>

</body>
</html>