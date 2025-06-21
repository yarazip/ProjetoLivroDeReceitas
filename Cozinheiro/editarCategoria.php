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
$categoriaEditar = null;
if (isset($_GET['id'])) {
    $idEditar = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM categorias WHERE id_categoria = ?");
    $stmt->execute([$idEditar]);
    $categoriaEditar = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$categoriaEditar) {
        $_SESSION['message'] = "Categoria não encontrada para edição.";
        $_SESSION['message_type'] = "error";
        header("Location: categoriaChef.php");
        exit;
    }
} else {
    $_SESSION['message'] = "ID da categoria não fornecido para edição.";
    $_SESSION['message_type'] = "error";
    header("Location: categoriaChef.php");
    exit;
}

// Lógica para SALVAR EDIÇÃO (POST)
if (isset($_POST['salvar_edicao'])) {
    $id = $_POST['id'];
    $nome_categoria = $_POST['nome_categoria'];
    $descricao = $_POST['descricao'];

    $conn->beginTransaction();
    try {
        $sql = "UPDATE categorias SET nome_categoria = ?, descricao = ? WHERE id_categoria = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nome_categoria, $descricao, $id]);
        $conn->commit();
        $_SESSION['message'] = "Categoria '" . htmlspecialchars($nome_categoria) . "' atualizada com sucesso!";
        $_SESSION['message_type'] = "success";
        header("Location: categoriaChef.php"); // Redireciona para a lista principal
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['message'] = "Erro ao atualizar categoria: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        error_log("Erro ao atualizar categoria: " . $e->getMessage());
        header("Location: editarCategoria.php?id=" . htmlspecialchars($id)); // Redireciona de volta para o formulário de edição
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

    <link rel="stylesheet" href="../styles/edicaoADM.css">
    <title>Editar Categoria | Cozinheiro</title>

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

        <h2>Editar Categoria</h2>
        <div class="insert-bar">
            <form method="post" action="">
                <input type="hidden" name="id" value="<?= htmlspecialchars($categoriaEditar['id_categoria']) ?>">
                <input type="hidden" name="salvar_edicao" value="1"> <label for="nome_categoria">Nome da Categoria:</label>
                <input type="text" id="nome_categoria" name="nome_categoria" placeholder="Nome da Categoria" required value="<?= htmlspecialchars($categoriaEditar['nome_categoria']) ?>">
                <label for="descricao_cat">Descrição (Opcional):</label>
                <input type="text" id="descricao_cat" name="descricao" placeholder="Uma breve descrição sobre a categoria." value="<?= htmlspecialchars($categoriaEditar['descricao'] ?? '') ?>">
                <button type="submit">Salvar Alterações</button>
                <a href="categoriaChef.php"><button type="button">Cancelar</button></a>
            </form>
        </div>
    </div>
</body>

</html>