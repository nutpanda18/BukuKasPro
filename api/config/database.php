<?php

$host = "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com";
$port = "4000";
$db   = "bukukaspro";
$user = "2K5i5b6SJjhkQCe.root";
$pass = "RTUpMBtlWglobf4G";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/isrgrootx1.pem',
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);
    
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>