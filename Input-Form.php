<?php
session_start();  // Mulai session untuk simpan data sementara
require_once 'config.php';  // Include koneksi DB

// Ambil slug subject dari URL
$subject_slug = isset($_GET['subject']) ? htmlspecialchars($_GET['subject']) : '';
$subject_name = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_name = trim($_POST['student_name']);
    $class_id = intval($_POST['class_id']);

    if (empty($student_name)) {
        $error = 'Nama siswa harus diisi!';
    } elseif ($class_id < 1) {
        $error = 'Pilih kelas yang valid!';
    } else {
        // Simpan ke session
        $_SESSION['student_name'] = $student_name;
        $_SESSION['class_id'] = $class_id;
        $_SESSION['subject_slug'] = $subject_slug;

        // Redirect ke halaman pilih bab (placeholder - buat chapters.php nanti)
        header('Location: chapters.php?subject=' . urlencode($subject_slug) . '&class=' . $class_id);
        exit;
    }
}

// Fetch nama subject dari DB
if (!empty($subject_slug)) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM subjects WHERE slug = ?");
        $stmt->execute([$subject_slug]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $subject_name = $result['name'];
        } else {
            $error = 'Mata pelajaran tidak ditemukan!';
        }
    } catch (PDOException $e) {
        $error = 'Error DB: ' . $e->getMessage();
    }
} else {
    $error = 'Parameter subject tidak valid!';
}

// Fetch daftar kelas untuk dropdown
$classes = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM classes ORDER BY grade ASC");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetch kelas: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Data Siswa - EduPlay</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Theme CSS (sama seperti index.html) -->
    <style>
        :root {
            --primary-color: #3b82f6; /* Biru */
            --secondary-color: #10b981; /* Hijau */
            --accent-color: #f59e0b; /* Kuning */
            --bg-gradient: linear-gradient(135deg, #e0f2fe 0%, #dcfce7 100%);
        }
        body { font-family: 'Comic Sans MS', cursive, sans-serif; background: var(--bg-gradient); color: #1f2937; }
        .hero { 
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); 
            color: white; 
            padding: 100px 0; 
            min-height: 100vh; 
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        .hero::before { 
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0, 0, 0, 0.3); z-index: 1; 
        }
        .hero > .container { position: relative; z-index: 2; }
        .hero-content { text-align: center; }
        .text-shadow { text-shadow: 2px 2px 4px rgba(0,0,0,0.7); }
        .form-container { background: rgba(255,255,255,0.95); border-radius: 15px; padding: 2rem; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-width: 500px; margin: 0 auto; }
        .btn-primary { background: var(--primary-color); border: none; }
        .btn-primary:hover { background: var(--secondary-color); }
        @media (max-width: 768px) { .hero { padding: 60px 0; min-height: 100vh; } .form-container { margin: 1rem; padding: 1.5rem; } }
    </style>
</head>
<body>
    <!-- Navbar (sama seperti index.html, tapi link kembali ke index) -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3" href="index.html">🧠 EduPlay</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.html"><i class="fas fa-home me-1"></i> Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="history.php"><i class="fas fa-chart-bar me-1"></i> History Nilai</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section dengan Form -->
    <section class="hero">
        <div class="container hero-content">
            <div class="form-container">
                <h2 class="mb-4 text-shadow"><i class="fas fa-user-plus me-2"></i>Masukkan Data Siswa</h2>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if (empty($subject_name)): ?>
                    <p class="text-warning">Mata pelajaran: Tidak ditemukan</p>
                <?php else: ?>
                    <p class="lead mb-4">Untuk mata pelajaran: <strong><?php echo htmlspecialchars($subject_name); ?></strong></p>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="student_name" class="form-label fw-bold">Nama Siswa <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="student_name" name="student_name" value="<?php echo isset($_POST['student_name']) ? htmlspecialchars($_POST['student_name']) : ''; ?>" required placeholder="Contoh: Andi Susanto">
                    </div>
                    <div class="mb-3">
                        <label for="class_id" class="form-label fw-bold">Kelas <span class="text-danger">*</span></label>
                        <select class="form-select" id="class_id" name="class_id" required>
                            <option value="">Pilih Kelas</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo (isset($_POST['class_id']) && $_POST['class_id'] == $class['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100">Lanjut ke Pilih Bab 🚀</button>
                </form>
                <a href="index.html" class="btn btn-secondary w-100 mt-2">Kembali ke Beranda</a>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>