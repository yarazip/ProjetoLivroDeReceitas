<?php
$host = 'localhost';
$dbname = 'teste_trabalho_1';
$username = 'root';
$password = 'senha';
// Define a porta do MySQL PENAS SE NECESSARIO
$port = '3307';

try {
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
?>
