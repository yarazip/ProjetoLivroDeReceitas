<?php
session_start();
if (!isset($_SESSION['id_funcionario'])) {
    header("Location: ../LoginSenha/login.php");
    exit();
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../BancoDeDados/conexao.php';

// Verifica se o usuário está logado e se tem permissão (Cozinheiro ou Administrador)
if (!isset($_SESSION['id_login']) || ($_SESSION['cargo'] !== 'cozinheiro' && $_SESSION['cargo'] !== 'administrador')) {
    $_SESSION['message'] = "Você não tem permissão para adicionar categorias.";
    $_SESSION['message_type'] = "error";
    header("Location: ../LoginSenha/login.php");
    exit;
}

// Lógica de ADICIONAR CATEGORIA
if (isset($_POST['adicionar'])) {
    $nome_categoria = $_POST['nome_categoria'];
    $descricao = $_POST['descricao'];

    $conn->beginTransaction();
    try {
        $sql = "INSERT INTO categorias (nome_categoria, descricao) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nome_categoria, $descricao]);
        $conn->commit();
        $_SESSION['message'] = "Categoria '" . htmlspecialchars($nome_categoria) . "' adicionada com sucesso!";
        $_SESSION['message_type'] = "success";
        header("Location: categoriaChef.php"); // Redireciona para a lista principal
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['message'] = "Erro ao adicionar categoria: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        error_log("Erro ao adicionar categoria: " . $e->getMessage());
        header("Location: adicionarCategoria.php"); // Redireciona de volta para o formulário
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

    <link rel="stylesheet" href="../styles/adicionarADM.css">
    <title>Adicionar Categoria | Cozinheiro</title>

</head>

<body>
        <a href="../LoginSenha/logout.php" class="logout-button">
        <i class="fa-solid fa-right-from-bracket fa-lg gray-icon"></i>
    </a>
    <div class="container">
        <div class="menu">
            <h1 class="logo">Código de Sabores</h1>
            <nav>
                <a href="receitasChef.php">Receitas</a>
                <a href="ingredientesChef.php">Ingredientes</a>
                <a href="medidasChef.php">Medidas</a>
                <a href="categoriaChef.php">Categorias</a>
                <a href="../../Receitas/listarReceitas.php">Página de Receitas</a>

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

        <h2>Adicionar Nova Categoria</h2>
        <div class="insert-bar">
            <form method="post" action="">
                <label for="nome_categoria">Nome da Categoria:</label>
                <input type="text" id="nome_categoria" name="nome_categoria" placeholder="Ex: Sobremesas, Salgados" required>
                <label for="descricao_cat">Descrição:</label>
                <input type="text" id="descricao_cat" name="descricao" placeholder="Uma breve descrição sobre a categoria.">
                <button type="submit" name="adicionar">Adicionar Categoria</button>
                <a href="categoriaChef.php"><button type="button">Cancelar</button></a>
            </form>
        </div>
    </div>
</body>

</html>