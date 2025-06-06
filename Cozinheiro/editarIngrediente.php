<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../BancoDeDados/conexao.php';

// Pega o cargo da sessão, converte para minúsculas. Usa '' se não existir.
$cargo_usuario = strtolower($_SESSION['cargo'] ?? '');

// Lista de cargos permitidos nesta página
$cargos_permitidos = ['cozinheiro', 'cozinheira', 'administrador'];

// Verifica se o usuário está logado e se o cargo dele está na lista de permitidos
if (!isset($_SESSION['id_login']) || !in_array($cargo_usuario, $cargos_permitidos)) {
    $_SESSION['message'] = "Você não tem permissão para acessar esta página.";
    $_SESSION['message_type'] = "error";
    header("Location: ../LoginSenha/login.php");
    exit;
}

// Lógica para CARREGAR DADOS PARA EDIÇÃO (GET)
$ingrediente_editar = null;
if (isset($_GET['id'])) {
    $idEditar = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM ingredientes WHERE id_ingrediente = ?");
    $stmt->execute([$idEditar]);
    $ingrediente_editar = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ingrediente_editar) {
        $_SESSION['message'] = "Ingrediente não encontrado para edição.";
        $_SESSION['message_type'] = "error";
        header("Location: ingredientesChef.php");
        exit;
    }
} else {
    $_SESSION['message'] = "ID do ingrediente não fornecido para edição.";
    $_SESSION['message_type'] = "error";
    header("Location: ingredientesChef.php");
    exit;
}

// Lógica para SALVAR EDIÇÃO (POST)
if (isset($_POST['salvar_edicao'])) {
    $id_ingrediente = $_POST['id_ingrediente'];
    $nome_ingrediente = $_POST['nome_ingrediente'];
    $descricao = $_POST['descricao'];

    $conn->beginTransaction();
    try {
        $sql = "UPDATE ingredientes SET nome = ?, descricao = ? WHERE id_ingrediente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nome_ingrediente, $descricao, $id_ingrediente]);
        $conn->commit();
        $_SESSION['message'] = "Ingrediente '" . htmlspecialchars($nome_ingrediente) . "' atualizado com sucesso!";
        $_SESSION['message_type'] = "success";
        header("Location: ingredientesChef.php"); // Redireciona para a lista principal
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['message'] = "Erro ao atualizar ingrediente: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        error_log("Erro ao atualizar ingrediente: " . $e->getMessage());
        header("Location: editarIngrediente.php?id=" . htmlspecialchars($id_ingrediente)); // Redireciona de volta para o formulário de edição
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
    <link rel="stylesheet" href="../styles/func.css">
    <title>Editar Ingrediente | Cozinheiro</title>
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
                <a href="receitasChef.php">Receitas</a>
                <a href="ingredientesChef.php">Ingredientes</a>
                <a href="medidasChef.php">Medidas</a>
                <a href="categoriaChef.php">Categorias</a>
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

        <h2>Editar Ingrediente</h2>
        <div class="insert-bar">
            <form method="post" action="">
                <input type="hidden" name="id_ingrediente" value="<?= htmlspecialchars($ingrediente_editar['id_ingrediente']) ?>">
                <input type="hidden" name="salvar_edicao" value="1"> <label for="nome_ingrediente">Nome do Ingrediente:</label>
                <input type="text" id="nome_ingrediente" name="nome_ingrediente" placeholder="Nome do Ingrediente" required value="<?= htmlspecialchars($ingrediente_editar['nome']) ?>">
                <label for="descricao_ing">Descrição (Opcional):</label>
                <input type="text" id="descricao_ing" name="descricao" placeholder="Uma breve descrição sobre o ingrediente." value="<?= htmlspecialchars($ingrediente_editar['descricao'] ?? '') ?>">
                <button type="submit">Salvar Alterações</button>
                <a href="ingredientesChef.php"><button type="button">Cancelar</button></a>
            </form>
        </div>
    </div>
</body>
</html>