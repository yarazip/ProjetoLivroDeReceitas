<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../BancoDeDados/conexao.php';

// Verifica se o usuário está logado e se tem permissão de Administrador
if (!isset($_SESSION['id_login']) || $_SESSION['cargo'] !== 'administrador') {
    // $_SESSION['message'] = "Você não tem permissão para editar cargos.";
    $_SESSION['message_type'] = "error";
    header("Location: ../LoginSenha/login.php");
    exit;
}

// Lógica para CARREGAR DADOS PARA EDIÇÃO (GET)
$cargoParaEditar = null;
if (isset($_GET['id'])) {
    $idEditar = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM cargos WHERE id_cargo = ?");
    $stmt->execute([$idEditar]);
    $cargoParaEditar = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cargoParaEditar) {
        $_SESSION['message'] = "Cargo não encontrado para edição.";
        $_SESSION['message_type'] = "error";
        header("Location: cargosADM.php");
        exit;
    }
} else {
    $_SESSION['message'] = "ID do cargo não fornecido para edição.";
    $_SESSION['message_type'] = "error";
    header("Location: cargosADM.php");
    exit;
}

// Lógica para SALVAR EDIÇÃO (POST)
if (isset($_POST['salvar_edicao'])) { // Renomeado o name do botão para 'salvar_edicao'
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];

    $conn->beginTransaction();
    try {
        $sql = "UPDATE cargos SET nome = ?, descricao = ? WHERE id_cargo = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nome, $descricao, $id]);
        $conn->commit();
        $_SESSION['message'] = "Cargo '" . htmlspecialchars($nome) . "' atualizado com sucesso!";
        $_SESSION['message_type'] = "success";
        header("Location: cargosADM.php"); // Redireciona para a lista principal
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['message'] = "Erro ao atualizar cargo: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        error_log("Erro ao atualizar cargo: " . $e->getMessage());
        header("Location: editarCargo.php?id=" . htmlspecialchars($id)); // Redireciona de volta para o formulário de edição
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
    <link rel="stylesheet" href="../../styles/edicaoADM.css">
    <title>Editar Cargo | ADM</title>
    
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

        <h2>Editar Cargo</h2>
        <div class="insert-bar">
            <form method="post" action="">
                <input type="hidden" name="id" value="<?= htmlspecialchars($cargoParaEditar['id_cargo']) ?>">
                <input type="hidden" name="salvar_edicao" value="1"> <label for="nome_cargo">Nome do Cargo:</label>
                <input type="text" id="nome_cargo" name="nome" placeholder="Nome do Cargo" required value="<?= htmlspecialchars($cargoParaEditar['nome']) ?>">
                <label for="descricao_cargo">Descrição do Cargo:</label>
                <input type="text" id="descricao_cargo" name="descricao" placeholder="Descrição do cargo" required value="<?= htmlspecialchars($cargoParaEditar['descricao']) ?>">
                <button type="submit">Salvar Alterações</button>
                <a href="cargosADM.php"><button type="button">Cancelar</button></a>
            </form>
        </div>
    </div>
</body>
</html>