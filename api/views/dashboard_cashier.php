<?php
if (!isset($_COOKIE['role'])) {
    // If no authorization cookies exist, bounce them instantly to the login gateway
    header("Location: ../login.php");
    exit;
} elseif ($_COOKIE['role'] !== 'cashier') {
    // If they are an owner or finance, dynamically redirect them to their correct home base
    header("Location: ../views/dashboard_" . $_COOKIE['role'] . ".php");
    exit;
}

// Secure database connection
require_once dirname(__DIR__) . '/config/database.php';

// Pull user traits straight out of the active browser cookies keyring
$user_id = $_COOKIE['user_id'];
$hari_ini = date('Y-m-d');

try {
    // 🌟 THE CRUCIAL FIX: Read and format from 'tanggal' instead of the empty 'created_at' column
    // We use LIKE to match any timestamp starting with today's date (e.g., '2026-05-24%')
    $sql_logs = "SELECT DATE_FORMAT(tanggal, '%H:%i') as waktu, keterangan, jenis_transaksi, nominal 
                 FROM transaksi 
                 WHERE user_id = ? AND tanggal LIKE ? 
                 ORDER BY id DESC";
    
    $stmt_logs = $pdo->prepare($sql_logs);
    $stmt_logs->execute([$user_id, $hari_ini . '%']);
    $logs_penjualan = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Gagal memuat log transaksi harian: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Desk - BukuKasPro</title>
    
    <link rel="stylesheet" href="../assets/css/cashier.css">
</head>
<body class="cashier-body">

    <nav class="cashier-nav">
        <div class="cashier-nav-wrapper">
            <div style="display:flex; align-items:center; gap:0.5rem; font-weight:900; font-size:1.25rem;">
                <span>BukuKas<span style="color:#4f46e5;">Pro</span></span>
                <span style="font-size:0.7rem; background-color:#ccfbf1; color:#115e59; padding:0.25rem 0.5rem; border-radius:0.5rem;">Cashier Desk</span>
            </div>
            <div style="display:flex; align-items:center; gap:1.5rem; font-size:0.875rem;">
                <span style="color:#475569;">Petugas Kasir: <b style="color:#0f172a;"><?= htmlspecialchars($_COOKIE['nama'] ?? 'Amirah Adzra'); ?></b></span>
                <a href="../proses/proses_logout.php" class="btn-cashier-submit" style="padding:0.5rem 1rem; margin:0; text-decoration:none; font-size:0.85rem; width: auto;">Keluar Toko</a>
            </div>
        </div>
    </nav>

    <main style="max-width: 80rem; margin: 2rem auto; padding: 0 2rem;">
        
        <div class="security-alert">
            🔒 <b>Kebijakan Keamanan Sistem:</b> Hak akses Anda dibatasi untuk input penjualan harian saja. Anda dilarang memodifikasi, menghapus data lama, atau melihat grafik finansial pusat. Jika ada salah masukan data, hubungi <b>Owner</b> untuk prosedur pembatalan (*approval correction*).
        </div>

        <div class="cashier-workspace-grid">
            
            <div class="panel-form-wide cashier-card">
                <h3 class="cashier-card-title">🛒 Input Penjualan Toko Harian</h3>
                
                <form action="../proses/proses_transaksi.php" method="POST" enctype="multipart/form-data" class="cashier-form-stack">
                    <input type="hidden" name="redirect_to" value="dashboard_cashier.php">

                    <div class="cashier-field-group">
                        <label class="cashier-label">Jenis Transaksi</label>
                        <select name="jenis_transaksi" class="cashier-input">
                            <option value="pemasukan">Pemasukan (Uang Kas Masuk)</option>
                            <option value="pengeluaran">Pengeluaran (Uang Kas Keluar)</option>
                        </select>
                    </div>

                    <div class="cashier-field-group">
                        <label class="cashier-label">Total Nilai Penjualan (Rp)</label>
                        <input type="number" name="nominal" required placeholder="Contoh: 150000" class="cashier-input">
                    </div>

                    <div class="cashier-field-group">
                        <label class="cashier-label">Tanggal Hari Ini</label>
                        <input type="date" name="tanggal" value="<?= date('Y-m-d'); ?>" required class="cashier-input">
                    </div>

                    <div class="cashier-field-group">
                        <label class="cashier-label">Pos Finansial</label>
                        <select name="kategori" class="cashier-input">
                            <option value="Penjualan">Penjualan Produk Lapangan</option>
                            <option value="Operasional">Biaya Operasional Kecil</option>
                        </select>
                    </div>

                    <div class="cashier-field-group">
                        <label class="cashier-label">Keterangan / Item Terjual</label>
                        <input type="text" name="keterangan" placeholder="Contoh: Penjualan baju kaos 3 pcs" class="cashier-input">
                    </div>

                    <div class="cashier-field-group">
                        <label class="cashier-label">Upload Nota / Struk Digital (Opsional)</label>
                        <input type="file" name="nota" accept="image/*" class="cashier-input" style="padding: 0.5rem; background: #ffffff;">
                        <span style="font-size: 0.7rem; color: #64748b; margin-top: 0.2rem; display: block;">Format: Gambar (JPG, PNG, WebP)</span>
                    </div>

                    <button type="submit" class="btn-cashier-submit">Simpan Transaksi Toko</button>
                </form>
            </div>

            <div class="cashier-card">
                <h3 class="cashier-card-title">📋 Log Input Hari Ini</h3>
                <p style="font-size:0.75rem; color:#64748b; margin-top:-0.5rem; margin-bottom:1rem;">Catatan data murni pada shift kerja ini:</p>
                
                <table class="table-responsive">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Keterangan</th>
                            <th>Total (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs_penjualan)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: #94a3b8; font-size: 0.85rem; padding: 2rem 0;">
                                    Belum ada transaksi yang diinput hari ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($logs_penjualan as $log): ?>
                            <tr>
                                <td style="font-weight:600; font-size:0.8rem; color:#64748b;">
                                    <?= !empty($log['waktu']) ? $log['waktu'] . ' WIB' : '-'; ?>
                                </td>
                                <td><?= htmlspecialchars($log['keterangan'] ?: 'Tanpa keterangan'); ?></td>
                                <td class="<?= $log['jenis_transaksi'] === 'pemasukan' ? 'text-masuk' : 'text-keluar'; ?>">
                                    <?= number_format($log['nominal'], 0, ',', '.'); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

</body>
</html>