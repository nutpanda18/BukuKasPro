<?php
// Check for valid cookie credentials instead of active sessions
if (!isset($_COOKIE['user_id']) || !isset($_COOKIE['role'])) {
    die("Access denied. Please log in first.");
}

require_once dirname(__DIR__) . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nominal    = floatval($_POST['nominal']);
    $tanggal    = $_POST['tanggal'];
    $kategori   = $_POST['kategori'];
    $jenis      = $_POST['jenis_transaksi'];
    $keterangan = trim($_POST['keterangan']);
    
    // Read from cookies
    $user_id    = $_COOKIE['user_id'];
    $user_role  = $_COOKIE['role']; 
    $nama_nota  = null;

    // Handle file upload
    if (isset($_FILES['nota']) && $_FILES['nota']['error'] === UPLOAD_ERR_OK) {
        $target_dir = dirname(__DIR__) . "/uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $file_name = time() . '_' . basename($_FILES['nota']['name']);
        if (move_uploaded_file($_FILES['nota']['tmp_name'], $target_dir . $file_name)) {
            $nama_nota = $file_name;
        }
    }

    try {
        $sql = "INSERT INTO transaksi (user_id, nominal, tanggal, kategori, jenis_transaksi, keterangan, nota) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $nominal, $tanggal, $kategori, $jenis, $keterangan, $nama_nota]);

        // BULLETPROOF EXPLICIT REDIRECTION (Now completely isolated per role)
        if (isset($_COOKIE['role'])) {
    $current_role = $_COOKIE['role'];
    
    if ($current_role === 'owner') {
        header("Location: ../views/dashboard_owner.php?status=success");
    } elseif ($current_role === 'finance') {
        header("Location: ../views/dashboard_finance.php?status=success");
    } elseif ($current_role === 'cashier') {
        header("Location: ../views/dashboard_cashier.php?status=success");
    } else {
        header("Location: ../index.html");
    }
} else {
    header("Location: ../index.html");
}
exit;

    } catch (PDOException $e) {
        die("Database entry failed: " . $e->getMessage());
    }
}
?>
