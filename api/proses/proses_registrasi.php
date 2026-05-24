<?php
require_once dirname(__DIR__) . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    try {
        // 1. Cek apakah username sudah digunakan
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check_stmt->execute([$username]);
        if ($check_stmt->fetch()) {
            echo "<script>alert('Username sudah terdaftar! Silakan pilih yang lain.'); window.history.back();</script>";
            exit;
        }

        // 2. Hitung jumlah user untuk menentukan peran (role) otomatis
        $stmtCount = $pdo->query("SELECT COUNT(*) FROM users");
        $total_users = $stmtCount->fetchColumn();

        if ($total_users == 0) {
            $role = 'owner';      // Email/User ke-1 otomatis Owner
        } elseif ($total_users == 1) {
            $role = 'finance';    // Email/User ke-2 otomatis Staff Keuangan
        } else {
            $role = 'cashier';    // Email/User ke-3 dst otomatis Kasir
        }

        // 🌟 ADDED FIX FOR TiDB CLUSTERED INDEX: Generate random ID unique integer
        $id_unik = rand(100000, 999999);

        // 3. Simpan user baru ke database (Updated to explicitly inject the id)
        $sql = "INSERT INTO users (id, nama, username, password, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_unik, $nama, $username, $password, $role]);

        echo "<script>alert('Registrasi Berhasil! Peran Anda ditetapkan otomatis sebagai: $role.'); window.location='../login.php';</script>";
        exit;

    } catch (PDOException $e) {
        die("Gagal mendaftarkan akun baru: " . $e->getMessage());
    }
} else {
    echo "Metode pengiriman data tidak valid.";
}
?>