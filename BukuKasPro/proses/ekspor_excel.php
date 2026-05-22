<?php
// PROTEKSI KEAMANAN COOKIE AKSES
if (!isset($_COOKIE['role']) || $_COOKIE['role'] !== 'owner') {
    header("Location: ../login.php");
    exit;
}

require_once dirname(__DIR__) . '/config/database.php';

// Konfigurasi Header untuk memaksa unduhan dokumen sebagai file excel (.xls)
$filename = "BukuKasPro_Laporan_" . date('Y-m-d_H-i') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

try {
    // Ambil semua data transaksi diurutkan dari yang terbaru
    $stmt = $pdo->query("SELECT tanggal, jenis_transaksi, kategori, nominal, keterangan FROM transaksi ORDER BY tanggal DESC");
    $transaksi = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Gagal memuat data transaksi untuk ekspor: " . $e->getMessage());
}
?>
<table border="1">
    <thead>
        <tr>
            <th colspan="5" align="center" height="35">
                LAPORAN ARUS KAS UTAMA - BUKUKASPRO
            </th>
        </tr>
        <tr>
            <th>Tanggal</th>
            <th>Jenis Transaksi</th>
            <th>Kategori</th>
            <th>Nominal Bersih (Rp)</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $total_pemasukan = 0;
        $total_pengeluaran = 0;
        
        foreach ($transaksi as $row): 
            if ($row['jenis_transaksi'] === 'pemasukan') {
                $total_pemasukan += $row['nominal'];
            } else {
                $total_pengeluaran += $row['nominal'];
            }
        ?>
        <tr>
            <td><?= date('d M Y', strtotime($row['tanggal'])); ?></td>
            <td><?= ucfirst($row['jenis_transaksi']); ?></td>
            <td><?= htmlspecialchars($row['kategori']); ?></td>
            <td><?= $row['nominal']; ?></td> 
            <td><?= htmlspecialchars($row['keterangan']); ?></td>
        </tr>
        <?php endforeach; ?>
        
        <tr>
            <td colspan="3" align="right"><b>Total Pemasukan (Uang Masuk):</b></td>
            <td><b><?= $total_pemasukan; ?></b></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="3" align="right"><b>Total Pengeluaran (Uang Keluar):</b></td>
            <td><b><?= $total_pengeluaran; ?></b></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="3" align="right"><b>Sisa Saldo Kas Bersih:</b></td>
            <td><b><?= $total_pemasukan - $total_pengeluaran; ?></b></td>
            <td></td>
        </tr>
    </tbody>
</table>