<?php
if (!isset($_COOKIE['role'])) {
    header("Location: ../login.php");
    exit;
} elseif ($_COOKIE['role'] !== 'owner') {
    header("Location: ../views/dashboard_" . $_COOKIE['role'] . ".php");
    exit;
}

require_once dirname(__DIR__) . '/config/database.php';

$current_page = $_GET['page'] ?? 'ringkasan';

try {
    // 1. PERHITUNGAN DINAMIS TOTAL KAS
    $stmt_masuk = $pdo->query("SELECT SUM(nominal) as total FROM transaksi WHERE jenis_transaksi = 'pemasukan'");
    $total_masuk = $stmt_masuk->fetch()['total'] ?? 0;

    $stmt_keluar = $pdo->query("SELECT SUM(nominal) as total FROM transaksi WHERE jenis_transaksi = 'pengeluaran'");
    $total_keluar = $stmt_keluar->fetch()['total'] ?? 0;
    $kas_sekarang = $total_masuk - $total_keluar;

    // 2. PERHITUNGAN DINAMIS TOTAL PIUTANG (Sesuai skema 'Belum Bayar')
    $stmt_piutang = $pdo->query("SELECT SUM(nominal) as total FROM hutang_piutang WHERE jenis_tagihan = 'piutang' AND status_tagihan = 'Belum Bayar'");
    $total_piutang = $stmt_piutang->fetch()['total'] ?? 0;

    // 3. PERHITUNGAN DINAMIS TOTAL HUTANG (Sesuai skema 'Belum Bayar')
    $stmt_hutang = $pdo->query("SELECT SUM(nominal) as total FROM hutang_piutang WHERE jenis_tagihan = 'hutang' AND status_tagihan = 'Belum Bayar'");
    $total_hutang = $stmt_hutang->fetch()['total'] ?? 0;

    // 4. PENGAMBILAN DATA KARYAWAN (Hanya jika membuka tab karyawan)
    $karyawan = [];
    if ($current_page === 'karyawan') {
        $stmt_emp = $pdo->prepare("SELECT id, nama, username, role, created_at FROM users WHERE role != 'owner' ORDER BY id DESC");
        $stmt_emp->execute();
        $karyawan = $stmt_emp->fetchAll(PDO::FETCH_ASSOC);
    }

    // 5. ENGINE GRAFIK 1 & 5: Ambil metrik tahunan dikelompokkan per bulan & Hitung Saldo Berjalan
    $tahun_ini = date('Y');
    $pemasukan_bulanan = array_fill(1, 12, 0);
    $pengeluaran_bulanan = array_fill(1, 12, 0);
    $saldo_akumulasi_bulanan = array_fill(1, 12, 0);

    $query_chart = "SELECT MONTH(tanggal) as bulan, jenis_transaksi, SUM(nominal) as total 
                    FROM transaksi 
                    WHERE YEAR(tanggal) = ? 
                    GROUP BY MONTH(tanggal), jenis_transaksi";
    $stmt_chart = $pdo->prepare($query_chart);
    $stmt_chart->execute([$tahun_ini]);
    
    foreach ($stmt_chart->fetchAll(PDO::FETCH_ASSOC) as $row_chart) {
        $bulan = (int)$row_chart['bulan'];
        if ($row_chart['jenis_transaksi'] === 'pemasukan') {
            $pemasukan_bulanan[$bulan] = (float)$row_chart['total'];
        } elseif ($row_chart['jenis_transaksi'] === 'pengeluaran') {
            $pengeluaran_bulanan[$bulan] = (float)$row_chart['total'];
        }
    }

    // Menghitung Saldo Akumulasi Berjalan untuk Grafik Garis Panjang (Chart 5)
    $running_balance = 0;
    for ($m = 1; $m <= 12; $m++) {
        $net_bulan_ini = $pemasukan_bulanan[$m] - $pengeluaran_bulanan[$m];
        $running_balance += $net_bulan_ini;
        $saldo_akumulasi_bulanan[$m] = $running_balance;
    }

    $json_pemasukan   = json_encode(array_values($pemasukan_bulanan));
    $json_pengeluaran = json_encode(array_values($pengeluaran_bulanan));
    $json_saldo_track = json_encode(array_values($saldo_akumulasi_bulanan));

    // 6. ENGINE GRAFIK 2: Diagram Lingkaran Pengeluaran Berdasarkan Kategori
    $stmt_pie = $pdo->query("SELECT kategori, SUM(nominal) as total FROM transaksi WHERE jenis_transaksi = 'pengeluaran' GROUP BY kategori");
    $data_pie = $stmt_pie->fetchAll(PDO::FETCH_ASSOC);
    
    $labels_pie = [];
    $values_pie = [];
    foreach ($data_pie as $p) {
        $labels_pie[] = $p['kategori'];
        $values_pie[] = (float)$p['total'];
    }
    $json_labels_pie = json_encode($labels_pie);
    $json_values_pie = json_encode($values_pie);

    // 7. ENGINE GRAFIK 3 & 4: Analisis Omzet Mingguan & Tren Piutang Berjalan (7 Hari Terakhir)
    $pendapatan_mingguan = array_fill(0, 7, 0);
    $piutang_mingguan = array_fill(0, 7, 0);
    $label_hari = [];
    for ($i = 6; $i >= 0; $i--) {
        $label_hari[] = date('Y-m-d', strtotime("-$i days"));
    }
    
    // Query data tren pemasukan omzet
    $stmt_bar = $pdo->query("SELECT DATE(tanggal) as tgl, SUM(nominal) as total FROM transaksi WHERE jenis_transaksi = 'pemasukan' AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(tanggal)");
    foreach ($stmt_bar->fetchAll(PDO::FETCH_ASSOC) as $b) {
        $index = array_search($b['tgl'], $label_hari);
        if ($index !== false) { $pendapatan_mingguan[$index] = (float)$b['total']; }
    }

    // Query data tren pertumbuhan piutang aktif berjalan
    $stmt_piutang_track = $pdo->query("SELECT DATE(created_at) as tgl, SUM(nominal) as total FROM hutang_piutang WHERE jenis_tagihan = 'piutang' AND status_tagihan = 'Belum Bayar' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at)");
    foreach ($stmt_piutang_track->fetchAll(PDO::FETCH_ASSOC) as $pt) {
        $index = array_search($pt['tgl'], $label_hari);
        if ($index !== false) { $piutang_mingguan[$index] = (float)$pt['total']; }
    }

    // Ubah format label penanggalan menjadi string nama hari (Sen, Sel, Rab...) untuk Chart.js
    $label_hari_formatted = [];
    foreach ($label_hari as $lh) {
        $label_hari_formatted[] = date('D', strtotime($lh));
    }

    $json_labels_bar = json_encode($label_hari_formatted);
    $json_values_bar = json_encode($pendapatan_mingguan);
    $json_values_piutang_track = json_encode($piutang_mingguan);

    // 8. DATA TAMBAHAN: Tarik riwayat buku besar piutang jika berada di tab Kelola Piutang
    $ledger_data = [];
    if ($current_page === 'piutang') {
        $stmt_ledger = $pdo->query("SELECT id, nama_kontak, no_whatsapp, nominal, jenis_tagihan, tanggal_jatuh_tempo, status_tagihan, keterangan_tagihan FROM hutang_piutang ORDER BY created_at DESC");
        $ledger_data = $stmt_ledger->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    die("Gagal memuat data operasional real-time: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - BukuKasPro</title>
    
    <link rel="stylesheet" href="../assets/css/owner.css?v=2">
    <link rel="stylesheet" href="../assets/css/kelola.css?v=2">
    <link rel="stylesheet" href="../assets/css/style.css?v=5">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="owner-body">

    <div class="dashboard-container">
        
        <aside class="owner-sidebar">
            <div class="sidebar-brand-area">
                BukuKas<span>Pro</span>
            </div>
            
            <div class="sidebar-menu-list">
                <a href="dashboard_owner.php?page=ringkasan" class="sidebar-item <?= $current_page === 'ringkasan' ? 'active' : ''; ?>">
                    <span><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M320-414v-306h120v306l-60-56-60 56Zm200 60v-526h120v406L520-354ZM120-216v-344h120v224L120-216Zm0 98 258-258 142 122 224-224h-64v-80h200v200h-80v-64L524-146 382-268 232-118H120Z"/></svg></span> Ringkasan Finansial
                </a>
                <a href="dashboard_owner.php?page=piutang" class="sidebar-item <?= $current_page === 'piutang' ? 'active' : ''; ?>">
                    <span><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M444-200h70v-50q50-9 86-39t36-89q0-42-24-77t-96-61q-60-20-83-35t-23-41q0-26 18.5-41t53.5-15q32 0 50 15.5t26 38.5l64-26q-11-35-40.5-61T516-710v-50h-70v50q-50 11-78 44t-28 74q0 47 27.5 76t86.5 50q63 23 87.5 41t24.5 47q0 33-23.5 48.5T486-314q-33 0-58.5-20.5T390-396l-66 26q14 48 43.5 77.5T444-252v52Zm36 120q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg></span> Kelola Utang Piutang
                </a>
                <a href="dashboard_owner.php?page=karyawan" class="sidebar-item <?= $current_page === 'karyawan' ? 'active' : ''; ?>">
                    <span><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M609-389q-29-29-29-71t29-71q29-29 71-29t71 29q29 29 29 71t-29 71q-29 29-71 29t-71-29ZM480-160v-56q0-24 12.5-44.5T528-290q36-15 74.5-22.5T680-320q39 0 77.5 7.5T832-290q23 9 35.5 29.5T880-216v56H480ZM287-527q-47-47-47-113t47-113q47-47 113-47t113 47q47 47 47 113t-47 113q-47 47-113 47t-113-47Zm113-113ZM80-160v-112q0-34 17-62.5t47-43.5q60-30 124.5-46T400-440q35 0 70 6t70 14l-34 34-34 34q-18-5-36-6.5t-36-1.5q-58 0-113.5 14T180-306q-10 5-15 14t-5 20v32h240v80H80Zm320-80Zm56.5-343.5Q480-607 480-640t-23.5-56.5Q433-720 400-720t-56.5 23.5Q320-673 320-640t23.5 56.5Q367-560 400-560t56.5-23.5Z"/></svg></span> Kelola Karyawan
                </a>

                <div class="sidebar-divider"></div>
                <div class="sidebar-section-title">Unduh Laporan</div>
                
                <a href="../proses/ekspor_excel.php" class="sidebar-item sidebar-item-export excel" title="Unduh Laporan Excel">
                    <span><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M480-320 280-520l56-58 104 104v-326h80v326l104-104 56 58-200 200ZM240-160q-33 0-56.5-23.5T160-240v-120h80v120h480v-120h80v120q0 33-23.5 56.5T720-160H240Z"/></svg></span> Laporan Excel (.xlsx)
                </a>
                <a href="../proses/ekspor_pdf.php" target="_blank" class="sidebar-item sidebar-item-export pdf" title="Print Laporan PDF">
                    <span><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M640-640v-120H320v120h-80v-200h480v200h-80Zm-480 80h640-640Zm560 100q17 0 28.5-11.5T760-500q0-17-11.5-28.5T720-540q-17 0-28.5 11.5T680-500q0 17 11.5 28.5T720-460Zm-80 260v-160H320v160h320Zm80 80H240v-160H80v-240q0-51 35-85.5t85-34.5h560q51 0 85.5 34.5T880-520v240H720v160Zm80-240v-160q0-17-11.5-28.5T760-560H200q-17 0-28.5 11.5T160-520v160h80v-80h480v80h80Z"/></svg></span> Cetak PDF (.pdf)
                </a>
            </div>
        </aside>

        <div class="owner-content-body">
            
            <div class="owner-top-bar">
                <span class="top-bar-greeting">Halo, <b class="top-bar-username"><?= htmlspecialchars($_COOKIE['nama'] ?? 'Owner'); ?></b></span>
                <a href="../proses/proses_logout.php" class="btn-logout-owner">Keluar</a>
            </div>

            <?php if ($current_page === 'ringkasan'): ?>
                <div class="dashboard-hero-banner">
                    <img src="/assets/img/accounting.png" alt="Financial Accounting Overview Header" class="your-class">
                </div>
            <?php endif; ?>

            <div class="owner-metrics-summary">
                <div class="summary-data-card">
                    <p class="summary-card-label">Total Kas Saat Ini</p>
                    <p class="summary-card-value" style="color: #16a34a;">Rp <?= number_format($kas_sekarang, 0, ',', '.'); ?></p>
                </div>
                <div class="summary-data-card">
                    <p class="summary-card-label">Total Piutang</p>
                    <p class="summary-card-value" style="color: #d97706;">Rp <?= number_format($total_piutang, 0, ',', '.'); ?></p>
                </div>
                <div class="summary-data-card">
                    <p class="summary-card-label">Total Hutang</p>
                    <p class="summary-card-value" style="color: #dc2626;">Rp <?= number_format($total_hutang, 0, ',', '.'); ?></p>
                </div>
            </div>

            <?php if ($current_page === 'karyawan'): ?>
                
                <div class="owner-block-card">
                    <h3 class="block-card-title">👥 Kredensial & Kontrol Akses Anggota Tim</h3>
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
                                <tr><td colspan="5" style="text-align: center; padding: 3rem 0;">Belum ada akun karyawan terdaftar.</td></tr>
                            <?php else: ?>
                                <?php foreach($karyawan as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['nama']); ?></td>
                                    <td><?= htmlspecialchars($row['username']); ?></td>
                                    <td><span class="badge-role <?= $row['role']; ?>"><?= ucfirst($row['role']); ?></span></td>
                                    <td>
                                        <?php 
                                        // 🌟 FIXED KEY DISCONNECT: Changed $user to $row to read the actual loop data
                                        $tanggal_raw = $row['created_at'] ?? $row['tanggal_terdaftar'] ?? null; 
            
                                        if (!empty($tanggal_raw)) {
                                            echo date('d M Y', strtotime($tanggal_raw));
                                        } else {
                                            echo '<span style="color: #94a3b8; font-style: italic; font-size: 0.85rem;">Belum ada data</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="edit_karyawan.php?id=<?= $row['id']; ?>" class="btn-action btn-edit">Ubah</a>
                                        <a href="../proses/proses_karyawan.php?action=delete&id=<?= $row['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Hapus permanen?')">Hapus</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($current_page === 'piutang'): ?>
                
                <div class="owner-block-card" style="margin-top: 0;">
                    <h3 class="block-card-title"> Buku Jurnal Utang & Piutang Aktif</h3>
                    <p style="font-size: 0.85rem; color: #64748b; margin: 0 0 1.5rem 0;">Pantau utang supplier, kredit limit klien, kirim penagihan instan via WhatsApp, dan lunaskan sisa tagihan:</p>

                    <table class="table-users">
                        <thead>
                            <tr>
                                <th>Nama Kontak</th>
                                <th>No. WhatsApp</th>
                                <th>Jenis Log</th>
                                <th>Nominal</th>
                                <th>Jatuh Tempo</th>
                                <th>Status</th>
                                <th>Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($ledger_data)): ?>
                                <tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 3rem 0;">Tidak ditemukan catatan tagihan aktif di database.</td></tr>
                            <?php else: ?>
                                <?php foreach($ledger_data as $item): ?>
                                <tr>
                                    <td style="font-weight: 600; color: #0f172a;"><?= htmlspecialchars($item['nama_kontak']); ?></td>
                                    <td style="color: #475569;"><?= htmlspecialchars($item['no_whatsapp']); ?></td>
                                    <td>
                                        <span class="badge-role" style="background-color: <?= $item['jenis_tagihan'] === 'piutang' ? '#e0e7ff; color: #4338ca;' : '#fef3c7; color: #d97706;'; ?>">
                                            <?= strtoupper($item['jenis_tagihan']); ?>
                                        </span>
                                    </td>
                                    <td style="font-weight: 700;">Rp <?= number_format($item['nominal'], 0, ',', '.'); ?></td>
                                    <td style="color: #64748b;"><?= date('d M Y', strtotime($item['tanggal_jatuh_tempo'])); ?></td>
                                    <td>
                                        <span class="badge-role" style="background-color: <?= $item['status_tagihan'] === 'Lunas' ? '#d1fae5; color: #065f46;' : '#fee2e2; color: #991b1b;'; ?>">
                                            <?= htmlspecialchars($item['status_tagihan']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($item['status_tagihan'] !== 'Lunas'): 
                                            $clean_phone = preg_replace('/[^0-9]/', '', $item['no_whatsapp']);
                                            if (strpos($clean_phone, '0') === 0) {
                                                $clean_phone = '62' . substr($clean_phone, 1);
                                            }
                                        ?>
                                            <a href="../proses/proses_piutang.php?action=settle&id=<?= $item['id'] ?>" class="btn-action btn-edit" onclick="return confirm('Tandai tagihan ini sebagai lunas?')">Lunas</a>
                                            <a href="https://wa.me/<?= $clean_phone ?>?text=Halo%20<?= urlencode($item['nama_kontak']) ?>%2C%20ini%20adalah%20pengingat%20resmi%20tagihan%20sebesar%20Rp%20<?= number_format($item['nominal'], 0, ',', '.') ?>%20dari%20BukuKasPro." target="_blank" class="btn-action btn-edit" style="background-color: #25d366; border-color: #25d366;">WhatsApp</a>
                                        <?php else: ?>
                                            <span style="color: #94a3b8; font-size: 0.85rem;">✔ Transaksi Tutup</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>
                
                <div class="owner-workspace-grid">
                    
                    <div class="owner-block-card-chart">
                        <h3 class="chart-title-area"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#274483"><path d="M495-155q-35-35-35-85t35-85q35-35 85-35t85 35q35 35 35 85t-35 85q-35 35-85 35t-85-35Zm113.5-56.5Q620-223 620-240t-11.5-28.5Q597-280 580-280t-28.5 11.5Q540-257 540-240t11.5 28.5Q563-200 580-200t28.5-11.5ZM504-464q-64-64-64-156t64-156q64-64 156-64t156 64q64 64 64 156t-64 156q-64 64-156 64t-156-64Zm255.5-56.5Q800-561 800-620t-40.5-99.5Q719-760 660-760t-99.5 40.5Q520-679 520-620t40.5 99.5Q601-480 660-480t99.5-40.5ZM280-240q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47Zm56.5-103.5Q360-367 360-400t-23.5-56.5Q313-480 280-480t-56.5 23.5Q200-433 200-400t23.5 56.5Q247-320 280-320t56.5-23.5ZM580-240Zm80-380ZM280-400Z"/></svg> Grafik Arus Kas Real-Time</h3>
                        <div class="chart-aspect-container">
                            <canvas id="arusKasChart" class="chart-rendering-canvas"></canvas>
                        </div>
                    </div>

                    <div class="owner-block-card-chart">
                        <h3 class="chart-title-area"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#274483"><path d="M495-155q-35-35-35-85t35-85q35-35 85-35t85 35q35 35 35 85t-35 85q-35 35-85 35t-85-35Zm113.5-56.5Q620-223 620-240t-11.5-28.5Q597-280 580-280t-28.5 11.5Q540-257 540-240t11.5 28.5Q563-200 580-200t28.5-11.5ZM504-464q-64-64-64-156t64-156q64-64 156-64t156 64q64 64 64 156t-64 156q-64 64-156 64t-156-64Zm255.5-56.5Q800-561 800-620t-40.5-99.5Q719-760 660-760t-99.5 40.5Q520-679 520-620t40.5 99.5Q601-480 660-480t99.5-40.5ZM280-240q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47Zm56.5-103.5Q360-367 360-400t-23.5-56.5Q313-480 280-480t-56.5 23.5Q200-433 200-400t23.5 56.5Q247-320 280-320t56.5-23.5ZM580-240Zm80-380ZM280-400Z"/></svg> Performa Omzet Mingguan</h3>
                        <div class="chart-pie-container">
                            <canvas id="omzetWeeklyBarChart"></canvas>
                        </div>
                    </div>

                    <div class="owner-block-card-chart">
                        <h3 class="chart-title-area"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#274483"><path d="M495-155q-35-35-35-85t35-85q35-35 85-35t85 35q35 35 35 85t-35 85q-35 35-85 35t-85-35Zm113.5-56.5Q620-223 620-240t-11.5-28.5Q597-280 580-280t-28.5 11.5Q540-257 540-240t11.5 28.5Q563-200 580-200t28.5-11.5ZM504-464q-64-64-64-156t64-156q64-64 156-64t156 64q64 64 64 156t-64 156q-64 64-156 64t-156-64Zm255.5-56.5Q800-561 800-620t-40.5-99.5Q719-760 660-760t-99.5 40.5Q520-679 520-620t40.5 99.5Q601-480 660-480t99.5-40.5ZM280-240q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47Zm56.5-103.5Q360-367 360-400t-23.5-56.5Q313-480 280-480t-56.5 23.5Q200-433 200-400t23.5 56.5Q247-320 280-320t56.5-23.5ZM580-240Zm80-380ZM280-400Z"/></svg> Komposisi Alokasi Pengeluaran</h3>
                        <div class="chart-pie-container">
                            <canvas id="pengeluaranPieChart"></canvas>
                        </div>
                    </div>

                    <div class="owner-block-card-chart">
                        <h3 class="chart-title-area"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#274483"><path d="M495-155q-35-35-35-85t35-85q35-35 85-35t85 35q35 35 35 85t-35 85q-35 35-85 35t-85-35Zm113.5-56.5Q620-223 620-240t-11.5-28.5Q597-280 580-280t-28.5 11.5Q540-257 540-240t11.5 28.5Q563-200 580-200t28.5-11.5ZM504-464q-64-64-64-156t64-156q64-64 156-64t156 64q64 64 64 156t-64 156q-64 64-156 64t-156-64Zm255.5-56.5Q800-561 800-620t-40.5-99.5Q719-760 660-760t-99.5 40.5Q520-679 520-620t40.5 99.5Q601-480 660-480t99.5-40.5ZM280-240q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47Zm56.5-103.5Q360-367 360-400t-23.5-56.5Q313-480 280-480t-56.5 23.5Q200-433 200-400t23.5 56.5Q247-320 280-320t56.5-23.5ZM580-240Zm80-380ZM280-400Z"/></svg> Tren Pertumbuhan Piutang Toko</h3>
                        <div class="chart-pie-container">
                            <canvas id="piutangTrendChart"></canvas>
                        </div>
                    </div>

                    <div class="panel-full-width owner-block-card-chart">
                        <h3 class="chart-title-area"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#274483"><path d="M495-155q-35-35-35-85t35-85q35-35 85-35t85 35q35 35 35 85t-35 85q-35 35-85 35t-85-35Zm113.5-56.5Q620-223 620-240t-11.5-28.5Q597-280 580-280t-28.5 11.5Q540-257 540-240t11.5 28.5Q563-200 580-200t28.5-11.5ZM504-464q-64-64-64-156t64-156q64-64 156-64t156 64q64 64 64 156t-64 156q-64 64-156 64t-156-64Zm255.5-56.5Q800-561 800-620t-40.5-99.5Q719-760 660-760t-99.5 40.5Q520-679 520-620t40.5 99.5Q601-480 660-480t99.5-40.5ZM280-240q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47Zm56.5-103.5Q360-367 360-400t-23.5-56.5Q313-480 280-480t-56.5 23.5Q200-433 200-400t23.5 56.5Q247-320 280-320t56.5-23.5ZM580-240Zm80-380ZM280-400Z"/></svg> Tren Akumulasi Saldo Kas & Pertumbuhan Bisnis (Bulanan)</h3>
                        <div class="chart-full-container">
                            <canvas id="saldoAccumulasiChart"></canvas>
                        </div>
                    </div>

                </div>

                <script>
                    // --- CHART 1: LINE ARUS KAS WAVE ---
                    const ctxArus = document.getElementById('arusKasChart').getContext('2d');
                    const gradMasuk = ctxArus.createLinearGradient(0, 0, 0, 220);
                    gradMasuk.addColorStop(0, 'rgba(79, 70, 229, 0.3)');
                    gradMasuk.addColorStop(1, 'rgba(79, 70, 229, 0.01)');

                    const gradKeluar = ctxArus.createLinearGradient(0, 0, 0, 220);
                    gradKeluar.addColorStop(0, 'rgba(14, 165, 233, 0.15)');
                    gradKeluar.addColorStop(1, 'rgba(14, 165, 233, 0.01)');

                    new Chart(ctxArus, {
                        type: 'line',
                        data: {
                            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                            datasets: [
                                { label: 'Masuk', data: <?= $json_pemasukan; ?>, borderColor: '#4f46e5', backgroundColor: gradMasuk, fill: true, tension: 0.4, borderWidth: 2 },
                                { label: 'Keluar', data: <?= $json_pengeluaran; ?>, borderColor: '#0ea5e9', backgroundColor: gradKeluar, fill: true, tension: 0.4, borderDash: [4, 4], borderWidth: 1.5 }
                            ]
                        },
                        options: { 
                            responsive: true, 
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: { y: { ticks: { font: { size: 9 } } }, x: { ticks: { font: { size: 9 } } } }
                        }
                    });

                    // --- CHART 2: WEEKLY COMPACT BAR ---
                    const ctxBar = document.getElementById('omzetWeeklyBarChart').getContext('2d');
                    new Chart(ctxBar, {
                        type: 'bar',
                        data: {
                            labels: <?= $json_labels_bar; ?>,
                            datasets: [{ data: <?= $json_values_bar; ?>, backgroundColor: '#4f46e5', borderRadius: 4, barThickness: 10 }]
                        },
                        options: { 
                            responsive: true, 
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: { y: { ticks: { font: { size: 9 } } }, x: { grid: { display: false }, ticks: { font: { size: 9 } } } }
                        }
                    });

                    // --- CHART 3: DONUT ALLOCATION PENGELUARAN ---
                    const ctxPie = document.getElementById('pengeluaranPieChart').getContext('2d');
                    new Chart(ctxPie, {
                        type: 'doughnut',
                        data: {
                            labels: <?= $json_labels_pie; ?>,
                            datasets: [{ data: <?= $json_values_pie; ?>, backgroundColor: ['#4f46e5', '#3b82f6', '#0ea5e9', '#cbd5e1'], borderWidth: 1 }]
                        },
                        options: { 
                            responsive: true, 
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'right', labels: { boxWidth: 8, font: { size: 9 } } } }
                        }
                    });

                    // --- CHART 4: TREN PERTUMBUHAN PIUTANG RIIL ---
                    const ctxPiutang = document.getElementById('piutangTrendChart').getContext('2d');
                    new Chart(ctxPiutang, {
                        type: 'line',
                        data: {
                            labels: <?= $json_labels_bar; ?>,
                            datasets: [{ data: <?= $json_values_piutang_track; ?>, borderColor: '#6366f1', backgroundColor: 'rgba(99, 102, 241, 0.05)', fill: true, tension: 0.4, pointRadius: 1, borderWidth: 1.5 }]
                        },
                        options: { 
                            responsive: true, 
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: { y: { ticks: { font: { size: 9 } } }, x: { grid: { display: false }, ticks: { font: { size: 9 } } } }
                        }
                    });

                    // --- CHART 5: TIMELINE SALDO KAS AKUMULATIF (RUNNING BALANCE) ---
                    const ctxSaldo = document.getElementById('saldoAccumulasiChart').getContext('2d');
                    const gradSaldo = ctxSaldo.createLinearGradient(0, 0, 0, 140);
                    gradSaldo.addColorStop(0, 'rgba(99, 102, 241, 0.2)');
                    gradSaldo.addColorStop(1, 'rgba(99, 102, 241, 0.00)');

                    new Chart(ctxSaldo, {
                        type: 'line',
                        data: {
                            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                            datasets: [{
                                label: 'Saldo Kas Berjalan',
                                data: <?= $json_saldo_track; ?>,
                                borderColor: '#6366f1',
                                backgroundColor: gradSaldo,
                                fill: true,
                                tension: 0.3,
                                borderWidth: 2,
                                pointRadius: 3,
                                pointBackgroundColor: '#ffffff',
                                pointBorderColor: '#6366f1',
                                pointBorderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { 
                                    grid: { color: '#f8fafc' },
                                    ticks: { font: { size: 9 }, callback: v => 'Rp ' + v.toLocaleString('id-ID') } 
                                },
                                x: { 
                                    grid: { display: false },
                                    ticks: { font: { size: 9 } } 
                                }
                            }
                        }
                    });
                </script>

            <?php endif; ?>

        </div>
    </div>

</body>
</html>