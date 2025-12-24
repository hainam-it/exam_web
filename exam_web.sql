-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th12 24, 2025 lúc 10:36 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `exam_web`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `answers`
--

CREATE TABLE `answers` (
  `id` int(11) NOT NULL,
  `question_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `answers`
--

INSERT INTO `answers` (`id`, `question_id`, `content`, `is_correct`) VALUES
(1, 1, '@', 0),
(2, 1, '$', 1),
(3, 1, '#', 0),
(4, 1, '&', 0),
(9, 3, 'len()', 0),
(10, 3, 'length()', 0),
(11, 3, 'count()', 1),
(12, 3, 'size()', 0),
(13, 4, '<h6>', 0),
(14, 4, '<head>', 0),
(15, 4, '<header>', 0),
(16, 4, '<h1>', 1),
(17, 5, 'color', 0),
(18, 5, 'background-color', 1),
(19, 5, 'bgcolor', 0),
(20, 5, 'bg-color', 0),
(53, 12, '1', 0),
(54, 12, '2', 1),
(55, 12, '3', 0),
(56, 12, '4', 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `exams`
--

CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `status` enum('active','closed') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `exams`
--

INSERT INTO `exams` (`id`, `title`, `description`, `duration`, `created_by`, `status`) VALUES
(1, 'Lập trình PHP Cơ bản', 'Kiểm tra kiến thức về biến, hàm và mảng trong PHP.', 15, 2, 'active'),
(2, 'HTML5 & CSS3 Master', 'Đề thi trắc nghiệm về giao diện web hiện đại\r\n', 20, 2, 'active'),
(4, 'Cấu trúc dữ liệu', 'Đề thi thử nghiệm (đang đóng)', 42, 3, '');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `questions`
--

INSERT INTO `questions` (`id`, `exam_id`, `content`, `attachment`) VALUES
(1, 1, 'Trong PHP, biến bắt đầu bằng ký tự nào?', NULL),
(3, 1, 'Hàm nào dùng để đếm số phần tử trong mảng?', NULL),
(4, 2, 'Thẻ nào tạo ra tiêu đề lớn nhất trong HTML?', NULL),
(5, 2, 'Để thay đổi màu nền trang web, ta dùng thuộc tính CSS nào?', NULL),
(12, 1, 'chó có mấy chân', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `results`
--

CREATE TABLE `results` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `score` float DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `results`
--

INSERT INTO `results` (`id`, `user_id`, `exam_id`, `score`, `submitted_at`) VALUES
(1, 4, 1, 10, '2023-10-20 09:15:00'),
(2, 5, 1, 6.67, '2023-10-20 09:20:00'),
(3, 4, 2, 5, '2023-10-21 14:00:00'),
(6, 4, 1, 0, '2025-12-24 12:00:20'),
(11, 7, 1, 8.33, '2025-12-24 13:46:23'),
(12, 7, 4, 0, '2025-12-24 13:50:02'),
(13, 7, 1, 0, '2025-12-24 14:00:55'),
(14, 7, 2, 0, '2025-12-24 14:11:02'),
(15, 7, 1, 0, '2025-12-24 14:13:30'),
(16, 7, 1, 0, '2025-12-24 14:15:29'),
(17, 7, 1, 8.33, '2025-12-24 14:22:17');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key_name` varchar(50) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `settings`
--

INSERT INTO `settings` (`id`, `key_name`, `value`, `created_at`) VALUES
(1, 'school_name', 'Hệ thống thi trắc nghiệm Online', '2025-12-24 06:21:54'),
(2, 'footer_info', '© 2025 Copyright by Admin', '2025-12-24 06:21:54'),
(3, 'exam_time_limit', '60', '2025-12-24 06:21:54');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL DEFAULT 'student',
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expire` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `email`, `role`, `reset_token`, `reset_token_expire`) VALUES
(1, 'admin', '$2y$10$FzsD6BbVFrhXkXvZV3Gepe2o3ZUDvTk9lBSvyr1lW6ztcEwsrQYKC', 'Quản Trị Viên', 'myemail@gmail.com', 'admin', NULL, NULL),
(2, 'gv_minh', '$2y$10$U3Q7ll5J9aFVtnI87G.Z5eWzrYCKOqp.sf.JzDUp7Sh6HQdgShu7u', 'Thầy Minh (Web)', 'giaovien@gmail.com', 'teacher', NULL, NULL),
(3, 'gv_huong', '$2y$10$/fTHy4uCVZUF0t/kjmc6ie0vEppdxZ7/mkykzKNIBTK5wGVUv/EYO', 'Cô Hương (Java)', 'giaovien@gmail.com', 'teacher', NULL, NULL),
(4, 'sv_nam', '$2y$10$lX7zs6nwYYYmogtX2GTkA.pRe021oCFCgmuE8NBwwFw4t8l7AKQSe', 'Nguyễn Văn Nam', 'sinhvien@gmail.com', 'student', NULL, NULL),
(5, 'sv_lan', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Thị Lan', 'sinhvien@gmail.com', 'student', NULL, NULL),
(6, 'sv_hung', '$2y$10$T1wOaxxBIYoN4R.jZ5oDXe.mHU2K2YysgTyKOeIU0AkxB8gtcfm9m', 'Lê Hùng', 'sinhvien@gmail.com', 'student', NULL, NULL),
(7, 'sv_xuan', '$2y$10$l4tK4GGbR4TU5c6.dsr4meR9A7ZKq.k9h6E0i/mK09lCEoXeN3JhS', 'Lê Minh Xuân', 'sinhvien@gmail.com', 'student', NULL, NULL),
(8, 'sv_A', '$2y$10$kJdxYTXvUXLWcmT7ZLw/..5XBNzMFz.kM5y8cNhvT9O4dp0qqjjxC', 'Nguyễn Văn A', 'sinhvien@gmail.com', 'student', NULL, NULL),
(9, 'admin1', '$2y$10$KnwvgCurS5vScpQs2mJdd.hd/AlIZaAyo0xzbMm9Jd3OjpqqTn/MK', 'a', '', 'student', NULL, NULL);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `answers`
--
ALTER TABLE `answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Chỉ mục cho bảng `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Chỉ mục cho bảng `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Chỉ mục cho bảng `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Chỉ mục cho bảng `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `answers`
--
ALTER TABLE `answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT cho bảng `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT cho bảng `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT cho bảng `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `answers`
--
ALTER TABLE `answers`
  ADD CONSTRAINT `answers_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`);

--
-- Các ràng buộc cho bảng `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`);

--
-- Các ràng buộc cho bảng `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `results_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
