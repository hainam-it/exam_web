<?php
// questions.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// --- 1. XỬ LÝ AJAX (Xóa, Lấy dữ liệu sửa, Lưu sửa) ---
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

    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// --- 2. HÀM ĐỌC WORD ---
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

// --- 3. XỬ LÝ FORM THÊM MỚI ---
$alertScript = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['ajax_action'])) {
    if (isset($_POST['action']) && $_POST['action'] == 'manual') {
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
    elseif (isset($_POST['action']) && $_POST['action'] == 'import_word') {
        $exam_id = $_POST['exam_id'];
        if (isset($_FILES['file_word']) && $_FILES['file_word']['error'] == 0) {
            $ext = pathinfo($_FILES['file_word']['name'], PATHINFO_EXTENSION);
            if ($ext != 'docx') {
                $alertScript = "Swal.fire('Lỗi', 'Chỉ hỗ trợ file .docx!', 'error');";
            } else {
                $text = read_docx($_FILES['file_word']['tmp_name']);
                $lines = explode("\n", $text);
                $questions_data = []; $current_q = [];
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    if (preg_match('/^(\*)?([A-D])\./i', $line, $matches)) {
                        $is_correct = ($matches[1] == '*') ? 1 : 0;
                        $ans_text = trim(preg_replace('/^(\*)?([A-D])\./i', '', $line));
                        $current_q['answers'][] = ['text' => $ans_text, 'correct' => $is_correct];
                    } else {
                        if (!empty($current_q) && isset($current_q['answers']) && count($current_q['answers']) >= 2) {
                            $questions_data[] = $current_q; $current_q = [];
                        }
                        $current_q['content'] = empty($current_q['content']) ? $line : $current_q['content'] . " " . $line;
                    }
                }
                if (!empty($current_q) && isset($current_q['answers'])) $questions_data[] = $current_q;

                if (count($questions_data) > 0) {
                    try {
                        $conn->beginTransaction();
                        $count = 0;
                        foreach ($questions_data as $q) {
                            $stmt = $conn->prepare("INSERT INTO questions (exam_id, content) VALUES (?, ?)");
                            $stmt->execute([$exam_id, $q['content']]);
                            $qid = $conn->lastInsertId();
                            $stmt_ans = $conn->prepare("INSERT INTO answers (question_id, content, is_correct) VALUES (?, ?, ?)");
                            foreach ($q['answers'] as $ans) $stmt_ans->execute([$qid, $ans['text'], $ans['correct']]);
                            $count++;
                        }
                        $conn->commit();
                        $alertScript = "Swal.fire('Thành công', 'Đã import $count câu hỏi!', 'success');";
                    } catch (Exception $e) { $conn->rollBack(); $alertScript = "Swal.fire('Lỗi', '".$e->getMessage()."', 'error');"; }
                } else { $alertScript = "Swal.fire('Lỗi', 'File rỗng hoặc sai định dạng!', 'error');"; }
            }
        }
    }
}

