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

$id_medida_para_excluir = $_GET['id'] ?? null;

if (is_null($id_medida_para_excluir)) {
    $_SESSION['message'] = "Medida não especificada para exclusão.";
    $_SESSION['message_type'] = "error";
    header("Location: ../medidasChef.php");
    exit;
}

// Buscar detalhes da medida para exibir na página de confirmação
try {
    $stmt = $conn->prepare("SELECT id_medida, descricao, medida FROM medidas WHERE id_medida = ?");
    $stmt->execute([$id_medida_para_excluir]);
    $medida_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$medida_info) {
        $_SESSION['message'] = "Medida não encontrada para confirmação de exclusão.";
        $_SESSION['message_type'] = "error";
        header("Location: ../medidasChef.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar medida para confirmação: " . $e->getMessage());
    $_SESSION['message'] = "Erro ao carregar detalhes da medida.";
    $_SESSION['message_type'] = "error";
    header("Location: ../medidasChef.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Confirmar Exclusão de Medida</title>
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
            <h2>Confirmar Exclusão de Medida</h2>
            <p>Você tem certeza que deseja excluir a medida:<br>
                <strong>"<?= htmlspecialchars($medida_info['descricao']) ?> (<?= htmlspecialchars($medida_info['medida']) ?>)" (ID: <?= htmlspecialchars($medida_info['id_medida']) ?>)</strong>?
            </p>
            <p>Esta ação é irreversível e só será possível se a medida não estiver sendo usada em nenhuma receita.</p>
            <div class="buttons">
                <form action="excluirMedida.php" method="GET">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($id_medida_para_excluir) ?>">
                    <input type="hidden" name="confirmar" value="sim">
                    <button type="submit" class="confirm-button">Sim, Excluir</button>
                </form>

                <button type="button" class="cancel-button-res" onclick="window.location.href='../medidasChef.php'">Cancelar</button>
            </div>
        </div>
    </div>
</body>

</html>