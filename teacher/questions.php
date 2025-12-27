<?php
// teacher/questions.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// --- 1. HÀM ĐỌC WORD (GIỮ NGUYÊN) ---
function read_docx($filename){
    $striped_content = '';
    $content = '';
    $zip = new ZipArchive;
    if ($zip->open($filename) === TRUE) {
        if (($index = $zip->locateName("word/document.xml")) !== false) {
            $content = $zip->getFromIndex($index);
            $zip->close();
        }
    }
    if (!empty($content)) {
        $content = str_replace('</w:p>', "\n", $content); 
        $content = str_replace('</w:r>', "", $content); 
        $striped_content = strip_tags($content);
    }
    return $striped_content;
}

// --- 2. XỬ LÝ AJAX ---
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'];

    try {
        // A. XỬ LÝ XÓA
        if ($action == 'delete_question') {
            $del_id = $_POST['id'];
            $conn->prepare("DELETE FROM answers WHERE question_id = ?")->execute([$del_id]);
            $conn->prepare("DELETE FROM questions WHERE id = ?")->execute([$del_id]);
            echo json_encode(['status' => 'success', 'message' => 'Đã xóa câu hỏi thành công!']);
            exit;
        }

        // B. LẤY DỮ LIỆU ĐỂ SỬA
        if ($action == 'get_question_detail') {
            $q_id = $_POST['id'];
            $stmt = $conn->prepare("SELECT * FROM questions WHERE id = ?");
            $stmt->execute([$q_id]);
            $question = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt_ans = $conn->prepare("SELECT * FROM answers WHERE question_id = ? ORDER BY id ASC");
            $stmt_ans->execute([$q_id]);
            $answers = $stmt_ans->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'success', 'question' => $question, 'answers' => $answers]);
            exit;
        }

        // C. LƯU DỮ LIỆU ĐÃ SỬA
        if ($action == 'update_question') {
            $q_id = $_POST['q_id'];
            $content = $_POST['content'];
            $exam_id = $_POST['exam_id'];
            $answers_text = $_POST['answer']; 
            $correct_idx = $_POST['correct'];

            $conn->beginTransaction();
            $stmt = $conn->prepare("UPDATE questions SET content = ?, exam_id = ? WHERE id = ?");
            $stmt->execute([$content, $exam_id, $q_id]);

            $conn->prepare("DELETE FROM answers WHERE question_id = ?")->execute([$q_id]);
            
            $stmt_ans = $conn->prepare("INSERT INTO answers (question_id, content, is_correct) VALUES (?, ?, ?)");
            foreach($answers_text as $idx => $ans) {
                $is_true = ($idx == $correct_idx) ? 1 : 0;
                $stmt_ans->execute([$q_id, $ans, $is_true]);
            }
            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Cập nhật thành công!']);
            exit;
        }

        // D. XEM TRƯỚC (PREVIEW) FILE WORD
        if ($action == 'preview_import') {
            if (isset($_FILES['file_word']) && $_FILES['file_word']['error'] == 0) {
                $ext = pathinfo($_FILES['file_word']['name'], PATHINFO_EXTENSION);
                if ($ext != 'docx') {
                    echo json_encode(['status' => 'error', 'message' => 'Chỉ hỗ trợ file .docx!']); exit;
                }

                $text = read_docx($_FILES['file_word']['tmp_name']);
                $lines = explode("\n", $text);
                $questions_data = []; 
                $current_q = [];
                
                // --- LOGIC PARSE V3 (Đã tối ưu) ---
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;

                    // Check dòng "Đáp án: A"
                    if (preg_match('/^(đáp án|answer|key|kết quả|đáp số)[\s\:\-\.]*([A-D])/iu', $line, $key_match)) {
                        $correct_char = strtoupper($key_match[2]); 
                        $correct_idx = ord($correct_char) - 65; 
                        if (isset($current_q['answers']) && isset($current_q['answers'][$correct_idx])) {
                            foreach ($current_q['answers'] as &$ans_reset) { $ans_reset['correct'] = 0; }
                            $current_q['answers'][$correct_idx]['correct'] = 1;
                        }
                        continue;
                    }

                    // Check dòng đáp án A. B.
                    if (preg_match('/^\s*(\*)?\s*([A-D])\s*([\.\)\:\,])\s+(.*)$/iu', $line, $matches)) {
                        $is_correct_prefix = !empty($matches[1]);
                        $raw_content = $matches[4];
                        $is_correct_suffix = false;
                        if (preg_match('/[\s\(\[]+(đúng|true|correct|\*)[\s\)\]]*$/iu', $raw_content)) {
                            $is_correct_suffix = true;
                            $raw_content = preg_replace('/[\s\(\[]+(đúng|true|correct|\*)[\s\)\]]*$/iu', '', $raw_content);
                        }
                        $is_final_correct = ($is_correct_prefix || $is_correct_suffix) ? 1 : 0;
                        $current_q['answers'][] = ['text' => trim($raw_content), 'correct' => $is_final_correct];
                    } else {
                        // Dòng câu hỏi
                        if (!empty($current_q) && isset($current_q['answers']) && count($current_q['answers']) >= 2) {
                            $questions_data[] = $current_q; 
                            $current_q = []; 
                        }
                        if (empty($current_q['content'])) $current_q['content'] = $line;
                        else $current_q['content'] .= " " . $line;
                    }
                }
                if (!empty($current_q) && isset($current_q['answers'])) $questions_data[] = $current_q;

                if (count($questions_data) > 0) {
                    echo json_encode(['status' => 'success', 'data' => $questions_data, 'count' => count($questions_data)]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy câu hỏi nào hợp lệ!']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Lỗi file upload!']);
            }
            exit;
        }

        // E. LƯU CHÍNH THỨC (SAU KHI CONFIRM)
        if ($action == 'save_import_data') {
            $exam_id = $_POST['exam_id'];
            $questions_json = $_POST['questions_data'];
            $questions = json_decode($questions_json, true);

            if (empty($questions)) {
                echo json_encode(['status' => 'error', 'message' => 'Dữ liệu rỗng!']); exit;
            }

            try {
                $conn->beginTransaction();
                $count = 0;
                foreach ($questions as $q) {
                    $stmt = $conn->prepare("INSERT INTO questions (exam_id, content) VALUES (?, ?)");
                    $stmt->execute([$exam_id, $q['content']]);
                    $qid = $conn->lastInsertId();
                    
                    $stmt_ans = $conn->prepare("INSERT INTO answers (question_id, content, is_correct) VALUES (?, ?, ?)");
                    foreach ($q['answers'] as $ans) {
                        $stmt_ans->execute([$qid, $ans['text'], $ans['correct']]);
                    }
                    $count++;
                }
                $conn->commit();
                echo json_encode(['status' => 'success', 'message' => "Đã lưu thành công $count câu hỏi!"]);
            } catch (Exception $e) {
                $conn->rollBack();
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
        }

    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// --- 3. XỬ LÝ FORM THÊM THỦ CÔNG (NON-AJAX) ---
$alertScript = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['ajax_action']) && isset($_POST['action']) && $_POST['action'] == 'manual') {
    // ... (Code thêm thủ công giữ nguyên như cũ) ...
    $exam_id = $_POST['exam_id'];
    $content = $_POST['content'];
    $answers = $_POST['answer']; 
    $correct = $_POST['correct'];
    try {
        $conn->beginTransaction();
        $stmt = $conn->prepare("INSERT INTO questions (exam_id, content) VALUES (?, ?)");
        $stmt->execute([$exam_id, $content]);
        $qid = $conn->lastInsertId();
        $stmt_ans = $conn->prepare("INSERT INTO answers (question_id, content, is_correct) VALUES (?, ?, ?)");
        foreach($answers as $idx => $ans) {
            $is_true = ($idx == $correct) ? 1 : 0;
            $stmt_ans->execute([$qid, $ans, $is_true]);
        }
        $conn->commit();
        $alertScript = "Swal.fire('Thành công', 'Đã thêm câu hỏi mới!', 'success');";
    } catch (Exception $e) { $conn->rollBack(); $alertScript = "Swal.fire('Lỗi', '".$e->getMessage()."', 'error');"; }
}

