<?php
$host = 'localhost';
$port = 3308;
$db = 'dbcartello';
$user = 'root';
//editor
$pass = '';
//forzanapoli

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connessione fallita: " . $e->getMessage());
}
?>