<?php
session_start();
require_once 'config.php';
requireAdminLogin();  // Cek login admin
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - EduPlay</title>
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
        .card { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .btn-primary { background: var(--primary-color); border: none; }
        .btn-primary:hover { background: var(--secondary-color); }
        @media (max-width: 768px) { .sidebar { position: relative; height: auto; width: 100%; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <!-- Sidebar Menu -->
    <div class="sidebar">
        <h4 class="text-center mb-4"><i class="fas fa-cog me-2"></i>Admin Panel</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="manage-chapters.php"><i class="fas fa-book me-2"></i>Kelola Bab (Chapters)</a></li>
            <li class="nav-item"><a class="nav-link" href="manage-questions.php"><i class="fas fa-question-circle me-2"></i>Kelola Pertanyaan (Questions)</a></li>
            <li class="nav-item"><a class="nav-link text-danger" href="?logout=1"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title"><i class="fas fa-dashboard me-2"></i>Selamat Datang, Admin!</h2>
                            <p class="card-text">Gunakan menu di sidebar untuk mengelola bab dan pertanyaan. Pastikan data lengkap agar kuis berjalan lancar.</p>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body">
                                            <h5><i class="fas fa-book"></i> Kelola Bab</h5>
                                            <p>Tambah/edit/hapus bab untuk mata pelajaran dan kelas.</p>
                                            <a href="manage-chapters.php" class="btn btn-light">Buka</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <h5><i class="fas fa-question-circle"></i> Kelola Pertanyaan</h5>
                                            <p>Tambah/edit/hapus 20 pertanyaan per bab (multiple choice).</p>
                                            <a href="manage-questions.php" class="btn btn-light">Buka</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Handle Logout -->
    <?php if (isset($_GET['logout'])): ?>
        <?php adminLogout(); ?>
        <script>window.location.href = 'admin-login.php';</script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>