// --- 4. LẤY DANH SÁCH HIỂN THỊ ---
$filter_exam = $_GET['filter_exam'] ?? '';
$sql_list = "SELECT q.*, e.title as exam_title FROM questions q JOIN exams e ON q.exam_id = e.id";
if ($filter_exam) $sql_list .= " WHERE q.exam_id = $filter_exam";
$sql_list .= " ORDER BY q.id DESC";
$questions_list = $conn->query($sql_list)->fetchAll();
$exams = $conn->query("SELECT * FROM exams")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Quản lý câu hỏi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/exam_web/assets/css/style.css">
    <link rel="stylesheet" href="/exam_web/assets/css/questions.css">
    <style>
        /* CSS cho phần Preview Chỉnh Sửa */
        #preview-section { display: none; margin-top: 20px; border-top: 2px dashed #444; padding-top: 20px; }
        .preview-item { background: rgba(255,255,255,0.05); padding: 15px; border-radius: 8px; margin-bottom: 15px; border: 1px solid rgba(255,255,255,0.1); }
        .preview-item:hover { border-color: rgba(255,255,255,0.3); }
        
        .btn-group { display: flex; gap: 10px; margin-top: 15px; }
        
        /* Style cho input trong preview */
        .q-input { width: 100%; background: rgba(0,0,0,0.3); border: 1px solid #444; color: #fff; padding: 8px; border-radius: 4px; font-weight: bold; }
        .ans-input { width: 100%; background: rgba(0,0,0,0.2); border: 1px solid #444; color: #ccc; padding: 6px; border-radius: 4px; }
        
        .q-input:focus, .ans-input:focus { border-color: var(--primary); outline: none; background: rgba(0,0,0,0.5); }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <h1>Ngân hàng câu hỏi</h1>

            <div class="card" style="margin-bottom: 30px;">
                <div class="tab-header">
                    <button class="tab-btn active" onclick="switchTab('manual')"><i class="fa-solid fa-pen"></i> Nhập tay</button>
                    <button class="tab-btn" onclick="switchTab('import')"><i class="fa-solid fa-file-word"></i> Nhập từ Word</button>
                </div>

                <!-- TAB NHẬP TAY -->
                <div id="manual" class="tab-content active">
                    <form method="POST">
                        <input type="hidden" name="action" value="manual">
                        <!-- ... (Form nhập tay giữ nguyên như cũ) ... -->
                        <div class="grid-3" style="grid-template-columns: 1fr 2fr;">
                            <div class="form-group">
                                <label>Chọn đề thi:</label>
                                <select name="exam_id" class="form-control" style="background: rgba(0,0,0,0.3); color: #fff;" required>
                                    <?php foreach($exams as $ex): ?>
                                        <option value="<?php echo $ex['id']; ?>"><?php echo $ex['title']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Nội dung câu hỏi:</label>
                                <input type="text" name="content" class="form-control" placeholder="Nhập câu hỏi..." required>
                            </div>
                        </div>
                        <label>Đáp án (Tích chọn đáp án đúng):</label>
                        <div class="grid-3" style="grid-template-columns: 1fr 1fr;">
                            <?php for($i=0; $i<4; $i++): ?>
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                <input type="radio" name="correct" value="<?php echo $i; ?>" <?php echo ($i==0)?'checked':''; ?>>
                                <input type="text" name="answer[]" class="form-control" placeholder="Đáp án <?php echo $i+1; ?>" required>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top: 15px;">Thêm ngay</button>
                    </form>
                </div>

                <!-- TAB IMPORT TỪ WORD (ĐÃ NÂNG CẤP PREVIEW) -->
                <div id="import" class="tab-content">
                    <!-- Khu vực Form Upload -->
                    <div id="upload-form-area">
                        <form id="formPreview" onsubmit="handlePreview(event)" enctype="multipart/form-data">
                            <input type="hidden" name="ajax_action" value="preview_import">
                            <div class="grid-3" style="align-items: end;">
                                <div class="form-group">
                                    <label>Chọn đề thi để import vào:</label>
                                    <select id="import_exam_id" class="form-control" style="background: rgba(0,0,0,0.3); color: #fff;" required>
                                        <?php foreach($exams as $ex): ?>
                                            <option value="<?php echo $ex['id']; ?>"><?php echo $ex['title']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>File Word (.docx):</label>
                                    <input type="file" name="file_word" class="form-control" accept=".docx" required>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-warning" style="width: 100%; color: #000;">
                                        <i class="fa-solid fa-eye"></i> Xem trước câu hỏi
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Khu vực Hiển thị Preview (Ẩn mặc định) -->
                    <div id="preview-section">
                        <h3 style="color: var(--primary);">Kết quả đọc file: <span id="preview-count">0</span> câu hỏi</h3>
                        <p style="font-size: 0.9rem; margin-bottom: 15px; color: #aaa;">
                            <i class="fa-solid fa-circle-info"></i> Kiểm tra và chỉnh sửa nội dung trực tiếp tại đây trước khi lưu.
                        </p>
                        
                        <div id="preview-list" style="max-height: 500px; overflow-y: auto; margin-bottom: 15px; padding-right: 5px;">
                            <!-- JS sẽ render INPUT vào đây -->
                        </div>

                        <div class="btn-group">
                            <button class="btn btn-danger" onclick="cancelPreview()">Hủy / Upload lại</button>
                            <button class="btn btn-primary" onclick="confirmImport()">
                                <i class="fa-solid fa-save"></i> Xác nhận lưu vào hệ thống
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Danh sách hiển thị bên dưới giữ nguyên -->
            <h2 style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
                Danh sách câu hỏi
                <!-- ... Filter Form ... -->
            </h2>
            
            <!-- ... Table danh sách câu hỏi ... -->
             <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Nội dung câu hỏi & Đáp án</th>
                            <th style="width: 150px;">Đề thi</th>
                            <th style="width: 100px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($questions_list) > 0): ?>
                            <?php foreach($questions_list as $q): ?>
                            <tr id="row-<?php echo $q['id']; ?>">
                                <td style="vertical-align: top; padding-top: 15px;">#<?php echo $q['id']; ?></td>
                                <td>
                                    <div style="color: #fff; font-weight: 500; font-size: 1rem; margin-bottom: 5px;">
                                        <?php echo $q['content']; ?>
                                    </div>
                                    <?php 
                                        $ans_stmt = $conn->prepare("SELECT * FROM answers WHERE question_id = ?");
                                        $ans_stmt->execute([$q['id']]);
                                        $answers = $ans_stmt->fetchAll();
                                    ?>
                                    <div class="ans-list">
                                        <?php foreach($answers as $ans): 
                                            $is_true = $ans['is_correct'] == 1;
                                        ?>
                                            <div class="ans-item <?php echo $is_true ? 'correct' : ''; ?>">
                                                <?php echo ($is_true ? '<i class="fa-solid fa-circle-check"></i>' : '<i class="fa-regular fa-circle"></i>') . " " . $ans['content']; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td style="vertical-align: top; padding-top: 15px;">
                                    <span class="status-badge status-active"><?php echo $q['exam_title']; ?></span>
                                </td>
                                <td style="vertical-align: top; padding-top: 15px; text-align: center;">
                                    <!-- Buttons Edit/Delete -->
                                     <button type="button" class="btn" onclick="deleteQuestion(<?php echo $q['id']; ?>)" style="padding: 6px 10px; background: rgba(244, 63, 94, 0.2); color: #f43f5e;">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <!-- Edit Modal giữ nguyên -->
    <!-- ... -->

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        // --- XỬ LÝ PREVIEW CHỈNH SỬA ---
        function handlePreview(e) {
            e.preventDefault();
            const form = document.getElementById('formPreview');
            const formData = new FormData(form);

            Swal.fire({ title: 'Đang đọc file...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() } });

            fetch('', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                Swal.close();
                if (data.status === 'success') {
                    // Render UI cho phép chỉnh sửa
                    document.getElementById('preview-count').innerText = data.count;
                    const listDiv = document.getElementById('preview-list');
                    listDiv.innerHTML = '';

                    data.data.forEach((q, index) => {
                        let ansHtml = '';
                        q.answers.forEach((ans, ansIndex) => {
                            const isChecked = ans.correct == 1 ? 'checked' : '';
                            ansHtml += `
                                <div class="ans-row" style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                    <input type="radio" name="correct_${index}" value="${ansIndex}" ${isChecked} style="accent-color: #4ade80; width: 18px; height: 18px; cursor: pointer;" title="Chọn đáp án đúng">
                                    <input type="text" class="form-control ans-input" value="${ans.text}" placeholder="Đáp án ${ansIndex + 1}">
                                </div>
                            `;
                        });
                        
                        listDiv.innerHTML += `
                            <div class="preview-item" id="preview-item-${index}">
                                <div style="display:flex; justify-content:space-between; margin-bottom: 5px;">
                                    <label style="color: #4ade80; font-weight: bold;">Câu ${index + 1}:</label>
                                    <button type="button" onclick="removePreviewItem('preview-item-${index}')" style="background:none; border:none; color: #f43f5e; cursor: pointer; font-size: 0.9rem;">
                                        <i class="fa-solid fa-trash"></i> Xóa câu này
                                    </button>
                                </div>
                                <textarea class="form-control q-input" rows="2" style="margin-bottom: 10px;" placeholder="Nội dung câu hỏi">${q.content}</textarea>
                                <div class="ans-container">
                                    ${ansHtml}
                                </div>
                            </div>
                        `;
                    });

                    // Chuyển màn hình
                    document.getElementById('upload-form-area').style.display = 'none';
                    document.getElementById('preview-section').style.display = 'block';
                } else {
                    Swal.fire('Lỗi', data.message, 'error');
                }
            })
            .catch(err => Swal.fire('Lỗi', 'Không thể kết nối server', 'error'));
        }

        function removePreviewItem(elementId) {
            document.getElementById(elementId).remove();
            // Cập nhật lại số lượng đếm
            const count = document.querySelectorAll('.preview-item').length;
            document.getElementById('preview-count').innerText = count;
        }

        function cancelPreview() {
            document.getElementById('preview-list').innerHTML = '';
            document.getElementById('preview-section').style.display = 'none';
            document.getElementById('upload-form-area').style.display = 'block';
            document.getElementById('formPreview').reset();
        }

        function confirmImport() {
            // Lấy dữ liệu từ DOM (những gì người dùng đã sửa)
            const items = document.querySelectorAll('.preview-item');
            if (items.length === 0) {
                Swal.fire('Lỗi', 'Không có câu hỏi nào để lưu!', 'warning');
                return;
            }

            const finalData = [];
            let isValid = true;

            items.forEach((item, index) => {
                const content = item.querySelector('.q-input').value.trim();
                if(!content) {
                    Swal.fire('Lỗi', `Câu hỏi số ${index + 1} đang bị trống nội dung!`, 'warning');
                    isValid = false;
                    return;
                }

                const answers = [];
                const ansRows = item.querySelectorAll('.ans-row');
                let hasCorrect = false;

                ansRows.forEach(row => {
                    const ansText = row.querySelector('.ans-input').value.trim();
                    const isCorrect = row.querySelector('input[type="radio"]').checked ? 1 : 0;
                    if(isCorrect) hasCorrect = true;
                    
                    answers.push({
                        text: ansText,
                        correct: isCorrect
                    });
                });

                if(!hasCorrect) {
                    Swal.fire('Lỗi', `Câu số ${index + 1} chưa chọn đáp án đúng!`, 'warning');
                    isValid = false;
                    return;
                }

                finalData.push({
                    content: content,
                    answers: answers
                });
            });

            if (!isValid) return;

            // Gửi dữ liệu đã gom được lên server
            const examId = document.getElementById('import_exam_id').value;
            const formData = new FormData();
            formData.append('ajax_action', 'save_import_data');
            formData.append('exam_id', examId);
            formData.append('questions_data', JSON.stringify(finalData));

            Swal.fire({ title: 'Đang lưu...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() } });

            fetch('', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Thành công', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Lỗi', data.message, 'error');
                }
            });
        }

        // --- Các hàm Delete/Edit giữ nguyên như cũ ---
        function deleteQuestion(id) {
             Swal.fire({
                title: 'Bạn chắc chắn chứ?',
                text: "Câu hỏi này sẽ bị xóa vĩnh viễn!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f43f5e',
                cancelButtonColor: '#334155',
                confirmButtonText: 'Vâng, xóa đi!',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('ajax_action', 'delete_question');
                    formData.append('id', id);

                    fetch('', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if(data.status === 'success') {
                            document.getElementById('row-' + id).remove();
                            Swal.fire('Đã xóa!', data.message, 'success');
                        } else {
                            Swal.fire('Lỗi!', data.message, 'error');
                        }
                    });
                }
            })
        }
        
        <?php if($alertScript) echo $alertScript; ?>
    </script>
</body>
</html>