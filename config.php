<?php
// config.php - Koneksi Database
$host = 'localhost';
$dbname = 'eduplay_db';
$username = 'root';  // Ganti jika perlu
$password = '';      // Ganti jika ada password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi DB gagal: " . $e->getMessage());
}
// Fungsi Admin Session (untuk panel admin)
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: admin-login.php');
        exit;
    }
}

function adminLogout() {
    unset($_SESSION['admin_logged_in']);
    session_destroy();
}

// Fungsi generate slug sederhana (untuk chapters)
function generateSlug($title) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    return $slug;
}

?>