<?php
if (!isset($_COOKIE['role']) || $_COOKIE['role'] !== 'owner') {
    die("Akses Ditolak! Operasi manajemen memerlukan hak administratif Owner.");
}

require_once dirname(__DIR__) . '/config/database.php';

$action = $_GET['action'] ?? null;

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = $_POST['id'];
    $nama     = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $role     = $_POST['role'];
    $password = $_POST['password'];

    try {
        if (!empty($password)) {
            // Update profile fields including a new encrypted password
            $hashed_pass = password_hash($password, PASSWORD_BCRYPT);
            $sql = "UPDATE users SET nama = ?, username = ?, role = ?, password = ? WHERE id = ? AND role != 'owner'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nama, $username, $role, $hashed_pass, $id]);
        } else {
            // Update profile metrics without touching the existing password field
            $sql = "UPDATE users SET nama = ?, username = ?, role = ? WHERE id = ? AND role != 'owner'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nama, $username, $role, $id]);
        }

        header("Location: ../views/kelola_karyawan.php?update=success");
        exit;
    } catch (PDOException $e) {
        die("Gagal memperbarui profil karyawan: " . $e->getMessage());
    }
} 

if ($action === 'delete') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'owner'");
            $stmt->execute([$id]);
            header("Location: ../views/kelola_karyawan.php?delete=success");
            exit;
        } catch (PDOException $e) {
            die("Gagal menghapus data karyawan: " . $e->getMessage());
        }
    }
}

header("Location: ../views/kelola_karyawan.php");
exit;
?>