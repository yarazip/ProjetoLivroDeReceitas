<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../BancoDeDados/conexao.php';

// Verifica se o usuário está logado e se tem permissão (Cozinheiro ou Administrador)
if (!isset($_SESSION['id_login']) || ($_SESSION['cargo'] !== 'cozinheiro' && $_SESSION['cargo'] !== 'administrador')) {
    $_SESSION['message'] = "Você não tem permissão para adicionar medidas.";
    $_SESSION['message_type'] = "error";
    header("Location: ../LoginSenha/login.php");
    exit;
}

// Lógica de ADICIONAR MEDIDA
if (isset($_POST['adicionar'])) {
    $descricao = $_POST['descricao'];
    $medida = $_POST['medida'];

    $conn->beginTransaction();
    try {
        $sql = "INSERT INTO medidas (descricao, medida) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$descricao, $medida]);
        $conn->commit();
        $_SESSION['message'] = "Medida '" . htmlspecialchars($descricao) . "' adicionada com sucesso!";
        $_SESSION['message_type'] = "success";
        header("Location: medidasChef.php"); // Redireciona para a lista principal
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['message'] = "Erro ao adicionar medida: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        error_log("Erro ao adicionar medida: " . $e->getMessage());
        header("Location: adicionarMedida.php"); // Redireciona de volta para o formulário
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
    <link rel="stylesheet" href="../styles/adicionarADM.css">
    <title>Adicionar Medida | Cozinheiro</title>
    
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

        <h2>Adicionar Nova Medida</h2>
        <div class="insert-bar">
            <form method="post" action="">
                <label for="descricao">Descrição da Medida:</label>
                <input type="text" id="descricao" name="descricao" placeholder="Ex: Quilogramas, Xícaras" required>
                <label for="medida">Símbolo da Medida:</label>
                <input type="text" id="medida" name="medida" placeholder="Ex: kg, xíc." required maxlength="20">
                <button type="submit" name="adicionar">Adicionar Medida</button>
                <a href="medidasChef.php"><button type="button">Cancelar</button></a>
            </form>
        </div>
    </div>
</body>
</html>