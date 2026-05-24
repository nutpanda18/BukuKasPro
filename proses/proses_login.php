<?php
// Hubungkan dengan file konfigurasi koneksi database PDO BukuKasPro
require_once dirname(__DIR__) . '/config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        // 1. CARI USER DI DATABASE BERDASARKAN USERNAME ATAU EMAIL
        $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. VERIFIKASI APAKAH USER DITEMUKAN DAN PASSWORD MATCH (ENKRIPSI DECRYPT)
        if ($user && password_verify($password, $user['password'])) {
            
            $role = $user['role'];
            $nama = $user['nama'];
            $user_id = $user['id'];

            // 3. SUNTIKKAN CREDENTIALS KE COOKIES AGAR VALIDASI DASHBOARD BERJALAN SINKRON
            setcookie('user_id', $user_id, time() + (86400 * 30), "/");
            setcookie('role', $role, time() + (86400 * 30), "/");
            setcookie('nama', $nama, time() + (86400 * 30), "/");

            // 4. LOGIKA ROUTING PENGALIHAN MENUJU SUBFOLDER VIEWS/ SECARA AKURAT
            switch ($role) {
                case 'owner':
                    header("Location: ../views/dashboard_owner.php");
                    exit();
                    break;
                    
                case 'finance':
                    header("Location: ../views/dashboard_finance.php");
                    exit();
                    break;
                    
                case 'cashier':
                    header("Location: ../views/dashboard_cashier.php");
                    exit();
                    break;
                    
                default:
                    // Jika role di database di luar 3 role utama
                    echo "<script type='text/javascript'>
                            alert('Akses Ditolak: Hak akses akun Anda tidak dikenali!');
                            window.location.href = '../login.php';
                          </script>";
                    exit();
                    break;
            }

        } else {
            // Pop-up jika kombinasi password salah atau data tidak match di tabel users
            echo "<script type='text/javascript'>
                    alert('Gagal Masuk: Username atau Password Anda salah!');
                    window.history.back();
                  </script>";
            exit();
        }

    } catch (PDOException $e) {
        // Penanganan darurat jika MySQL mati atau crash sewaktu-waktu
        echo "<script type='text/javascript'>
                alert('Gangguan Sistem: Gagal terhubung ke database server!');
                window.history.back();
              </script>";
        exit();
    }
} else {
    // Proteksi direct access URL ilegal
    header("Location: ../login.php");
    exit();
}
?>