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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Keuangan - BukuKasPro</title>
    
    <link rel="stylesheet" href="../assets/css/laporan.css?v=<?= time(); ?>">
</head>
<body>

    <div class="no-print-bar">
        <span>📄 Dokumen Laporan Siap Cetak / Simpan ke PDF</span>
        <button onclick="window.print()" class="btn-print">Cetak Dokumen</button>
    </div>

    <div class="report-wrapper">

        <div class="header">
            <h1>LAPORAN KEUANGAN UTAMA BUKUKASPRO</h1>
            <p>Penanggung Jawab: <b><?= htmlspecialchars($_COOKIE['nama'] ?? 'Owner'); ?></b> | Tanggal Unduh: <?= date('d F Y, H:i'); ?> WIB</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="col-date">Tanggal</th>
                    <th class="col-type">Jenis</th>
                    <th class="col-category">Kategori</th>
                    <th class="col-desc">Keterangan</th>
                    <th class="col-nominal text-right">Nominal</th>
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
                    <td><?= htmlspecialchars($row['keterangan'] ?: '-'); ?></td>
                    <td class="text-right col-nominal">
                        Rp <?= number_format($row['nominal'], 0, ',', '.'); ?>
                    </td>
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

    </div>

    <script>
        window.onload = function() {
            setTimeout(function() { 
                window.print(); 
            }, 800); // Small timeout threshold ensures style metrics complete loading onto browser thread
        }
    </script>
</body>
</html>