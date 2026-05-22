<?php
require_once dirname(__DIR__) . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role     = $_POST['role']; // Captures 'cashier' or 'finance'

    // Simple security validation limiters
    if ($role !== 'cashier' && $role !== 'finance') {
        die("Error: Peran pendaftaran tidak valid.");
    }

    try {
        // Check if username already exists
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check_stmt->execute([$username]);
        if ($check_stmt->fetch()) {
            echo "<script>alert('Username sudah terdaftar!'); window.history.back();</script>";
            exit;
        }

        // Insert new worker profile securely
        $sql = "INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama, $username, $password, $role]);

        echo "<script>alert('Registrasi Berhasil! Silakan Login.'); window.location='../login.php';</script>";
        exit;

    } catch (PDOException $e) {
        die("Gagal mendaftarkan akun baru: " . $e->getMessage());
    }
}
?>