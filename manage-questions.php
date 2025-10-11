<?php
session_start();
require_once 'config.php';
requireAdminLogin();  // Cek login admin

$chapters = [];
$questions = [];
$selected_chapter_id = isset($_GET['chapter_id']) ? intval($_GET['chapter_id']) : 0;
$success_msg = '';
$error_msg = '';

// Fetch chapters untuk dropdown
try {
    $stmt = $pdo->query("
        SELECT c.id, c.title, s.name AS subject_name, cl.name AS class_name 
        FROM chapters c 
        JOIN subjects s ON c.subject_id = s.id 
        JOIN classes cl ON c.class_id = cl.id 
        ORDER BY s.name, cl.grade, c.title
    ");
    $chapters = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_msg = 'Error fetch chapters: ' . $e->getMessage();
}

// Fetch questions untuk selected chapter
if ($selected_chapter_id > 0 && empty($error_msg)) {
    try {
        $stmt = $pdo->prepare("
            SELECT q.*, c.title AS chapter_title 
            FROM questions q 
            JOIN chapters c ON q.chapter_id = c.id 
            WHERE q.chapter_id = ? 
            ORDER BY q.order_num ASC
        ");
        $stmt->execute([$selected_chapter_id]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_msg = 'Error fetch questions: ' . $e->getMessage();
    }
}

// Handle POST: Tambah question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $chapter_id = intval($_POST['chapter_id']);
    $order_num = intval($_POST['order_num']);
    $question_text = trim($_POST['question_text']);
    $option_a = trim($_POST['option_a']);
    $option_b = trim($_POST['option_b']);
    $option_c = trim($_POST['option_c']);
    $option_d = trim($_POST['option_d']);
    $correct_option = strtoupper(trim($_POST['correct_option']));
    $explanation = trim($_POST['explanation']);

    if (empty($question_text) || empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d)) {
        $error_msg = 'Question text dan semua options harus diisi!';
    } elseif (!in_array($correct_option, ['A', 'B', 'C', 'D'])) {
        $error_msg = 'Correct option harus A, B, C, atau D!';
    } elseif ($order_num < 1 || $order_num > 20) {
        $error_msg = 'Order num harus antara 1-20!';
    } else {
        try {
            // Cek unique order_num per chapter
            $stmt = $pdo->prepare("SELECT id FROM questions WHERE chapter_id = ? AND order_num = ?");
            $stmt->execute([$chapter_id, $order_num]);
            if ($stmt->fetch()) {
                $error_msg = 'Order num ' . $order_num . ' sudah ada di chapter ini! Pilih nomor lain.';
            } else {
                // Cek total questions <=20
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM questions WHERE chapter_id = ?");
                $stmt->execute([$chapter_id]);
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                if ($count >= 20) {
                    $error_msg = 'Chapter ini sudah punya 20 questions. Hapus satu dulu atau edit order!';
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO questions (chapter_id, order_num, question_text, option_a, option_b, option_c, option_d, correct_option, explanation) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$chapter_id, $order_num, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_option, $explanation]);
                    $success_msg = 'Pertanyaan baru berhasil ditambahkan!';
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?chapter_id=' . $chapter_id);
                    exit;
                }
            }
        } catch (PDOException $e) {
            $error_msg = 'Error tambah question: ' . $e->getMessage();
        }
    }
}

// Handle POST: Edit question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['id']);
    $order_num = intval($_POST['order_num']);
    $question_text = trim($_POST['question_text']);
    $option_a = trim($_POST['option_a']);
    $option_b = trim($_POST['option_b']);
    $option_c = trim($_POST['option_c']);
    $option_d = trim($_POST['option_d']);
    $correct_option = strtoupper(trim($_POST['correct_option']));
    $explanation = trim($_POST['explanation']);

    if (empty($question_text) || empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d)) {
        $error_msg = 'Question text dan semua options harus diisi!';
    } elseif (!in_array($correct_option, ['A', 'B', 'C', 'D'])) {
        $error_msg = 'Correct option harus A, B, C, atau D!';
    } elseif ($order_num < 1 || $order_num > 20) {
        $error_msg = 'Order num harus antara 1-20!';
    } else {
        try {
            // Cek unique order_num (kecuali id sendiri)
            $stmt = $pdo->prepare("SELECT id FROM questions WHERE chapter_id = (SELECT chapter_id FROM questions WHERE id = ?) AND order_num = ? AND id != ?");
            $stmt->execute([$id, $order_num, $id]);
            if ($stmt->fetch()) {
                $error_msg = 'Order num ' . $order_num . ' sudah digunakan oleh soal lain di chapter ini!';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE questions SET order_num = ?, question_text = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_option = ?, explanation = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$order_num, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_option, $explanation, $id]);
                $success_msg = 'Pertanyaan berhasil diupdate!';
                header('Location: ' . $_SERVER['PHP_SELF'] . '?chapter_id=' . $selected_chapter_id);
                exit;
            }
        } catch (PDOException $e) {
            $error_msg = 'Error update question: ' . $e->getMessage();
        }
    }
}

