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

// Verifica se o usuário está logado e se tem permissão (Degustador ou Administrador)
if (!isset($_SESSION['id_login']) || ($_SESSION['cargo'] !== 'degustador' && $_SESSION['cargo'] !== 'administrador')) {
    $_SESSION['message'] = "Você não tem permissão para realizar esta ação.";
    $_SESSION['message_type'] = "error";
    header("Location: ../../LoginSenha/login.php");
    exit;
}

$id_funcionario_excluir = $_GET['id_funcionario'] ?? null;
$nome_receita_excluir = $_GET['nome_receita'] ?? null;

if (is_null($id_funcionario_excluir) || is_null($nome_receita_excluir)) {
    $_SESSION['message'] = "Degustação não especificada para exclusão.";
    $_SESSION['message_type'] = "error";
    header("Location: ../receitasDegustador.php");
    exit;
}

// Buscar detalhes da degustação para exibir na página de confirmação
try {
    $sql = "SELECT d.*, f.nome AS nome_funcionario, r.nome_receita AS nome_receita_completo
            FROM degustacoes d
            JOIN funcionarios f ON d.id_funcionario = f.id_funcionario
            JOIN receitas r ON d.nome_receita = r.nome_receita
            WHERE d.id_funcionario = ? AND d.nome_receita = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_funcionario_excluir, $nome_receita_excluir]);
    $degustacao_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$degustacao_info) {
        $_SESSION['message'] = "Degustação não encontrada para confirmação de exclusão.";
        $_SESSION['message_type'] = "error";
        header("Location: ../receitasDegustador.php");
        exit;
    }

    // Apenas o degustador que criou ou um administrador podem excluir
    if ($_SESSION['cargo'] === 'Degustador' && ($_SESSION['id_funcionario'] ?? null) != $degustacao_info['id_funcionario']) {
        $_SESSION['message'] = "Você só pode excluir suas próprias degustações.";
        $_SESSION['message_type'] = "error";
        header("Location: ../receitasDegustador.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar degustação para confirmação: " . $e->getMessage());
    $_SESSION['message'] = "Erro ao carregar detalhes da degustação.";
    $_SESSION['message_type'] = "error";
    header("Location: ../receitasDegustador.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Confirmar Exclusão de Degustação</title>
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
                <a href="../receitasDegustador.php">Degustações</a>
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?= htmlspecialchars($_SESSION['nome_funcionario'] ?? 'Desconhecido') ?></span>
                </div>
            </nav>
        </div>

        <div class="confirmation-box">
            <h2>Confirmar Exclusão de Degustação</h2>
            <p>Você tem certeza que deseja excluir a avaliação da receita <strong>"<?= htmlspecialchars($degustacao_info['nome_receita_completo']) ?>"</strong> feita por <strong><?= htmlspecialchars($degustacao_info['nome_funcionario']) ?></strong> (Nota: <?= htmlspecialchars($degustacao_info['nota']) ?>)?</p>
            <p>Esta ação é irreversível.</p>
            <div class="buttons">
                <form action="excluirDegustacao.php" method="GET">
                    <input type="hidden" name="id_funcionario" value="<?= htmlspecialchars($id_funcionario_excluir) ?>">
                    <input type="hidden" name="nome_receita" value="<?= htmlspecialchars($nome_receita_excluir) ?>">
                    <input type="hidden" name="confirmar" value="sim">
                    <button type="submit" class="confirm-button">Sim, Excluir</button>
                </form>

                <a href="../receitasDegustador.php" class="cancel-button"><button type="button" class="cancel-button">Cancelar</button></a>
            </div>
        </div>
    </div>
</body>

</html>