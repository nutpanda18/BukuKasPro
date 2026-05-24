<?php

$host = getenv("gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com");
$port = getenv("4000");
$db   = getenv("bukukaspro");
$user = getenv("2K5i5b6SJjhkQCe.root");
$pass = getenv("RTUpMBtlWglobf4G");

try {
    // FORCE TCP CONNECTION: Add your exact TiDB host string here
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