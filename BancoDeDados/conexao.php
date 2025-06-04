<?php
$host = 'localhost';
$dbname = 'teste_trabalho_1';
$username = 'root';
$password = 'bela123';
// Define a porta do MySQL
// $port = '3307';

// caso na sua máquina o MySQL esteja rodando em uma porta diferente, descomente a linha acima e adicione a porta na string de conexão
// Adicione port=$port; no codigo abaixo, por exemplo:
// $conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
?>
