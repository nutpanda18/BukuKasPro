<?php
// Smart Cookie Security Verification Lookups
if (!isset($_COOKIE['role']) || $_COOKIE['role'] !== 'owner') {
    header("Location: ../login.php");
    exit;
}

require_once dirname(__DIR__) . '/config/database.php';

try {
    // Fetch all workers, excluding the primary business owner accounts
    $stmt = $pdo->prepare("SELECT id, nama, username, role, created_at FROM users WHERE role != 'owner' ORDER BY id DESC");
    $stmt->execute();
    $karyawan = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Gagal mengambil data karyawan: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Karyawan - BukuKasPro</title>
    
    <link rel="stylesheet" href="../assets/css/owner.css">
    <link rel="stylesheet" href="../assets/css/kelola.css">
</head>
<body class="owner-body">

    <nav class="owner-nav">
        <div class="owner-nav-wrapper">
            <div class="owner-logo-area">
                <span>BukuKas<span style="color:#4f46e5;">Pro</span></span>
                <span class="owner-badge">Manajemen Staf</span>
            </div>
            <div class="owner-nav-right">
                <a href="dashboard_owner.php" style="color: #4f46e5; text-decoration: none; font-weight: bold; font-size: 0.9rem;">← Kembali ke Dashboard</a>
            </div>
        </div>
    </nav>

    <main style="max-width: 85rem; margin: 2rem auto; padding: 0 2rem;">
        <div class="owner-block-card" style="background: white; padding: 2rem; border-radius: 1rem;">
            <h3 style="margin: 0 0 0.5rem 0;">👥 Kredensial & Kontrol Akses Anggota Tim</h3>
            <p style="font-size: 0.85rem; color: #64748b; margin: 0;">Lihat, ubah status pembukuan, atau hapus izin penugasan staf operasional Anda di bawah ini:</p>

            <table class="table-users">
                <thead>
                    <tr>
                        <th>Nama Lengkap</th>
                        <th>Username</th>
                        <th>Peran Akses</th>
                        <th>Tanggal Terdaftar</th>
                        <th>Opsi Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($karyawan)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #94a3b8; padding: 3rem 0;">Belum ada akun karyawan terdaftar.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($karyawan as $row): ?>
                        <tr>
                            <td style="font-weight: 600; color: #0f172a;"><?= htmlspecialchars($row['nama']); ?></td>
                            <td style="color: #475569;"><?= htmlspecialchars($row['username']); ?></td>
                            <td>
                                <span class="badge-role <?= $row['role'] === 'cashier' ? 'badge-cashier' : 'badge-finance'; ?>">
                                    <?= $row['role'] === 'cashier' ? 'Cashier' : 'Finance'; ?>
                                </span>
                            </td>
                            <td style="color: #64748b;"><?= date('d M Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <a href="edit_karyawan.php?id=<?= $row['id']; ?>" class="btn-action btn-edit">Ubah</a>
                                <a href="../proses/proses_karyawan.php?action=delete&id=<?= $row['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus akun staf ini secara permanen?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>