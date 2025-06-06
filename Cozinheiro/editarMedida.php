<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../BancoDeDados/conexao.php';

// Verifica se o usuário está logado e se tem permissão (Cozinheiro ou Administrador)
if (!isset($_SESSION['id_login']) || ($_SESSION['cargo'] !== 'Cozinheiro' && $_SESSION['cargo'] !== 'Administrador')) {
    $_SESSION['message'] = "Você não tem permissão para editar medidas.";
    $_SESSION['message_type'] = "error";
    header("Location: ../LoginSenha/login.php");
    exit;
}

// Lógica para CARREGAR DADOS PARA EDIÇÃO (GET)
$medida_editando = null;
if (isset($_GET['id'])) {
    $idEditar = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM medidas WHERE id_medida = ?");
    $stmt->execute([$idEditar]);
    $medida_editando = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$medida_editando) {
        $_SESSION['message'] = "Medida não encontrada para edição.";
        $_SESSION['message_type'] = "error";
        header("Location: medidasChef.php");
        exit;
    }
} else {
    $_SESSION['message'] = "ID da medida não fornecido para edição.";
    $_SESSION['message_type'] = "error";
    header("Location: medidasChef.php");
    exit;
}

// Lógica para SALVAR EDIÇÃO (POST)
if (isset($_POST['salvar_edicao'])) {
    $id = $_POST['id_medida'];
    $descricao = $_POST['descricao'];
    $medida = $_POST['medida'];

    $conn->beginTransaction();
    try {
        $sql = "UPDATE medidas SET descricao = ?, medida = ? WHERE id_medida = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$descricao, $medida, $id]);
        $conn->commit();
        $_SESSION['message'] = "Medida '" . htmlspecialchars($descricao) . "' atualizada com sucesso!";
        $_SESSION['message_type'] = "success";
        header("Location: medidasChef.php"); // Redireciona para a lista principal
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['message'] = "Erro ao atualizar medida: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        error_log("Erro ao atualizar medida: " . $e->getMessage());
        header("Location: editarMedida.php?id=" . htmlspecialchars($id)); // Redireciona de volta para o formulário de edição
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
    <title>Editar Medida | Cozinheiro</title>
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

        <h2>Editar Medida</h2>
        <div class="insert-bar">
            <form method="post" action="">
                <input type="hidden" name="id_medida" value="<?= htmlspecialchars($medida_editando['id_medida']) ?>">
                <input type="hidden" name="salvar_edicao" value="1"> <label for="descricao">Descrição da Medida:</label>
                <input type="text" id="descricao" name="descricao" placeholder="Descrição da Medida" required value="<?= htmlspecialchars($medida_editando['descricao']) ?>">
                <label for="medida">Símbolo da Medida:</label>
                <input type="text" id="medida" name="medida" placeholder="Símbolo da Medida" required maxlength="20" value="<?= htmlspecialchars($medida_editando['medida']) ?>">
                <button type="submit">Salvar Alterações</button>
                <a href="medidasChef.php"><button type="button">Cancelar</button></a>
            </form>
        </div>
    </div>
</body>
</html>