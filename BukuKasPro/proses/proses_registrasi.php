<?php
// Hubungkan dengan file konfigurasi koneksi database PDO milik BukuKasPro
require_once dirname(__DIR__) . '/config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data kiriman dari formulir register.php
    $nama     = trim($_POST['nama']);
    $username = trim($_POST['username']); 
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Amankan enkripsi password

    try {
        // 1. HITUNG JUMLAH PENGGUNA RIIL YANG SUDAH TERDAFTAR DI DATABASE (REAL-TIME COUNT)
        $stmt_check = $pdo->query("SELECT COUNT(*) as total FROM users");
        $jumlah_user = (int)$stmt_check->fetch()['total'];

        // 2. LOGIKA OTOMATISASI ROLE BERDASARKAN URUTAN DAFTAR (TANPA INPUT MANUAL USER)
        if ($jumlah_user === 0) {
            // Pendaftar ke-1 (Akun Pertama kali di sistem)
            $role_otomatis = "owner";
            $dashboard_tujuan = "dashboard_owner.php";
        } elseif ($jumlah_user === 1) {
            // Pendaftar ke-2
            $role_otomatis = "finance";
            $dashboard_tujuan = "dashboard_finance.php";
        } else {
            // Pendaftar ke-3 dan seterusnya
            $role_otomatis = "cashier";
            $dashboard_tujuan = "dashboard_cashier.php";
        }

        // 3. MASUKKAN DATA USER BARU KE DATABASE DENGAN ROLE OTOMATISASI SISTEM
        $sql_insert = "INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, ?)";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->execute([$nama, $username, $password, $role_otomatis]);

        // 4. DAPATKAN ID TERAKHIR YANG BARU SAJA DIINSERT UNTUK KEBUTUHAN COOKIE LOGIN INSTAN
        $new_user_id = $pdo->lastInsertId();

        // 5. SUNTIKKAN COOKIES LOGIN OTOMATIS AGAR USER TIDAK PERLU LOGIN ULANG SETELAH DAFTAR
        setcookie('user_id', $new_user_id, time() + (86400 * 30), "/");
        setcookie('role', $role_otomatis, time() + (86400 * 30), "/");
        setcookie('nama', $nama, time() + (86400 * 30), "/");

        // 6. POP-UP ALERT + REDIRECT GERBANG MENUJU DASHBOARD SESUAI HAK AKSES ROLE NYA
        echo "<script type='text/javascript'>
                alert('Pendaftaran Berhasil! Akun Anda otomatis terdaftar dan mendapatkan akses sebagai: " . strtoupper($role_otomatis) . "');
                window.location.href = '../views/" . $dashboard_tujuan . "';
              </script>";
        exit;

    } catch (PDOException $e) {
        // Pop-up penanganan error jika username kembar/duplikat atau terjadi gangguan koneksi database MySQL
        echo "<script type='text/javascript'>
                alert('Gagal Mendaftar: Username atau Email sudah digunakan di sistem!');
                window.history.back();
              </script>";
        exit;
    }
} else {
    // Proteksi pengalihan langsung jika file dicoba diakses tanpa submit formulir POST
    header("Location: ../register.php");
    exit;
}
?>