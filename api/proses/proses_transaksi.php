<?php
// Check for valid cookie credentials instead of active sessions
if (!isset($_COOKIE['user_id']) || !isset($_COOKIE['role'])) {
    die("Access denied. Please log in first.");
}

require_once dirname(__DIR__) . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nominal    = floatval($_POST['nominal']);
    $kategori   = $_POST['kategori'];
    $jenis      = $_POST['jenis_transaksi'];
    $keterangan = trim($_POST['keterangan']);
    
    // 🌟 ENHANCED DATE CAPTURE: Match fallback options reliably
    $input_tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    if (!empty($input_tanggal)) {
        $tanggal = $input_tanggal . ' ' . date('H:i:s');
    } else {
        $tanggal = date('Y-m-d H:i:s');
    }
    
    // Read from cookies
    $user_id    = $_COOKIE['user_id'];
    $user_role  = $_COOKIE['role']; 
    $nama_nota  = null;

    // 🌟 VERCEL-COMPATIBLE FILE HANDLING: Convert upload directly into a Base64 String
    if (isset($_FILES['nota']) && $_FILES['nota']['error'] === UPLOAD_ERR_OK) {
        $file_tmp   = $_FILES['nota']['tmp_name'];
        $file_type  = $_FILES['nota']['type'];
        $file_data  = file_get_contents($file_tmp);
        
        // Encode image contents to string: data:image/jpeg;base64,/9j/4AAQSk...
        $nama_nota  = 'data:' . $file_type . ';base64,' . base64_encode($file_data);
    }

    try {
        // ADDED FIX FOR TiDB CLUSTERED INDEX: Generate random unique transaction ID
        $transaksi_id = rand(100000, 999999);

        // 3. Simpan transaksi baru ke database (Explicitly include the id parameter)
        $sql = "INSERT INTO transaksi (id, user_id, nominal, tanggal, kategori, jenis_transaksi, keterangan, nota) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$transaksi_id, $user_id, $nominal, $tanggal, $kategori, $jenis, $keterangan, $nama_nota]);

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