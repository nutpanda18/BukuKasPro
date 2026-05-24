<?php
// ==========================================================================
// MULTI-ROLE SECURITY GUARD (PREVENTS ACCIDENTAL LOGOUT)
// ==========================================================================
if (!isset($_COOKIE['role']) || ($_COOKIE['role'] !== 'owner' && $_COOKIE['role'] !== 'finance')) {
    // If an unauthorized role or guest wanders in, boot them to the login gateway
    header("Location: ../login.php");
    exit;
}

// Secure database connection
require_once dirname(__DIR__) . '/config/database.php';

// --------------------------------------------------------------------------
// ACTION 1: HANDLING THE SETTLE ACTION FROM THE LEDGER TABLES (GET)
// --------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'settle') {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("UPDATE hutang_piutang SET status_tagihan = 'Lunas' WHERE id = ?");
        $stmt->execute([$id]);
        
        // Dynamically return back to whichever dashboard executed the request
        $return_page = ($_COOKIE['role'] === 'finance') ? 'dashboard_finance.php' : 'dashboard_owner.php?page=piutang';
        header("Location: ../views/" . $return_page . "&status=settled");
        exit;
    } catch (PDOException $e) {
        die("Error updating entry: " . $e->getMessage());
    }
}

// --------------------------------------------------------------------------
// ACTION 2: HANDLING THE NEW RECORD LOG ENTRY FORM SUBMISSION (POST)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kontak         = trim($_POST['nama_kontak']);
    $no_whatsapp        = trim($_POST['no_whatsapp']);
    $nominal            = (float)$_POST['nominal'];
    $jenis_tagihan      = $_POST['jenis_tagihan'];
    $tanggal_jatuh_tempo = $_POST['tanggal_jatuh_tempo'];

    try {
        // Prepare row insertion matching your real MySQL schema layout
        $sql = "INSERT INTO hutang_piutang (nama_kontak, no_whatsapp, nominal, jenis_tagihan, tanggal_jatuh_tempo, status_tagihan) 
                VALUES (?, ?, ?, ?, ?, 'Belum Bayar')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama_kontak, $no_whatsapp, $nominal, $jenis_tagihan, $tanggal_jatuh_tempo]);

        // COMPACT ROUTING ROUTE: Send them back to their respective origin screen layout deck
        if ($_COOKIE['role'] === 'finance') {
            header("Location: ../views/dashboard_finance.php?status=success");
        } else {
            header("Location: ../views/dashboard_owner.php?page=piutang&status=success");
        }
        exit;
        
    } catch (PDOException $e) {
        die("Error saving entry to structural database table: " . $e->getMessage());
    }
} else {
    // Graceful error track bounce back if accessed illegally
    $fallback = ($_COOKIE['role'] === 'finance') ? 'dashboard_finance.php' : 'dashboard_owner.php';
    header("Location: ../views/" . $fallback);
    exit;
}

require_once dirname(__DIR__) . '/config/database.php';

// ACTION 1: Handling the Settle action from the table links (GET request)
if (isset($_GET['action']) && $_GET['action'] === 'settle') {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("UPDATE hutang_piutang SET status_tagihan = 'Lunas' WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: ../views/dashboard_owner.php?page=piutang&status=settled");
        exit;
    } catch (PDOException $e) {
        die("Error updating entry: " . $e->getMessage());
    }
}

// ACTION 2: Handling the New Entry form submission (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kontak         = trim($_POST['nama_kontak']);
    $no_whatsapp        = trim($_POST['no_whatsapp']);
    $nominal            = (float)$_POST['nominal'];
    $jenis_tagihan      = $_POST['jenis_tagihan'];
    $tanggal_jatuh_tempo = $_POST['tanggal_jatuh_tempo'];

    try {
        $sql = "INSERT INTO hutang_piutang (nama_kontak, no_whatsapp, nominal, jenis_tagihan, tanggal_jatuh_tempo, status_tagihan) 
                VALUES (?, ?, ?, ?, ?, 'Belum Bayar')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama_kontak, $no_whatsapp, $nominal, $jenis_tagihan, $tanggal_jatuh_tempo]);

        header("Location: ../views/dashboard_owner.php?page=piutang&status=success");
        exit;
    } catch (PDOException $e) {
        die("Error saving entry: " . $e->getMessage());
    }
}