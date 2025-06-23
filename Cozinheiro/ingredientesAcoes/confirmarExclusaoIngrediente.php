<?php
session_start();
if (!isset($_SESSION['id_funcionario'])) {
    header("Location: ../LoginSenha/login.php");
    exit();
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../BancoDeDados/conexao.php';

// Verifica se o usuário está logado e se tem permissão (Cozinheiro ou Administrador)
if (!isset($_SESSION['id_login']) || ($_SESSION['cargo'] !== 'cozinheiro' && $_SESSION['cargo'] !== 'administrador')) {
    $_SESSION['message'] = "Você não tem permissão para realizar esta ação.";
    $_SESSION['message_type'] = "error";
    header("Location: ../../LoginSenha/login.php");
    exit;
}

$id_ingrediente_para_excluir = $_GET['id'] ?? null;

if (is_null($id_ingrediente_para_excluir)) {
    $_SESSION['message'] = "Ingrediente não especificado para exclusão.";
    $_SESSION['message_type'] = "error";
    header("Location: ../ingredientesChef.php");
    exit;
}

// Buscar detalhes do ingrediente para exibir na página de confirmação
try {
    $stmt = $conn->prepare("SELECT id_ingrediente, nome, descricao FROM ingredientes WHERE id_ingrediente = ?");
    $stmt->execute([$id_ingrediente_para_excluir]);
    $ingrediente_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ingrediente_info) {
        $_SESSION['message'] = "Ingrediente não encontrado para confirmação de exclusão.";
        $_SESSION['message_type'] = "error";
        header("Location: ../ingredientesChef.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar ingrediente para confirmação: " . $e->getMessage());
    $_SESSION['message'] = "Erro ao carregar detalhes do ingrediente.";
    $_SESSION['message_type'] = "error";
    header("Location: ../ingredientesChef.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Confirmar Exclusão de Ingrediente</title>
    <link rel="stylesheet" href="../../styles/excluirADM.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="shortcut icon" href="../../assets/favicon.png" type="image/x-icon">

</head>

<body>
        <a href="../LoginSenha/logout.php" class="logout-button">
        <i class="fa-solid fa-right-from-bracket fa-lg gray-icon"></i>
    </a>
    <div class="container">
        <div class="menu">
            <h1 class="logo">Código de Sabores</h1>
            <nav>
                <a href="../receitasChef.php">Receitas</a>
                <a href="../ingredientesChef.php">Ingredientes</a>
                <a href="../medidasChef.php">Medidas</a>
                <a href="../categoriaChef.php">Categorias</a>
                <a href="../../Receitas/listarReceitas.php">Página de Receitas</a>

                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?= htmlspecialchars($_SESSION['nome_funcionario'] ?? 'Desconhecido') ?></span>
                </div>
            </nav>
        </div>

        <div class="confirmation-box">
            <h2>Confirmar Exclusão de Ingrediente</h2>
            <p>Você tem certeza que deseja excluir o ingrediente:<br>
                <strong>"<?= htmlspecialchars($ingrediente_info['nome']) ?>" (ID: <?= htmlspecialchars($ingrediente_info['id_ingrediente']) ?>)</strong>?
            </p>
            <p>Esta ação é irreversível e só será possível se o ingrediente não estiver sendo usado em nenhuma receita.</p>
            <div class="buttons">
                <form action="excluirIngrediente.php" method="GET">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($id_ingrediente_para_excluir) ?>">
                    <input type="hidden" name="confirmar" value="sim">
                    <button type="submit" class="confirm-button">Sim, Excluir</button>
                </form>

                <button type="button" class="cancel-button-res" onclick="window.location.href='../ingredientesChef.php'">Cancelar</button>
            </div>
        </div>
    </div>
</body>

</html>