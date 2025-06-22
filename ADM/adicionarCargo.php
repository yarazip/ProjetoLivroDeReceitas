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

// Verifica se o usuário está logado e se tem permissão de Administrador
if (!isset($_SESSION['id_login']) || $_SESSION['cargo'] !== 'administrador') {
    // $_SESSION['message'] = "Você não tem permissão para adicionar cargos.";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../styles/adicionarADM.css">
    <title>Adicionar Cargo | ADM</title>

</head>

<body>
    <a href="../LoginSenha/logout.php" class="logout-button">
        <i class="fa-solid fa-right-from-bracket fa-lg gray-icon"></i>
    </a>
    <div class="container">
        <div class="menu">
            <h1>Código de Sabores</h1>
            <?php
            function isActive($page)
            {
                return basename($_SERVER['PHP_SELF']) == $page ? 'active' : '';
            }
            ?>

            <nav>
                <a href="cargosADM.php" class="<?= isActive('cargosADM.php') ?>">Cargo</a>
                <a href="restauranteADM.php" class="<?= isActive('restauranteADM.php') ?>">Restaurantes</a>
                <a href="funcionarioADM.php" class="<?= isActive('funcionarioADM.php') ?>">Funcionário</a>
                <a href="referenciaADM.php" class="<?= isActive('referenciaADM.php') ?>">Referência</a>
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