// Handle POST: Hapus question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = intval($_POST['id']);
    $chapter_id = intval($_POST['chapter_id']);

    try {
        $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->execute([$id]);
        $success_msg = 'Pertanyaan berhasil dihapus!';
        header('Location: ' . $_SERVER['PHP_SELF'] . '?chapter_id=' . $chapter_id);
        exit;
    } catch (PDOException $e) {
        $error_msg = 'Error hapus question: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pertanyaan - Admin EduPlay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3b82f6; --secondary-color: #10b981; --bg-gradient: linear-gradient(135deg, #e0f2fe 0%, #dcfce7 100%);
        }
        body { font-family: 'Comic Sans MS', cursive, sans-serif; background: var(--bg-gradient); padding-top: 80px; }
        .sidebar { background: rgba(255,255,255,0.95); border-radius: 15px; padding: 2rem; box-shadow: 0 10px 25px rgba(0,0,0,0.1); height: 100vh; position: fixed; left: 0; top: 80px; width: 250px; }
        .main-content { margin-left: 270px; padding: 2rem; }
        .nav-link { color: var(--primary-color); font-weight: bold; transition: color 0.3s; }
        .nav-link:hover, .nav-link.active { color: var(--secondary-color); }
        .table { box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .btn-sm { padding: 0.25rem 0.5rem; }
        .btn-primary { background: var(--primary-color); border: none; }
        .btn-primary:hover { background: var(--secondary-color); }
        .btn-warning { background: #f59e0b; border: none; }
        .btn-danger { background: #ef4444; border: none; }
        .form-floating { margin-bottom: 1rem; }
        .modal-header { background: var(--primary-color); color: white; }
        .alert { border-radius: 10px; }
        .option-input { margin-bottom: 0.5rem; }
        .correct-radio { display: flex; align-items: center; margin-bottom: 0.5rem; }
        .correct-radio input[type="radio"] { margin-right: 0.5rem; transform: scale(1.1); }
        @media (max-width: 768px) { .sidebar { position: relative; height: auto; width: 100%; } .main-content { margin-left: 0; } .table { font-size: 0.875rem; } }
    </style>
</head>
<body>
    <!-- Sidebar Menu -->
    <div class="sidebar">
        <h4 class="text-center mb-4"><i class="fas fa-cog me-2"></i>Admin Panel</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="admin-dashboard.php"><i class="fas fa-dashboard me-2"></i>Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="manage-chapters.php"><i class="fas fa-book me-2"></i>Kelola Bab</a></li>
            <li class="nav-item"><a class="nav-link active" href="manage-questions.php"><i class="fas fa-question-circle me-2"></i>Kelola Pertanyaan</a></li>
            <li class="nav-item"><a class="nav-link text-danger" href="admin-dashboard.php?logout=1"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Success/Error Messages -->
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Form Pilih Chapter & Tambah Question -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Tambah Pertanyaan Baru</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="form-floating mb-3">
                            <select class="form-select" id="chapter_id" name="chapter_id" required onchange="loadQuestions(this.value)">
                                <option value="">Pilih Bab/Chapter</option>
                                <?php foreach ($chapters as $chapter): ?>
                                    <option value="<?php echo $chapter['id']; ?>" <?php echo ($selected_chapter_id == $chapter['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($chapter['subject_name'] . ' - ' . $chapter['class_name'] . ' - ' . $chapter['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="chapter_id">Bab/Chapter</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" id="order_num" name="order_num" min="1" max="20" required placeholder="1">
                            <label for="order_num">Order Num (1-20, unik per chapter)</label>
                        </div>
                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="question_text" name="question_text" placeholder="Teks pertanyaan" required style="height: 100px;"></textarea>
                            <label for="question_text">Teks Pertanyaan</label>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3 option-input">
                                    <input type="text" class="form-control" id="option_a" name="option_a" placeholder="Option A" required>
                                    <label for="option_a">Option A</label>
                                </div>
                                <div class="form-floating mb-3 option-input">
                                    <input type="text" class="form-control" id="option_b" name="option_b" placeholder="Option B" required>
                                    <label for="option_b">Option B</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3 option-input">
                                    <input type="text" class="form-control" id="option_c" name="option_c" placeholder="Option C" required>
                                    <label for="option_c">Option C</label>
                                </div>
                                <div class="form-floating mb-3 option-input">
                                    <input type="text" class="form-control" id="option_d" name="option_d" placeholder="Option D" required>
                                    <label for="option_d">Option D</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Correct Option <span class="text-danger">*</span></label>
                            <div class="correct-radio">
                                <input type="radio" name="correct_option" value="A" id="correct_a" required>
                                <label for="correct_a" class="ms-2">A</label>
                            </div>
                            <div class="correct-radio">
                                <input type="radio" name="correct_option" value="B" id="correct_b" required>
                                <label for="correct_b" class="ms-2">B</label>
                            </div>
                            <div class="correct-radio">
                                <input type="radio" name="correct_option" value="C" id="correct_c" required>
                                <label for="correct_c" class="ms-2">C</label>
                            </div>
                            <div class="correct-radio">
                                <input type="radio" name="correct_option" value="D" id="correct_d" required>
                                <label for="correct_d" class="ms-2">D</label>
                            </div>
                        </div>
                        <div class="form-floating mb-3">
                                                        <textarea class="form-control" id="explanation" name="explanation" placeholder="Penjelasan jawaban benar (opsional)" style="height: 100px;"></textarea>
                            <label for="explanation">Penjelasan (untuk feedback di kuis)</label>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Tambah Pertanyaan</button>
                    </form>
                    <?php if ($selected_chapter_id > 0): ?>
                        <div class="alert alert-info mt-3">
                            <small>Total soal di chapter ini: <?php echo count($questions); ?>/20. Tambah maksimal 20 soal per bab.</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- List Questions (jika chapter dipilih) -->
            <?php if ($selected_chapter_id > 0 && !empty($questions)): ?>
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Daftar Pertanyaan untuk <?php echo htmlspecialchars($questions[0]['chapter_title']); ?> (<?php echo count($questions); ?> soal)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Order</th>
                                        <th>Pertanyaan</th>
                                        <th>Options (Ringkasan)</th>
                                        <th>Benar</th>
                                        <th>Penjelasan</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($questions as $question): ?>
                                        <tr>
                                            <td><?php echo $question['order_num']; ?></td>
                                            <td><?php echo htmlspecialchars(substr($question['question_text'], 0, 50)) . (strlen($question['question_text']) > 50 ? '...' : ''); ?></td>
                                            <td>
                                                <small>A: <?php echo htmlspecialchars(substr($question['option_a'], 0, 15)) . '...'; ?><br>
                                                B: <?php echo htmlspecialchars(substr($question['option_b'], 0, 15)) . '...'; ?><br>
                                                C: <?php echo htmlspecialchars(substr($question['option_c'], 0, 15)) . '...'; ?><br>
                                                D: <?php echo htmlspecialchars(substr($question['option_d'], 0, 15)) . '...'; ?></small>
                                            </td>
                                            <td><span class="badge bg-primary"><?php echo $question['correct_option']; ?></span></td>
                                            <td><?php echo htmlspecialchars(substr($question['explanation'], 0, 30)) . (strlen($question['explanation']) > 30 ? '...' : ''); ?></td>
                                            <td>
                                                <button class="btn btn-warning btn-sm me-2" onclick="editQuestion(<?php echo $question['id']; ?>, <?php echo $question['order_num']; ?>, '<?php echo addslashes($question['question_text']); ?>', '<?php echo addslashes($question['option_a']); ?>', '<?php echo addslashes($question['option_b']); ?>', '<?php echo addslashes($question['option_c']); ?>', '<?php echo addslashes($question['option_d']); ?>', '<?php echo $question['correct_option']; ?>', '<?php echo addslashes($question['explanation']); ?>')">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin hapus pertanyaan ini?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $question['id']; ?>">
                                                    <input type="hidden" name="chapter_id" value="<?php echo $selected_chapter_id; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i> Hapus
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (count($questions) >= 20): ?>
                            <div class="alert alert-warning">
                                <small>Chapter ini sudah penuh (20 soal). Hapus satu untuk tambah baru.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($selected_chapter_id > 0): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Belum ada pertanyaan untuk chapter ini. Tambahkan yang pertama!
                </div>
            <?php else: ?>
                <div class="alert alert-secondary">
                    <i class="fas fa-exclamation-triangle me-2"></i>Pilih bab/chapter di atas untuk melihat/mengelola pertanyaan.
                </div>
            <?php endif; ?>

            <!-- Kembali Button -->
            <div class="text-center mt-4">
                <a href="admin-dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
            </div>
        </div>
    </div>

    <!-- Modal Edit Question -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Pertanyaan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <input type="hidden" name="chapter_id" value="<?php echo $selected_chapter_id; ?>">
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" id="edit_order_num" name="order_num" min="1" max="20" required>
                            <label for="edit_order_num">Order Num (1-20)</label>
                        </div>
                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="edit_question_text" name="question_text" placeholder="Teks pertanyaan" required style="height: 80px;"></textarea>
                            <label for="edit_question_text">Teks Pertanyaan</label>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="edit_option_a" name="option_a" placeholder="Option A" required>
                                    <label for="edit_option_a">Option A</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="edit_option_b" name="option_b" placeholder="Option B" required>
                                    <label for="edit_option_b">Option B</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="edit_option_c" name="option_c" placeholder="Option C" required>
                                    <label for="edit_option_c">Option C</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="edit_option_d" name="option_d" placeholder="Option D" required>
                                    <label for="edit_option_d">Option D</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Correct Option <span class="text-danger">*</span></label>
                            <div class="correct-radio">
                                <input type="radio" name="correct_option" value="A" id="edit_correct_a" required>
                                <label for="edit_correct_a" class="ms-2">A</label>
                            </div>
                            <div class="correct-radio">
                                <input type="radio" name="correct_option" value="B" id="edit_correct_b" required>
                                <label for="edit_correct_b" class="ms-2">B</label>
                            </div>
                            <div class="correct-radio">
                                <input type="radio" name="correct_option" value="C" id="edit_correct_c" required>
                                <label for="edit_correct_c" class="ms-2">C</label>
                            </div>
                            <div class="correct-radio">
                                <input type="radio" name="correct_option" value="D" id="edit_correct_d" required>
                                <label for="edit_correct_d" class="ms-2">D</label>
                            </div>
                        </div>
                        <div class="form-floating">
                            <textarea class="form-control" id="edit_explanation" name="explanation" placeholder="Penjelasan" style="height: 80px;"></textarea>
                            <label for="edit_explanation">Penjelasan (opsional)</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update Pertanyaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Handle Logout -->
    <?php if (isset($_GET['logout'])): ?>
        <?php adminLogout(); ?>
        <script>window.location.href = 'admin-login.php';</script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JS untuk Edit Modal
        function editQuestion(id, order_num, question_text, option_a, option_b, option_c, option_d, correct_option, explanation) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_order_num').value = order_num;
            document.getElementById('edit_question_text').value = question_text;
            document.getElementById('edit_option_a').value = option_a;
            document.getElementById('edit_option_b').value = option_b;
            document.getElementById('edit_option_c').value = option_c;
            document.getElementById('edit_option_d').value = option_d;
            document.getElementById('edit_explanation').value = explanation;
            document.getElementById('edit_correct_' + correct_option.toLowerCase()).checked = true;
            var modal = new bootstrap.Modal(document.getElementById('editModal'));
            modal.show();
        }

        // Validasi form tambah/edit (pastikan correct radio dipilih)
        document.addEventListener('DOMContentLoaded', function() {
            const addForm = document.querySelector('form[action="add"]');
            const editForm = document.querySelector('form[action="edit"]');
            if (addForm) {
                addForm.addEventListener('submit', function(e) {
                    const correct = document.querySelector('input[name="correct_option"]:checked');
                    if (!correct) {
                        e.preventDefault();
                        alert('Pilih correct option (A/B/C/D) dulu!');
                        return false;
                    }
                });
            }
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    const correct = document.querySelector('input[name="correct_option"]:checked');
                    if (!correct) {
                        e.preventDefault();
                        alert('Pilih correct option (A/B/C/D) dulu!');
                        return false;
                    }
                });
            }

            // Auto-load questions saat pilih chapter (reload page via URL)
            const chapterSelect = document.getElementById('chapter_id');
            if (chapterSelect) {
                chapterSelect.addEventListener('change', function() {
                    if (this.value) {
                        window.location.href = '?chapter_id=' + this.value;
                    } else {
                        window.location.href = '?';
                    }
                });
            }
        });
    </script>
</body>
</html>