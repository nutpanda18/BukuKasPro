<?php
// PROTEKSI KEAMANAN COOKIE AKSES
if (!isset($_COOKIE['role']) || $_COOKIE['role'] !== 'owner') {
    header("Location: ../login.php");
    exit;
}

require_once dirname(__DIR__) . '/config/database.php';

try {
    $stmt = $pdo->query("SELECT tanggal, jenis_transaksi, kategori, nominal, keterangan FROM transaksi ORDER BY tanggal DESC");
    $transaksi = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Gagal memuat data transaksi untuk PDF: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan Keuangan - BukuKasPro</title>
    
    <link rel="stylesheet" href="../assets/css/laporan.css?v=1">
</head>
<body>

    <div class="no-print-bar">
        <span style="color: #4338ca;">📄 Dokumen Laporan Siap Cetak / Simpan ke PDF</span>
        <button onclick="window.print()" class="btn-print">Cetak Dokumen</button>
    </div>

    <div class="header">
        <h1>LAPORAN KEUANGAN UTAMA BUKUKASPRO</h1>
        <p>Penanggung Jawab: <?= htmlspecialchars($_COOKIE['nama'] ?? 'Owner'); ?> | Tanggal Unduh: <?= date('d F Y, H:i'); ?> WIB</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Kategori</th>
                <th>Keterangan</th>
                <th class="text-right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $pemasukan = 0; 
            $pengeluaran = 0;
            foreach ($transaksi as $row): 
                if ($row['jenis_transaksi'] === 'pemasukan') { 
                    $pemasukan += $row['nominal']; 
                } else { 
                    $pengeluaran += $row['nominal']; 
                }
            ?>
            <tr>
                <td><?= date('d M Y', strtotime($row['tanggal'])); ?></td>
                <td class="<?= $row['jenis_transaksi'] === 'pemasukan' ? 'text-success' : 'text-danger'; ?>">
                    <?= ucfirst($row['jenis_transaksi']); ?>
                </td>
                <td><?= htmlspecialchars($row['kategori']); ?></td>
                <td><?= htmlspecialchars($row['keterangan']); ?></td>
                <td class="text-right">Rp <?= number_format($row['nominal'], 0, ',', '.'); ?></td>
            </tr>
            <?php endforeach; ?>
            
            <tr class="row-total">
                <td colspan="4" class="text-right">Total Pemasukan (Uang Masuk):</td>
                <td class="text-right text-success">Rp <?= number_format($pemasukan, 0, ',', '.'); ?></td>
            </tr>
            <tr class="row-total">
                <td colspan="4" class="text-right">Total Pengeluaran (Uang Keluar):</td>
                <td class="text-right text-danger">Rp <?= number_format($pengeluaran, 0, ',', '.'); ?></td>
            </tr>
            <tr class="row-grand-total">
                <td colspan="4" class="text-right">Sisa Saldo Kas Bersih:</td>
                <td class="text-right">Rp <?= number_format($pemasukan - $pengeluaran, 0, ',', '.'); ?></td>
            </tr>
        </tbody>
    </table>

    <script>
        window.onload = function() {
            setTimeout(function() { 
                window.print(); 
            }, 500);
        }
    </script>
</body>
</html>