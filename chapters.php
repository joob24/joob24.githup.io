<?php
session_start();  // Mulai session
require_once 'config.php';  // Include koneksi DB

// Cek session: Jika nama siswa tidak ada, redirect ke index
if (!isset($_SESSION['student_name']) || empty($_SESSION['student_name'])) {
    header('Location: index.html');
    exit;
}

$student_name = htmlspecialchars($_SESSION['student_name']);
$subject_slug = isset($_GET['subject']) ? htmlspecialchars($_GET['subject']) : '';
$class_id = isset($_GET['class']) ? intval($_GET['class']) : 0;

$subject_name = '';
$chapters = [];
$error = '';

if (empty($subject_slug) || $class_id < 1) {
    $error = 'Parameter subject atau kelas tidak valid! Kembali ke beranda.';
} else {
    // Fetch nama subject
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
        $error = 'Error DB subject: ' . $e->getMessage();
    }

    // Fetch daftar chapters berdasarkan subject dan class
    if (empty($error)) {
        try {
            $stmt = $pdo->prepare("
                SELECT c.id, c.title, c.slug, c.description 
                FROM chapters c 
                JOIN subjects s ON c.subject_id = s.id 
                WHERE s.slug = ? AND c.class_id = ? 
                ORDER BY c.id ASC
            ");
            $stmt->execute([$subject_slug, $class_id]);
            $chapters = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($chapters)) {
                $error = 'Belum ada bab untuk mata pelajaran "' . $subject_name . '" di kelas ini. Silakan pilih mata pelajaran/kelas lain.';
            }
        } catch (PDOException $e) {
            $error = 'Error fetch bab: ' . $e->getMessage();
        }
    }
}

// Fetch nama kelas untuk tampilan
$class_name = '';
if ($class_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $class_name = $result['name'];
        }
    } catch (PDOException $e) {
        // Ignore jika gagal fetch kelas
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Bab - EduPlay</title>
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
        body { font-family: 'Comic Sans MS', cursive, sans-serif; background: var(--bg-gradient); color: #1f2937; padding-top: 100px; }
        .hero { 
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); 
            color: white; 
            padding: 60px 0; 
            text-align: center;
            margin-bottom: 2rem;
        }
        .hero-content { position: relative; z-index: 5; }
        .text-shadow { text-shadow: 2px 2px 4px rgba(0,0,0,0.7); }
        .chapters-section { padding: 2rem 0; }
        .card-hover { 
            transition: transform 0.3s, box-shadow 0.3s; 
            height: 250px; 
            display: flex;
            flex-direction: column;
        }
        .card-hover:hover { 
            transform: translateY(-10px); 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
        }
        .sparkle-anim {
            animation: sparkle 2s ease-in-out infinite;
        }
        @keyframes sparkle {
            0%, 100% { box-shadow: 0 0 5px rgba(59, 130, 246, 0.3), 0 4px 8px rgba(0,0,0,0.1); }
            50% { box-shadow: 0 0 20px rgba(59, 130, 246, 0.8), 0 4px 12px rgba(0,0,0,0.15); }
        }
        .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 1rem;
            text-align: center;
        }
        .card-title { 
            font-size: 1rem; 
            font-weight: bold; 
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }
        .card-text { 
            font-size: 0.875rem; 
            color: #6b7280; 
            margin-bottom: auto;
        }
        .chapter-emoji { font-size: 2rem; margin-bottom: 0.5rem; }
        a.btn { font-size: 0.875rem; padding: 0.5rem 1rem; text-decoration: none; }
        .btn-primary { background: var(--secondary-color); border: none; }
        .btn-primary:hover { background: var(--accent-color); }
        .no-data { text-align: center; padding: 4rem 0; }
        .no-data i { font-size: 4rem; color: #6b7280; margin-bottom: 1rem; }
        @media (max-width: 768px) { 
            body { padding-top: 120px; } 
            .hero { padding: 40px 0; } 
            .card-hover { height: 220px; } 
        }
    </style>
</head>
<body>
    <!-- Navbar -->
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="container hero-content">
            <h1 class="display-5 fw-bold mb-3 text-shadow">Halo, <?php echo $student_name; ?>!</h1>
            <?php if (!empty($error)): ?>
                <div class="alert alert-warning"><?php echo $error; ?></div>
                <a href="index.html" class="btn btn-light">Kembali ke Beranda</a>
            <?php else: ?>
                <p class="lead mb-4 text-shadow">Pilih bab untuk mata pelajaran <strong><?php echo htmlspecialchars($subject_name); ?></strong> di <strong><?php echo htmlspecialchars($class_name); ?></strong></p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Chapters Section -->
    <section class="chapters-section">
        <div class="container">
            <?php if (empty($error) && !empty($chapters)): ?>
                <h2 class="text-center mb-5 fw-bold fs-2 text-primary">Pilih Bab</h2>
                <div class="row g-4">
                    <?php foreach ($chapters as $chapter): ?>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="card card-hover text-center h-100 sparkle-anim">
                                <div class="card-body">
                                    <div class="chapter-emoji">📚</div> <!-- Emoji umum; bisa ganti per bab nanti -->
                                    <h5 class="card-title"><?php echo htmlspecialchars($chapter['title']); ?></h5>
                                    <p class="card-text"><?php echo !empty($chapter['description']) ? htmlspecialchars($chapter['description']) : 'Pelajari materi bab ini melalui kuis seru!'; ?></p>
                                    <a href="test.php?chapter=<?php echo urlencode($chapter['slug']); ?>" class="btn btn-primary">Mulai Test 🚀</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif (empty($error)): ?>
                <!-- No data message sudah di-handle di hero -->
            <?php endif; ?>
            <div class="text-center mt-5">
                <a href="index.html" class="btn btn-secondary">Kembali ke Beranda</a>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>