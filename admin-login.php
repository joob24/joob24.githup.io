<?php
session_start();
require_once 'config.php';  // Include koneksi (meski tidak dipakai di sini)

// Handle login POST
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Hardcode admin (ganti nanti jika pakai DB)
    if ($username === 'admin' && $password === 'eduplay123') {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin-dashboard.php');
        exit;
    } else {
        $login_error = 'Username atau password salah!';
    }
}

// Jika sudah login, redirect ke dashboard
if (isAdminLoggedIn()) {
    header('Location: admin-dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - EduPlay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3b82f6; --secondary-color: #10b981; --bg-gradient: linear-gradient(135deg, #e0f2fe 0%, #dcfce7 100%);
        }
        body { font-family: 'Comic Sans MS', cursive, sans-serif; background: var(--bg-gradient); padding-top: 50px; }
        .login-container { max-width: 400px; margin: 0 auto; background: white; border-radius: 15px; padding: 2rem; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .btn-primary { background: var(--primary-color); border: none; }
        .btn-primary:hover { background: var(--secondary-color); }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h2 class="text-center mb-4"><i class="fas fa-lock me-2"></i>Login Admin EduPlay</h2>
            <?php if (!empty($login_error)): ?>
                <div class="alert alert-danger"><?php echo $login_error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label fw-bold">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required placeholder="admin">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label fw-bold">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="eduplay123">
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
            <div class="text-center mt-3">
                <small class="text-muted">Demo: admin / eduplay123</small>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>