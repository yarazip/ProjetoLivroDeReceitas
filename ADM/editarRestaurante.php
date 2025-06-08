<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../BancoDeDados/conexao.php';

// Verifica se o usuário está logado e se tem permissão de Administrador
if (!isset($_SESSION['id_login']) || $_SESSION['cargo'] !== 'administrador') {
    // $_SESSION['message'] = "Você não tem permissão para editar restaurantes.";
    $_SESSION['message_type'] = "error";
    header("Location: ../LoginSenha/login.php");
    exit;
}

// Lógica para CARREGAR DADOS PARA EDIÇÃO (GET)
$restauranteEditar = null;
if (isset($_GET['id'])) {
    $idEditar = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM restaurantes WHERE id_restaurante = ?");
    $stmt->execute([$idEditar]);
    $restauranteEditar = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$restauranteEditar) {
        $_SESSION['message'] = "Restaurante não encontrado para edição.";
        $_SESSION['message_type'] = "error";
        header("Location: restauranteADM.php");
        exit;
    }
} else {
    $_SESSION['message'] = "ID do restaurante não fornecido para edição.";
    $_SESSION['message_type'] = "error";
    header("Location: restauranteADM.php");
    exit;
}

// Lógica para SALVAR EDIÇÃO (POST)
if (isset($_POST['salvar_edicao'])) { // Renomeado o name do botão para 'salvar_edicao'
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $contato = $_POST['contato'];
    $telefone = $_POST['telefone'];
    $descricao = $_POST['descricao'];

    $conn->beginTransaction();
    try {
        $sql = "UPDATE restaurantes SET nome = ?, contato = ?, telefone = ?, descricao = ? WHERE id_restaurante = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nome, $contato, $telefone, $descricao, $id]);
        $conn->commit();
        $_SESSION['message'] = "Restaurante '" . htmlspecialchars($nome) . "' atualizado com sucesso!";
        $_SESSION['message_type'] = "success";
        header("Location: restauranteADM.php"); // Redireciona para a lista principal
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['message'] = "Erro ao atualizar restaurante: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        error_log("Erro ao atualizar restaurante: " . $e->getMessage());
        header("Location: editarRestaurante.php?id=" . htmlspecialchars($id)); // Redireciona de volta para o formulário de edição
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
    <link rel="stylesheet" href="../../styles/func.css">
    <link rel="stylesheet" href="../styles/restaurante.css">
    <title>Editar Restaurante | ADM</title>
    <style>
        /* Estilos do formulário (adaptados de restaurante.css ou inline) */
        .insert-bar form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
        }
        .insert-bar input[type="text"],
        .insert-bar input[type="tel"],
        .insert-bar textarea {
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
            background-color: #007bff; /* Azul para salvar */
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

        <h2>Editar Restaurante</h2>
        <div class="insert-bar">
            <form method="post" action="">
                <input type="hidden" name="id" value="<?= htmlspecialchars($restauranteEditar['id_restaurante']) ?>">
                <input type="hidden" name="salvar_edicao" value="1"> <label for="nome">Nome do Restaurante:</label>
                <input type="text" id="nome" name="nome" placeholder="Nome do Restaurante" required value="<?= htmlspecialchars($restauranteEditar['nome']) ?>">

                <label for="contato">Nome do Contato:</label>
                <input type="text" id="contato" name="contato" placeholder="Pessoa de contato no restaurante" required value="<?= htmlspecialchars($restauranteEditar['contato']) ?>">

                <label for="telefone">Telefone:</label>
                <input type="tel" id="telefone" name="telefone" placeholder="Telefone (ex: 11987654321)" required value="<?= htmlspecialchars($restauranteEditar['telefone']) ?>">

                <label for="descricao">Descrição (Opcional):</label>
                <textarea id="descricao" name="descricao" placeholder="Uma breve descrição sobre o restaurante." rows="4"><?= htmlspecialchars($restauranteEditar['descricao'] ?? '') ?></textarea>

                <button type="submit">Salvar Alterações</button>
                <a href="restauranteADM.php"><button type="button">Cancelar</button></a>
            </form>
        </div>
    </div>
</body>
</html>