// --- 4. LẤY DANH SÁCH ---
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
</head>
<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <h1 style="margin-bottom: 20px;">Ngân hàng câu hỏi</h1>

            <div class="card" style="margin-bottom: 30px;">
                <div class="tab-header">
                    <button class="tab-btn active" onclick="switchTab('manual')"><i class="fa-solid fa-pen"></i> Nhập tay</button>
                    <button class="tab-btn" onclick="switchTab('import')"><i class="fa-solid fa-file-word"></i> Nhập từ Word</button>
                </div>

                <div id="manual" class="tab-content active">
                    <form method="POST">
                        <input type="hidden" name="action" value="manual">
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
                        <label style="margin-bottom: 10px; display: block; font-size: 0.9rem; color: var(--text-light);">Đáp án (Tích chọn đáp án đúng):</label>
                        <div class="grid-3" style="grid-template-columns: 1fr 1fr;">
                            <?php for($i=0; $i<4; $i++): ?>
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                <input type="radio" name="correct" value="<?php echo $i; ?>" <?php echo ($i==0)?'checked':''; ?> style="accent-color: var(--primary);">
                                <input type="text" name="answer[]" class="form-control" placeholder="Đáp án <?php echo $i+1; ?>" required>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top: 15px;">Thêm ngay</button>
                    </form>
                </div>

                <div id="import" class="tab-content">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="import_word">
                        <div class="grid-3" style="align-items: end;">
                            <div class="form-group">
                                <label>Chọn đề thi:</label>
                                <select name="exam_id" class="form-control" style="background: rgba(0,0,0,0.3); color: #fff;" required>
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
                                <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fa-solid fa-upload"></i> Upload</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <h2 style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
                Danh sách câu hỏi
                <form method="GET" style="display: flex; gap: 10px;">
                    <select name="filter_exam" class="form-control" style="padding: 8px; width: 200px; font-size: 0.9rem;" onchange="this.form.submit()">
                        <option value="">-- Tất cả đề thi --</option>
                        <?php foreach($exams as $ex): ?>
                            <option value="<?php echo $ex['id']; ?>" <?php echo ($filter_exam == $ex['id']) ? 'selected' : ''; ?>>
                                <?php echo $ex['title']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </h2>

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
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <button type="button" class="btn" onclick="openEditModal(<?php echo $q['id']; ?>)" style="padding: 6px 10px; background: rgba(59, 130, 246, 0.2); color: #3b82f6;">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <button type="button" class="btn" onclick="deleteQuestion(<?php echo $q['id']; ?>)" style="padding: 6px 10px; background: rgba(244, 63, 94, 0.2); color: #f43f5e;">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center;">Chưa có câu hỏi nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div id="editModal" class="modal-overlay">
        <div class="modal-box">
            <span class="modal-close" onclick="closeEditModal()">&times;</span>
            <div class="modal-title">Chỉnh sửa câu hỏi</div>
            <form id="editForm" onsubmit="submitEdit(event)">
                <input type="hidden" name="ajax_action" value="update_question">
                <input type="hidden" name="q_id" id="edit_q_id">
                
                <div class="form-group">
                    <label>Đề thi:</label>
                    <select name="exam_id" id="edit_exam_id" class="form-control" required>
                        <?php foreach($exams as $ex): ?>
                            <option value="<?php echo $ex['id']; ?>"><?php echo $ex['title']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Nội dung câu hỏi:</label>
                    <input type="text" name="content" id="edit_content" class="form-control" required>
                </div>

                <label>Đáp án:</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <?php for($i=0; $i<4; $i++): ?>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <input type="radio" name="correct" value="<?php echo $i; ?>" id="edit_radio_<?php echo $i; ?>">
                        <input type="text" name="answer[]" id="edit_ans_<?php echo $i; ?>" class="form-control" placeholder="Đáp án <?php echo $i+1; ?>" required>
                    </div>
                    <?php endfor; ?>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;">Lưu thay đổi</button>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        <?php if($alertScript) echo $alertScript; ?>

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

        function openEditModal(id) {
            const formData = new FormData();
            formData.append('ajax_action', 'get_question_detail');
            formData.append('id', id);

            fetch('', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    document.getElementById('edit_q_id').value = data.question.id;
                    document.getElementById('edit_content').value = data.question.content;
                    document.getElementById('edit_exam_id').value = data.question.exam_id;
                    
                    data.answers.forEach((ans, index) => {
                        if(index < 4) {
                            document.getElementById('edit_ans_' + index).value = ans.content;
                            if(ans.is_correct == 1) {
                                document.getElementById('edit_radio_' + index).checked = true;
                            }
                        }
                    });
                    document.getElementById('editModal').classList.add('open');
                }
            });
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('open');
        }

        function submitEdit(e) {
            e.preventDefault();
            const form = document.getElementById('editForm');
            const formData = new FormData(form);

            fetch('', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    Swal.fire('Thành công', 'Đã cập nhật câu hỏi!', 'success')
                    .then(() => {
                        location.reload(); 
                    });
                } else {
                    Swal.fire('Lỗi', data.message, 'error');
                }
            });
        }
    </script>
</body>
</html>