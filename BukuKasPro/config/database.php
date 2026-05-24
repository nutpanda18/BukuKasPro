<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "bukukaspro"; // Make sure this matches your schema name in phpMyAdmin

try {
    // CRUCIAL: This variable name MUST be exactly $pdo
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>