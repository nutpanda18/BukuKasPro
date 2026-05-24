<?php
require_once dirname(__DIR__) . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        // Cari data pengguna di database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Verifikasi kecocokan sandi
        if ($user && password_verify($password, $user['password'])) {
            
            // Waktu kedaluwarsa cookie (1 hari)
            $cookie_expire = time() + (86400 * 1); 

            // Simpan kredensial ke dalam Cookie Browser agar bisa diakses di semua folder
            setcookie('user_id', $user['id'], $cookie_expire, "/");
            setcookie('nama', $user['nama'], $cookie_expire, "/");
            setcookie('role', $user['role'], $cookie_expire, "/");

            // Redirect otomatis sesuai peran (role) dari database
            switch ($user['role']) {
                case 'owner':
                    header("Location: ../views/dashboard_owner.php");
                    break;
                case 'finance':
                    header("Location: ../views/dashboard_finance.php");
                    break;
                case 'cashier':
                    header("Location: ../views/dashboard_cashier.php");
                    break;
                default:
                    // Bersihkan cookie jika tidak memiliki peran valid
                    setcookie('user_id', '', time() - 3600, "/");
                    setcookie('nama', '', time() - 3600, "/");
                    setcookie('role', '', time() - 3600, "/");
                    die("Error: Akun Anda tidak memiliki peran akses valid.");
            }
            exit;
            
        } else {
            echo "<script>alert('Gagal! Identitas akun salah.'); window.location='../login.php';</script>";
            exit;
        }
    } catch (PDOException $e) {
        die("Proses login bermasalah: " . $e->getMessage());
    }
} else {
    echo "Akses ditolak.";
}
?>