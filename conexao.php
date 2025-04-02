<?php
$host = 'localhost';
$dbname = 'AcervoReceitas';
$username = 'root';
$password = 'nova_senha';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
?>
