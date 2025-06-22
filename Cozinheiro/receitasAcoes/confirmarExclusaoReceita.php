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

// Verifica se o usuário está logado como cozinheiro (ou outro cargo autorizado para excluir)
if (!isset($_SESSION['id_login']) || $_SESSION['cargo'] !== 'cozinheiro') {
    $_SESSION['message'] = "Você não tem permissão para realizar esta ação.";
    $_SESSION['message_type'] = "error";
    header("Location: ../../LoginSenha/login.php");
    exit;
}

$nome_receita_para_excluir = $_GET['nome'] ?? null;

// Se o nome da receita não foi fornecido na URL, redireciona de volta
if (is_null($nome_receita_para_excluir)) {
    $_SESSION['message'] = "Receita não especificada para exclusão.";
    $_SESSION['message_type'] = "error";
    header("Location: ../receitasChef.php");
    exit;
}

// Opcional: Buscar mais detalhes da receita para exibir na página de confirmação
// Por exemplo, para mostrar ao usuário mais informações antes de confirmar
try {
    $stmt = $conn->prepare("SELECT nome_receita, descricao FROM receitas WHERE nome_receita = ?");
    $stmt->execute([$nome_receita_para_excluir]);
    $receita_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receita_info) {
        $_SESSION['message'] = "Receita não encontrada para confirmação de exclusão.";
        $_SESSION['message_type'] = "error";
        header("Location: ../receitasChef.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar receita para confirmação: " . $e->getMessage());
    $_SESSION['message'] = "Erro ao carregar detalhes da receita.";
    $_SESSION['message_type'] = "error";
    header("Location: ../receitasChef.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Confirmar Exclusão de Receita</title>
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
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?= htmlspecialchars($_SESSION['nome_funcionario'] ?? 'Desconhecido') ?></span>
                </div>
            </nav>
        </div>

        <div class="confirmation-box">
            <h2>Confirmar Exclusão</h2>
            <p>Você tem certeza que deseja excluir a receita:<br>
                <strong>"<?= htmlspecialchars($receita_info['nome_receita']) ?>"</strong>?
            </p>
            <p>Esta ação é irreversível.</p>
            <div class="buttons">
                <form action="excluir.php" method="GET">
                    <input type="hidden" name="excluir" value="<?= htmlspecialchars($nome_receita_para_excluir) ?>">
                    <input type="hidden" name="confirmar" value="sim">
                    <button type="submit" class="confirm-button">Sim, Excluir</button>
                </form>

                <button type="button" class="cancel-button-res" onclick="window.location.href='../receitasChef.php'">Cancelar</button>
            </div>
        </div>
    </div>
</body>

</html>