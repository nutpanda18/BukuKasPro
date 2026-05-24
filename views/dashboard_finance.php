<?php
if (!isset($_COOKIE['role'])) {
    header("Location: ../login.php");
    exit;
} elseif ($_COOKIE['role'] !== 'finance') {
    header("Location: ../views/dashboard_" . $_COOKIE['role'] . ".php");
    exit;
}

require_once dirname(__DIR__) . '/config/database.php';

$user_id = $_COOKIE['user_id'];

try {
    // 1. Hitung Real-time Total Kas Perusahaan
    $stmt_masuk = $pdo->query("SELECT SUM(nominal) as total FROM transaksi WHERE jenis_transaksi = 'pemasukan'");
    $total_masuk = $stmt_masuk->fetch()['total'] ?? 0;

    $stmt_keluar = $pdo->query("SELECT SUM(nominal) as total FROM transaksi WHERE jenis_transaksi = 'pengeluaran'");
    $total_keluar = $stmt_keluar->fetch()['total'] ?? 0;

    $kas_sekarang = $total_masuk - $total_keluar;

    // 2. Hitung Real-time Total Piutang Client yang belum lunas
    $stmt_piutang = $pdo->query("SELECT SUM(nominal) as total FROM hutang_piutang WHERE jenis_tagihan = 'piutang' AND status_tagihan = 'Belum Bayar'");
    $total_piutang = $stmt_piutang->fetch()['total'] ?? 0;

    // 3. Hitung Real-time Total Hutang Supplier yang belum lunas
    $stmt_hutang = $pdo->query("SELECT SUM(nominal) as total FROM hutang_piutang WHERE jenis_tagihan = 'hutang' AND status_tagihan = 'Belum Bayar'");
    $total_hutang = $stmt_hutang->fetch()['total'] ?? 0;

    // 4. Fetch Active Outstanding Reminders dynamically
    $stmt_reminders = $pdo->query("SELECT nama_kontak, no_whatsapp, nominal, status_tagihan FROM hutang_piutang WHERE status_tagihan != 'Lunas' ORDER BY tanggal_jatuh_tempo ASC LIMIT 5");
    $active_reminders = $stmt_reminders->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Gagal memuat data finansial real-time: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Dashboard - BukuKasPro</title>
    
    <link rel="stylesheet" href="../assets/css/finance.css?v=7">
    <link rel="stylesheet" href="../assets/css/style.css?v=7">
</head>
<body class="dashboard-body">

    <nav class="nav-container">
        <div class="nav-wrapper">
            <div class="nav-branding-group">
                <span class="nav-title-main">BukuKas<span class="nav-title-sub">Pro</span></span>
                <span class="nav-badge-mode">Finance Mode</span>
            </div>
            <div class="nav-profile-group">
                <p class="nav-profile-text">Staf Keuangan: <b><?= htmlspecialchars($_COOKIE['nama'] ?? 'Staff'); ?></b></p>
                <a href="../proses/proses_logout.php" class="nav-logout-btn">Keluar</a>
            </div>
    </nav>

    <main class="finance-layout-main">
        
        <div class="metric-grid">
            <div class="metric-card">
                <p class="metric-title">Total Kas Perusahaan</p>
                <p class="metric-value kas-green">Rp <?= number_format($kas_sekarang, 0, ',', '.'); ?></p>
            </div>
            <div class="metric-card">
                <p class="metric-title">Total Piutang Client</p>
                <p class="metric-value piutang-orange">Rp <?= number_format($total_piutang, 0, ',', '.'); ?></p>
            </div>
            <div class="metric-card">
                <p class="metric-title">Total Hutang Supplier</p>
                <p class="metric-value hutang-red">Rp <?= number_format($total_hutang, 0, ',', '.'); ?></p>
            </div>
        </div>

        <div class="workspace-grid">
            
            <div class="col-main-stack">
                
                <div class="form-sub-nav">
                    <button class="sub-nav-btn active" onclick="switchFormTab(event, 'tab-buku-besar')"> Buku Besar</button>
                    <button class="sub-nav-btn" onclick="switchFormTab(event, 'tab-utang-piutang')"> Utang & Piutang</button>
                </div>
                
                <div id="tab-buku-besar" class="form-view-panel active">
                    <div class="workspace-card">
                        <h3 class="panel-section-title"> Pencatatan Transaksi Buku Besar</h3>
                        <p class="panel-section-subtitle">Log physical store sales mutations, operational expenses, or direct payroll logs.</p>
                        
                        <form action="../proses/proses_transaksi.php" method="POST" enctype="multipart/form-data" class="input-stack">
                            <input type="hidden" name="redirect_to" value="dashboard_finance.php">  

                            <div class="field-group">
                                <label class="field-label">Jenis Transaksi</label>
                                <select name="jenis_transaksi" class="field-element">
                                    <option value="pemasukan">Pemasukan (Uang Masuk)</option>
                                    <option value="pengeluaran">Pengeluaran (Uang Keluar)</option>
                                </select>
                            </div>

                            <div class="field-group">
                                <label class="field-label">Kategori Pos Akuntansi</label>
                                <select name="kategori" class="field-element">
                                    <option value="Penjualan">Penjualan Produk</option>
                                    <option value="Operasional">Biaya Operasional</option>
                                    <option value="Gaji">Gaji Karyawan</option>
                                </select>
                            </div>

                            <div class="field-group">
                                <label class="field-label">Nominal Rupiah</label>
                                <input type="number" name="nominal" required placeholder="0" class="field-element">
                            </div>

                            <div class="field-group">
                                <label class="field-label">Tanggal</label>
                                <input type="date" name="tanggal" value="<?= date('Y-m-d'); ?>" required class="field-element">
                            </div>

                            <div class="field-group">
                                <label class="field-label">Keterangan</label>
                                <input type="text" name="keterangan" placeholder="Keterangan transaksi..." class="field-element">
                            </div>

                            <button type="submit" class="btn-save-ledger">Simpan Buku Kas</button>
                        </form>
                    </div>
                </div>

                <div id="tab-utang-piutang" class="form-view-panel">
                    <div class="workspace-card">
                        <h3 class="panel-section-title" style="color: #4f46e5;"> Catat Mutasi Utang & Piutang Baru</h3>
                        <p class="panel-section-subtitle">Initialize client payment delays or register wholesale invoices with deferred balances.</p>
                        
                        <form action="../proses/proses_piutang.php" method="POST" class="input-stack">
                            <div class="field-group">
                                <label class="field-label">Nama Kontak / Instansi</label>
                                <input type="text" name="nama_kontak" required placeholder="Contoh: PT. Wijaya Mandiri" class="field-element">
                            </div>

                            <div class="field-group">
                                <label class="field-label">Nomor WhatsApp Aktif</label>
                                <input type="text" name="no_whatsapp" required placeholder="Contoh: 62812345678" class="field-element">
                            </div>

                            <div class="field-group">
                                <label class="field-label">Nominal Rupiah (Rp)</label>
                                <input type="number" name="nominal" required placeholder="0" class="field-element">
                            </div>

                            <div class="field-group">
                                <label class="field-label">Jenis Pembukuan</label>
                                <select name="jenis_tagihan" class="field-element">
                                    <option value="piutang">PIUTANG (Pihak Lain Berutang Ke Kita)</option>
                                    <option value="hutang">HUTANG (Kita Berutang Ke Supplier)</option>
                                </select>
                            </div>

                            <div class="field-group">
                                <label class="field-label">Tanggal Jatuh Tempo</label>
                                <input type="date" name="tanggal_jatuh_tempo" value="<?= date('Y-m-d', strtotime('+30 days')); ?>" required class="field-element">
                            </div>

                            <button type="submit" class="btn-save-ledger" style="background-color: #4f46e5;">Simpan Catatan Tagihan</button>
                        </form>
                    </div>
                </div>

            </div> <div class="workspace-card">
                <h3 class="panel-section-title">Status Penagihan (Smart Reminder)</h3>
                <p class="panel-section-subtitle">Daftar Invoice Aktif Jatuh Tempo:</p>
                
                <div class="reminder-list-container">
                    <?php if (empty($active_reminders)): ?>
                        <p class="empty-reminder-text">Tidak ada tagihan atau piutang aktif berjalan.</p>
                    <?php else: ?>
                        <?php foreach($active_reminders as $item): 
                            $clean_phone = preg_replace('/[^0-9]/', '', $item['no_whatsapp']);
                            if (strpos($clean_phone, '0') === 0) { $clean_phone = '62' . substr($clean_phone, 1); }
                            
                            $badge_class = ($item['status_tagihan'] === 'Overdue') ? 'badge-overdue' : 'badge-pending';
                            $btn_label = ($item['status_tagihan'] === 'Overdue') ? '🟢 Tagih via WA' : '🟢 Kirim Pengingat';
                        ?>
                            <div class="reminder-item-row">
                                <div class="reminder-meta-block">
                                    <div class="reminder-profile-data">
                                        <p class="reminder-entity-name"><?= htmlspecialchars($item['nama_kontak']); ?></p>
                                        <p class="reminder-entity-amount">Rp <?= number_format($item['nominal'], 0, ',', '.'); ?></p>
                                    </div>
                                    <span class="status-badge <?= $badge_class; ?>"><?= htmlspecialchars($item['status_tagihan']); ?></span>
                                </div>
                                <a href="https://wa.me/<?= $clean_phone ?>?text=Halo%20<?= urlencode($item['nama_kontak']) ?>%2C%20ini%20adalah%20pengingat%20resmi%20tagihan%20BukuKasPro%20anda%20sebesar%20Rp%20<?= number_format($item['nominal'], 0, ',', '.') ?>." target="_blank" class="reminder-wa-trigger-btn">
                                    <?= $btn_label ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div> </main>

    <script>
        function switchFormTab(evt, tabId) {
            // Hide all structural input panel blocks
            const panels = document.getElementsByClassName("form-view-panel");
            for (let i = 0; i < panels.length; i++) {
                panels[i].classList.remove("active");
            }

            // Remove active formatting style from all button tabs
            const buttons = document.getElementsByClassName("sub-nav-btn");
            for (let i = 0; i < buttons.length; i++) {
                buttons[i].classList.remove("active");
            }

            // Unveil targeted selected canvas element block and style selection trigger button
            document.getElementById(tabId).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
    </script>

</body>
</html>