<?php

$host = getenv("gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com");
$port = getenv("4000");
$db   = getenv("bukukaspro");
$user = getenv("2K5i5b6SJjhkQCe.root");
$pass = getenv("RTUpMBtlWgl0bf4g");

try {

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

    $pdo = new PDO($dsn, $user, $pass);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    die("Connection failed: " . $e->getMessage());

}
?>