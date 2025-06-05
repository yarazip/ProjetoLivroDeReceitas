<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../BancoDeDados/conexao.php';

// Verifica se o usuário está logado e se tem permissão de Administrador
if (!isset($_SESSION['id_login']) || $_SESSION['cargo'] !== 'Administrador') {
    $_SESSION['message'] = "Você não tem permissão para adicionar cargos.";
    $_SESSION['message_type'] = "error";
    header("Location: ../LoginSenha/login.php");
    exit;
}

// Lógica de ADICIONAR CARGO
if (isset($_POST['adicionar'])) {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];

    $conn->beginTransaction();
    try {
        $sql = "INSERT INTO cargos (nome, descricao) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nome, $descricao]);
        $conn->commit();
        $_SESSION['message'] = "Cargo '" . htmlspecialchars($nome) . "' adicionado com sucesso!";
        $_SESSION['message_type'] = "success";
        header("Location: cargosADM.php"); // Redireciona para a lista principal
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['message'] = "Erro ao adicionar cargo: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        error_log("Erro ao adicionar cargo: " . $e->getMessage());
        header("Location: adicionarCargo.php"); // Redireciona de volta para o formulário
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../styles/func.css">
    <link rel="stylesheet" href="../../styles/cargos.css">
    <title>Adicionar Cargo | ADM</title>
    <style>
        /* Estilos do formulário */
        .insert-bar form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-width: 500px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
        }
        .insert-bar input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .insert-bar button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
        }
        .insert-bar button[type="submit"] {
            background-color: #28a745; /* Verde para adicionar */
            color: white;
        }
        .insert-bar a { text-decoration: none; }
        .insert-bar a button {
            background-color: #dc3545; /* Vermelho para cancelar */
            color: white;
            margin-left: 10px;
        }
        h2 { text-align: center; margin-bottom: 20px; color: #333; }
        .message-success, .message-error {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
        }
        .message-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
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

        <h2>Adicionar Novo Cargo</h2>
        <div class="insert-bar">
            <form method="post" action="">
                <label for="nome_cargo">Nome do Cargo:</label>
                <input type="text" id="nome_cargo" name="nome" placeholder="Ex: Gerente, Cozinheiro" required>
                <label for="descricao_cargo">Descrição do Cargo:</label>
                <input type="text" id="descricao_cargo" name="descricao" placeholder="Uma breve descrição sobre as responsabilidades do cargo." required>
                <button type="submit" name="adicionar">Adicionar Cargo</button>
                <a href="cargosADM.php"><button type="button">Cancelar</button></a>
            </form>
        </div>
    </div>
</body>
</html>