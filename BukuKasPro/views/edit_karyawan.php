<?php
// 1. Validasi Hak Akses Owner
if (!isset($_COOKIE['role']) || $_COOKIE['role'] !== 'owner') {
    header("Location: ../login.php");
    exit;
}

require_once dirname(__DIR__) . '/config/database.php';

$id = $_GET['id'] ?? null;

// Jika ID tidak dikirimkan di URL, langsung balikkan ke tabel lewat Pop-up
if (!$id) {
    echo "<script type='text/javascript'>
            alert('Akses Ilegal: ID Anggota Tim tidak ditentukan!');
            window.location.href = 'kelola_karyawan.php';
          </script>";
    exit;
}

try {
    // 2. Query pencarian data staff
    $stmt = $pdo->prepare("SELECT id, nama, username, role FROM users WHERE id = ? AND role != 'owner'");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "<script type='text/javascript'>
                alert('Gagal Memuat: Profil pekerja dengan ID tersebut tidak ditemukan atau Anda tidak memiliki izin menyunting akun Owner!');
                window.location.href = 'kelola_karyawan.php';
              </script>";
        exit;
    }
    
} catch (PDOException $e) {
    echo "<script type='text/javascript'>
            alert('Database Error: " . addslashes($e->getMessage()) . "');
            window.location.href = 'kelola_karyawan.php';
          </script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Akun Staf - BukuKasPro</title>
    
    <link rel="stylesheet" href="../assets/css/owner.css">
    <link rel="stylesheet" href="../assets/css/edit.css">
</head>
<body class="owner-body">

    <main class="edit-karyawan-container">
        <div class="owner-block-card edit-workspace-card">
            <h3 style="margin-top: 0; margin-bottom: 1.5rem;">✏️ Edit Profil Pekerja</h3>
            
            <form action="../proses/proses_karyawan.php?action=update" method="POST" class="edit-form-stack">
                <input type="hidden" name="id" value="<?= $user['id']; ?>">

                <div class="owner-input-wrapper">
                    <label class="owner-input-label">Nama Lengkap</label>
                    <input type="text" name="nama" value="<?= htmlspecialchars($user['nama']); ?>" required class="owner-input-control">
                </div>

                <div class="owner-input-wrapper">
                    <label class="owner-input-label">Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']); ?>" required class="owner-input-control">
                </div>

                <div class="owner-input-wrapper">
                    <label class="owner-input-label">Peran Pembukuan Baru</label>
                    <select name="role" class="owner-input-control">
                        <option value="cashier" <?= $user['role'] === 'cashier' ? 'selected' : ''; ?>>Staf Kasir (Cashier Desk)</option>
                        <option value="finance" <?= $user['role'] === 'finance' ? 'selected' : ''; ?>>Staf Keuangan (Finance Mode)</option>
                    </select>
                </div>

                <div class="owner-input-wrapper">
                    <label class="owner-input-label">Password Baru <span class="password-note">(Kosongkan jika tidak ingin diubah)</span></label>
                    <input type="password" name="password" placeholder="Masukkan sandi baru..." class="owner-input-control">
                </div>

                <div class="edit-form-actions">
                    <a href="kelola_karyawan.php" class="btn-cancel">Batal</a>
                    <button type="submit" class="btn-owner-submit btn-update-submit">Perbarui Akun</button>
                </div>
            </form>
        </div>
    </main>

</body>
</html>