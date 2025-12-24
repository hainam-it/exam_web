<?php
require_once '../config/database.php';

// Thiết lập header để trình duyệt hiểu đây là file Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Bao_cao_ket_qua_thi.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Lấy dữ liệu
$sql = "SELECT u.username, u.fullname, e.title, r.score, r.submitted_at 
        FROM results r 
        JOIN users u ON r.user_id = u.id 
        JOIN exams e ON r.exam_id = e.id 
        ORDER BY r.submitted_at DESC";
$results = $conn->query($sql)->fetchAll();

// Xuất bảng HTML (Excel đọc được HTML Table)
echo '<meta charset="UTF-8">';
echo '<table border="1" style="font-family: Arial;">';
echo '<tr style="background-color: #f0f0f0; font-weight: bold;">
        <th>MSSV</th>
        <th>Họ và Tên</th>
        <th>Đề thi</th>
        <th>Điểm số</th>
        <th>Ngày nộp</th>
        <th>Đánh giá</th>
      </tr>';

foreach ($results as $row) {
    $status = ($row['score'] >= 5) ? 'Đạt' : 'Trượt';
    $color = ($row['score'] >= 5) ? '#d1fae5' : '#fee2e2'; // Xanh nhạt / Đỏ nhạt
    
    echo '<tr>
            <td>'.$row['username'].'</td>
            <td>'.$row['fullname'].'</td>
            <td>'.$row['title'].'</td>
            <td style="background-color: '.$color.'; text-align: center;">'.$row['score'].'</td>
            <td>'.date("d/m/Y H:i", strtotime($row['submitted_at'])).'</td>
            <td>'.$status.'</td>
          </tr>';
}
echo '</table>';
?>