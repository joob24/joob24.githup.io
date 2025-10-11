<?php
session_start();
require_once 'config.php';
requireAdminLogin();  // Cek login admin

$subjects = [];
$classes = [];
$chapters = [];
$success_msg = '';
$error_msg = '';

// Fetch subjects dan classes untuk dropdown
try {
    $stmt = $pdo->query("SELECT id, name, slug FROM subjects ORDER BY name");
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->query("SELECT id, name FROM classes ORDER BY grade");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_msg = 'Error fetch data: ' . $e->getMessage();
}

// Fetch list chapters
if (empty($error_msg)) {
    try {
        $stmt = $pdo->query("
            SELECT c.*, s.name AS subject_name, cl.name AS class_name 
            FROM chapters c 
            JOIN subjects s ON c.subject_id = s.id 
            JOIN classes cl ON c.class_id = cl.id 
            ORDER BY s.name, cl.grade, c.title
        ");
        $chapters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_msg = 'Error fetch chapters: ' . $e->getMessage();
    }
}

// Handle POST: Tambah chapter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $subject_id = intval($_POST['subject_id']);
    $class_id = intval($_POST['class_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $slug = generateSlug($title);  // Auto-generate slug

    if (empty($title)) {
        $error_msg = 'Title bab harus diisi!';
    } else {
        try {
            // Cek slug unique
            $stmt = $pdo->prepare("SELECT id FROM chapters WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->fetch()) {
                $slug .= '-' . time();  // Tambah timestamp jika duplicate
            }

            $stmt = $pdo->prepare("INSERT INTO chapters (subject_id, class_id, title, slug, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$subject_id, $class_id, $title, $slug, $description]);
            $success_msg = 'Bab baru berhasil ditambahkan!';
            header('Location: ' . $_SERVER['PHP_SELF']);  // Reload untuk lihat list
            exit;
        } catch (PDOException $e) {
            $error_msg = 'Error tambah bab: ' . $e->getMessage();
        }
    }
}

// Handle POST: Edit chapter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $slug = generateSlug($title);

    if (empty($title)) {
        $error_msg = 'Title bab harus diisi!';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE chapters SET title = ?, slug = ?, description = ? WHERE id = ?");
            $stmt->execute([$title, $slug, $description, $id]);
            $success_msg = 'Bab berhasil diupdate!';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } catch (PDOException $e) {
            $error_msg = 'Error update bab: ' . $e->getMessage();
        }
    }
}

// Handle POST: Hapus chapter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = intval($_POST['id']);

    try {
        $stmt = $pdo->prepare("DELETE FROM chapters WHERE id = ?");
        $stmt->execute([$id]);
        $success_msg = 'Bab berhasil dihapus! (Questions juga terhapus otomatis)';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (PDOException $e) {
        $error_msg = 'Error hapus bab: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Bab - Admin EduPlay</title>
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
        .nav-link:hover { color: var(--secondary-color); }
        .main-content { margin-left: 270px; padding: 2rem; }
        .table { box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .btn-sm { padding: 0.25rem 0.5rem; }
        .btn-primary { background: var(--primary-color); border: none; }
        .btn-primary:hover { background: var(--secondary-color); }
        .btn-warning { background: #f59e0b; border: none; }
        .btn-danger { background: #ef4444; border: none; }
        .form-floating { margin-bottom: 1rem; }
        .modal-header { background: var(--primary-color); color: white; }
        .alert { border-radius: 10px; }
        @media (max-width: 768px) { .sidebar { position: relative; height: auto; width: 100%; } .main-content { margin-left: 0; } .table { font-size: 0.875rem; } }
    </style>
</head>
<body>
    <!-- Sidebar Menu (sama seperti dashboard) -->
    <div class="sidebar">
        <h4 class="text-center mb-4"><i class="fas fa-cog me-2"></i>Admin Panel</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="admin-dashboard.php"><i class="fas fa-dashboard me-2"></i>Dashboard</a></li>
            <li class="nav-item"><a class="nav-link active" href="manage-chapters.php"><i class="fas fa-book me-2"></i>Kelola Bab</a></li>
            <li class="nav-item"><a class="nav-link" href="manage-questions.php"><i class="fas fa-question-circle me-2"></i>Kelola Pertanyaan</a></li>
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

            <!-- Form Tambah Chapter -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Tambah Bab Baru</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-select" id="subject_id" name="subject_id" required>
                                        <option value="">Pilih Mata Pelajaran</option>
                                        <?php foreach ($subjects as $subject): ?>
                                            <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="subject_id">Mata Pelajaran</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-select" id="class_id" name="class_id" required>
                                        <option value="">Pilih Kelas</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="class_id">Kelas</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="title" name="title" placeholder="Title Bab" required>
                            <label for="title">Title Bab (e.g., Bab 1: Pengenalan Alam)</label>
                        </div>
                        <div class="form-floating">
                            <textarea class="form-control" id="description" name="description" placeholder="Deskripsi" style="height: 100px;"></textarea>
                            <label for="description">Deskripsi (opsional)</label>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-save me-2"></i>Tambah Bab</button>
                    </form>
                </div>
            </div>

            <!-- List Chapters -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Daftar Bab (<?php echo count($chapters); ?> bab)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($chapters)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-book fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Belum ada bab. Tambahkan yang pertama!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Mata Pelajaran</th>
                                        <th>Kelas</th>
                                        <th>Title</th>
                                        <th>Slug</th>
                                        <th>Deskripsi</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($chapters as $chapter): ?>
                                        <tr>
                                            <td><?php echo $chapter['id']; ?></td>
                                            <td><?php echo htmlspecialchars($chapter['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($chapter['class_name']); ?></td>
                                            <td><?php echo htmlspecialchars($chapter['title']); ?></td>
                                            <td><?php echo htmlspecialchars($chapter['slug']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($chapter['description'], 0, 50)) . (strlen($chapter['description']) > 50 ? '...' : ''); ?></td>
                                            <td>
                                                <button class="btn btn-warning btn-sm me-2" onclick="editChapter(<?php echo $chapter['id']; ?>, '<?php echo addslashes($chapter['title']); ?>', '<?php echo addslashes($chapter['description']); ?>')">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin hapus bab ini? Questions terkait juga akan terhapus!');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $chapter['id']; ?>">
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Chapter -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Bab</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                            <label for="edit_title">Title Bab</label>
                        </div>
                        <div class="form-floating">
                            <textarea class="form-control" id="edit_description" name="description" style="height: 100px;"></textarea>
                            <label for="edit_description">Deskripsi</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update Bab</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Handle Logout (sama seperti dashboard) -->
    <?php if (isset($_GET['logout'])): ?>
        <?php adminLogout(); ?>
        <script>window.location.href = 'admin-login.php';</script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JS untuk Edit Modal dan Auto-Slug
        function editChapter(id, title, description) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_description').value = description;
            var modal = new bootstrap.Modal(document.getElementById('editModal'));
            modal.show();
        }

        // Auto-generate slug saat ketik title (untuk form tambah)
        document.addEventListener('DOMContentLoaded', function() {
            const titleInput = document.getElementById('title');
            if (titleInput) {
                titleInput.addEventListener('input', function() {
                    // Auto-slug (opsional: tampilkan preview)
                    console.log('Slug preview: ' + generateSlug(this.value));  // Bisa tambah field slug preview nanti
                });
            }
        });

        function generateSlug(title) {
            return title.toLowerCase().trim().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-');
        }
    </script>
</body>
</html>