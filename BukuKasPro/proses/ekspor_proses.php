<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    die("Unauthorized.");
}

$type  = $_GET['type'] ?? '';
$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end'] ?? date('Y-m-t');

if ($type === 'excel') {
    // Tell browser to expect an Excel document file extension download
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=BukuKasPro_Report_$start" . "_to_$end.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Simple trick: Excel renders HTML raw tables perfectly if headers are sent!
    echo "<h3>BukuKasPro Financial Export: $start to $end</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Category</th><th>Amount</th></tr>";
    echo "<tr><td>Gross Revenue</td><td>$start</td></tr>";
    echo "</table>";
    exit;
} elseif ($type === 'pdf') {
    // Quick trick: Open browser print dialog to natively save clean PDFs via CSS!
    echo "<script>window.print(); window.history.back();</script>";
    exit;
}
?>