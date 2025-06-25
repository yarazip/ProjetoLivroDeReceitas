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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="../styles/edicaoADM.css">
    <title>Editar Medida | Cozinheiro</title>

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

        <h2>Editar Medida</h2>
        <div class="insert-bar">
            <form method="post" action="">
                <input type="hidden" name="id_medida" value="<?= htmlspecialchars($medida_editando['id_medida']) ?>">
                <input type="hidden" name="salvar_edicao" value="1"> <label for="descricao">Descrição da Medida:</label>
                <input type="text" id="descricao" name="descricao" placeholder="Descrição da Medida" required value="<?= htmlspecialchars($medida_editando['descricao']) ?>">
                <label for="medida">Símbolo da Medida:</label>
                <input type="text" id="medida" name="medida" placeholder="Símbolo da Medida" value="<?= htmlspecialchars($medida_editando['medida']) ?>">
                <button type="submit">Salvar Alterações</button>
                <a href="medidasChef.php"><button type="button">Cancelar</button></a>
            </form>
        </div>
    </div>
</body>

</html>