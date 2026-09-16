<?php

$host = "127.0.0.1";
$port = "3306";
$dbname = "cooking_app";
$username = "root";
$password = "barca";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}