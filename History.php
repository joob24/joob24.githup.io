<?php
require_once 'config.php';  // Include koneksi DB

$search_name = '';
$results = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $search_name = trim($_POST['search_name']);
}

// Query untuk fetch history (dengan filter jika ada search)
try {
    $sql = "SELECT tr.id, tr.student_name, cl.name AS class_name, s.name AS subject_name,
            ch.title AS chapter_title, tr.score, tr.test_date
            FROM test_results tr
            JOIN classes cl ON tr.class_id = cl.id
            JOIN subjects s ON tr.subject_id = s.id
            JOIN chapters ch ON tr.chapter_id = ch.id";
    
    $params = [];
    if (!empty($search_name)) {
        $sql .= " WHERE tr.student_name LIKE ?";
        $params[] = '%' . $search_name . '%';
    }
    
    $sql .= " ORDER BY tr.test_date DESC LIMIT 100";  // Limit 100 terbaru
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($results)) {
        $error = 'Tidak ada data ditemukan!';
    }
} catch (PDOException $e) {
    $error = 'Error fetch data: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History Nilai - EduPlay</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Theme CSS (diperbaiki: hilangkan overlay hitam, tambah z-index) -->
    <style>
        :root {
            --primary-color: #3b82f6; /* Biru */
            --secondary-color: #10b981; /* Hijau */
            --accent-color: #f59e0b; /* Kuning */
            --bg-gradient: linear-gradient(135deg, #e0f2fe 0%, #dcfce7 100%);
        }
        body { 
            font-family: 'Comic Sans MS', cursive, sans-serif; 
            background: var(--bg-gradient); 
            color: #1f2937; 
            padding-top: 100px; /* Lebih besar untuk navbar fixed dan hindari overlap */
        }
        .hero { 
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); /* Gradient langsung, tanpa ::before hitam */
            color: white; 
            padding: 60px 0; 
            text-align: center;
            margin-bottom: 2rem; /* Jarak dari content di bawah */
            position: relative; /* Pastikan hero relative */
        }
        .hero-content { 
            position: relative; 
            z-index: 5; /* Tinggi z-index untuk text di hero */
        }
        .text-shadow { text-shadow: 2px 2px 4px rgba(0,0,0,0.7); }
        .main-content { 
            position: relative; 
            z-index: 10; /* Pastikan content utama di atas segalanya */
            min-height: 100vh;
        }
        .table-container { 
            background: rgba(255,255,255,0.95); 
            border-radius: 15px; 
            padding: 2rem; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
            margin: 2rem auto; 
            max-width: 1200px; 
            position: relative; 
            z-index: 10; /* Tinggi untuk table */
        }
        .search-form { 
            max-width: 400px; 
            margin: 0 auto 2rem; 
            position: relative; 
            z-index: 15; /* Pastikan form search paling atas dan clickable */
            pointer-events: auto; /* Explicitly enable pointer events */
        }
        .search-form .input-group { 
            box-shadow: 0 4px 8px rgba(0,0,0,0.1); 
            border-radius: 10px; 
            overflow: hidden; 
        }
        .btn-primary { background: var(--primary-color); border: none; }
        .btn-primary:hover { background: var(--secondary-color); }
        .score-badge { background: var(--accent-color); color: white; padding: 0.25rem 0.5rem; border-radius: 5px; font-weight: bold; }
        .table { transition: all 0.3s ease; }
        .table-hover tbody tr:hover { background-color: rgba(59, 130, 246, 0.1); transform: scale(1.01); }
        .alert { position: relative; z-index: 10; }
        @media (max-width: 768px) { 
            body { padding-top: 120px; } /* Adjust untuk mobile */
            .table-container { margin: 1rem; padding: 1rem; } 
            .search-form { max-width: 100%; padding: 0 1rem; } 
            .hero { padding: 40px 0; } 
        }
    </style>
</head>
<body>
    <!-- Navbar (sama seperti sebelumnya) -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3" href="index.html">🧠 EduPlay</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.html"><i class="fas fa-home me-1"></i> Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.html#subjects"><i class="fas fa-book me-1"></i> Mata Pelajaran</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section (diperbaiki: tanpa overlay hitam) -->
    <section class="hero">
        <div class="container hero-content">
            <h1 class="display-5 fw-bold mb-4 text-shadow"><i class="fas fa-chart-bar me-2"></i>History Nilai Test</h1>
            <p class="lead mb-0 text-shadow">Lihat hasil belajar siswa dari kuis interaktif EduPlay!</p>
        </div>
    </section>

    <!-- Main Content (diperbaiki: z-index tinggi untuk clickable) -->
    <div class="main-content">
        <div class="container">
            <?php if (!empty($error)): ?>
                <div class="alert alert-warning text-center"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Search Form (diperbaiki: z-index tinggi, clickable) -->
            <form method="POST" class="search-form">
                <div class="input-group">
                    <input type="text" class="form-control" name="search_name" placeholder="Cari berdasarkan nama siswa (contoh: Andi)" value="<?php echo htmlspecialchars($search_name); ?>" required>
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Cari</button>
                </div>
                <?php if (!empty($search_name)): ?>
                    <small class="text-muted d-block mt-1">Hasil pencarian untuk: "<?php echo htmlspecialchars($search_name); ?>"</small>
                <?php endif; ?>
                <?php if (!empty($search_name)): ?>
                    <a href="history.php" class="btn btn-outline-secondary btn-sm mt-2">Hapus Filter</a>
                <?php endif; ?>
            </form>

            <!-- Table Container (diperbaiki: z-index tinggi) -->
            <div class="table-container">
                <?php if (empty($results)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Belum ada data history nilai.</h4>
                        <p class="text-muted">Mulai test kuis untuk melihat hasil di sini!</p>
                        <a href="index.html" class="btn btn-primary">Kembali ke Beranda</a>
                    </div>
                <?php else: ?>
                    <h3 class="mb-4">Daftar Hasil Test (<?php echo count($results); ?> hasil)</h3>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Bab</th>
                                    <th>Skor (dari 100)</th>
                                    <th>Tanggal Test</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['class_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['chapter_title']); ?></td>
                                        <td><span class="score-badge"><?php echo $row['score']; ?></span></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($row['test_date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-4">
                        <a href="index.html" class="btn btn-secondary">Kembali ke Beranda</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JS tambahan untuk pastikan form clickable (opsional, tapi aman) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchForm = document.querySelector('.search-form');
            if (searchForm) {
                searchForm.style.pointerEvents = 'auto';
                searchForm.style.zIndex = '15';
            }
            // Test klik: Console log jika diklik
            const searchInput = document.querySelector('input[name="search_name"]');
            if (searchInput) {
                searchInput.addEventListener('focus', () => console.log('Input search diklik!'));
            }
        });
    </script>
</body>
</html>