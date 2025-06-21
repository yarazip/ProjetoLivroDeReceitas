<?php
$host = 'localhost';
$dbname = 'teste_trabalho_1';
$username = 'root';
$password = 'senha';
// Define a porta do MySQL
$port = '3307';

// caso na sua máquina o MySQL esteja rodando em uma porta diferente, descomente a linha acima e adicione a porta na string de conexão
// Adicione port=$port; no codigo abaixo, por exemplo:
// $conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);

try {
$conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// --- FUNÇÕES DE FEEDBACK (FLASH MESSAGES) ---
function set_flash_message($message, $type = 'success') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        // Adicionei classes CSS para estilizar as mensagens
        $class = $flash['type'] === 'success' ? 'flash-success' : 'flash-error';
        echo '<div class="' . $class . '">' . htmlspecialchars($flash['message']) . '</div>';
        unset($_SESSION['flash_message']);
    }
}
?>
