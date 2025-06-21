<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../BancoDeDados/conexao.php';

// Verifica se o usuário está logado e se tem permissão de Administrador
if (!isset($_SESSION['id_login']) || $_SESSION['cargo'] !== 'administrador') {
    // $_SESSION['message'] = "Você não tem permissão para adicionar restaurantes.";
    $_SESSION['message_type'] = "error";
    header("Location: ../LoginSenha/login.php");
    exit;
}

// Lógica de ADICIONAR RESTAURANTE
if (isset($_POST['adicionar'])) {
    $nome = $_POST['nome'];
    $contato = $_POST['contato'];
    $telefone = $_POST['telefone'];
    $descricao = $_POST['descricao'];

    $conn->beginTransaction();
    try {
        $sql = "INSERT INTO restaurantes (nome, contato, telefone, descricao) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nome, $contato, $telefone, $descricao]);
        $conn->commit();
        $_SESSION['message'] = "Restaurante '" . htmlspecialchars($nome) . "' adicionado com sucesso!";
        $_SESSION['message_type'] = "success";
        header("Location: restauranteADM.php"); // Redireciona para a lista principal
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['message'] = "Erro ao adicionar restaurante: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        error_log("Erro ao adicionar restaurante: " . $e->getMessage());
        header("Location: adicionarRestaurante.php"); // Redireciona de volta para o formulário
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="../../styles/adicionarADM.css">
    <link rel="stylesheet" href="../styles/restaurante.css">
    <title>Adicionar Restaurante | ADM</title>

</head>

<body>
    <div class="container">
        <div class="menu">
            <h1 class="logo">Código de Sabores</h1>
            <nav>
                <a href="cargosADM.php">Cargo</a>
                <a href="restauranteADM.php">Restaurantes</a>
                <a href="funcionarioADM.php">Funcionário</a>
                <a href="referenciaADM.php">Referência</a>
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?= htmlspecialchars($_SESSION['nome_funcionario'] ?? 'Desconhecido') ?></span>
                </div>
            </nav>
        </div>

        <?php
        if (isset($_SESSION['message'])): ?>
            <div class="message-<?= $_SESSION['message_type'] ?? 'info' ?>">
                <?= htmlspecialchars($_SESSION['message']) ?>
            </div>
        <?php
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        endif;
        ?>

        <h2>Adicionar Novo Restaurante</h2>
        <div class="insert-bar">
            <form method="post" action="">
                <label for="nome">Nome do Restaurante:</label>
                <input type="text" id="nome" name="nome" placeholder="Nome do Restaurante" required>

                <label for="contato">Nome do Contato:</label>
                <input type="text" id="contato" name="contato" placeholder="Pessoa de contato no restaurante" required>

                <label for="telefone">Telefone:</label>
                <input type="tel" id="telefone" name="telefone" placeholder="Telefone (ex: 11987654321)" required>

                <label for="descricao">Descrição (Opcional):</label>
                <input id="descricao" name="descricao" placeholder="Uma breve descrição sobre o restaurante." rows="4"></input>

                <button type="submit" name="adicionar">Adicionar Restaurante</button>
                <a href="restauranteADM.php"><button type="button">Cancelar</button></a>
            </form>
        </div>
    </div>
</body>

</html>