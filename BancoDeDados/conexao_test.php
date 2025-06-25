<?php
$host = 'localhost';
$dbname = 'teste_trabalho_1';
$username = 'root';
$password = 'yara123';
$port = '3307';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
} catch(PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
