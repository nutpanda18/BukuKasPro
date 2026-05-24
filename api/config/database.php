<?php

$host = getenv("gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com");
$port = getenv("4000");
$db   = getenv("bukukaspro");
$user = getenv("2K5i5b6SJjhkQCe.root");
$pass = getenv("RTUpMBtlWgl0bf4g");

try {
    // Configure PDO to enforce an encrypted SSL tunnel
    $dsn = "mysql:host=$host;port=$port;dbname=$db_name;charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // CRUCIAL FIX: Force MySQL driver to execute over SSL
        PDO::MYSQL_ATTR_SSL_CA => true 
    ];

    $pdo = new PDO($dsn, $username, $password, $options);
    
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>