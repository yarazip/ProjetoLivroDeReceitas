<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../BancoDeDados/conexao.php';

// Pega o cargo da sessão e converte para minúsculas. Usa '' se não existir.
$cargo_usuario = strtolower($_SESSION['cargo'] ?? '');

// Lista de cargos permitidos para esta página
$cargos_permitidos = ['degustador', 'degustadora', 'administrador'];

// Verifica se o usuário está logado e se o cargo dele está na lista de permitidos
if (!isset($_SESSION['id_login']) || !in_array($cargo_usuario, $cargos_permitidos)) {
    // Define a mensagem de erro antes de redirecionar
    $_SESSION['message'] = "Você não tem permissão para acessar esta página.";
    $_SESSION['message_type'] = "error";
    header("Location: ../LoginSenha/login.php");
    exit; // Para a execução do script imediatamente
}

// O ID do funcionário logado vem da sessão, usado para verificar permissão de edição
$id_funcionario_logado = $_SESSION['id_funcionario'] ?? null;

// Lógica para CARREGAR DADOS PARA EDIÇÃO (GET)
$degustacao_editando = null;
$id_funcionario_edit = $_GET['id_funcionario'] ?? null;
$nome_receita_edit = $_GET['nome_receita'] ?? null;

// Basicamente, apenas o degustador que fez a avaliação pode editá-la, ou um Admin
if (is_null($id_funcionario_edit) || is_null($nome_receita_edit)) {
    $_SESSION['message'] = "Parâmetros incompletos para edição de degustação.";
    $_SESSION['message_type'] = "error";
    header("Location: receitasDegustador.php");
    exit;
}

try {
    $sql = "SELECT d.*, f.nome AS nome_funcionario
            FROM degustacoes d
            JOIN funcionarios f ON d.id_funcionario = f.id_funcionario
            WHERE d.id_funcionario = ? AND d.nome_receita = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_funcionario_edit, $nome_receita_edit]);
    $degustacao_editando = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$degustacao_editando) {
        $_SESSION['message'] = "Degustação não encontrada para edição.";
        $_SESSION['message_type'] = "error";
        header("Location: receitasDegustador.php");
        exit;
    }

    // Apenas o degustador que criou ou um administrador podem editar
    if ($_SESSION['cargo'] === 'Degustador' && $id_funcionario_logado != $degustacao_editando['id_funcionario']) {
        $_SESSION['message'] = "Você só pode editar suas próprias degustações.";
        $_SESSION['message_type'] = "error";
        header("Location: receitasDegustador.php");
        exit;
    }

} catch (PDOException $e) {
    error_log("Erro ao buscar degustação para edição: " . $e->getMessage());
    $_SESSION['message'] = "Erro ao carregar detalhes da degustação: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
    header("Location: receitasDegustador.php");
    exit;
}

// Lógica para SALVAR EDIÇÃO (POST)
if (isset($_POST['salvar_edicao'])) {
    $id_funcionario_original = $_POST['id_funcionario_original']; // IDs originais da PK
    $nome_receita_original = $_POST['nome_receita_original']; // IDs originais da PK

    // Novos valores
    $data_degustacao = $_POST['data_degustacao'];
    $nota = $_POST['nota'];
    $descricao = $_POST['descricao'];

    $conn->beginTransaction();
    try {
        $sql = "UPDATE degustacoes
                SET data_degustacao = ?, nota = ?, descricao = ?
                WHERE id_funcionario = ? AND nome_receita = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$data_degustacao, $nota, $descricao, $id_funcionario_original, $nome_receita_original]);
        $conn->commit();
        $_SESSION['message'] = "Degustação para '" . htmlspecialchars($nome_receita_original) . "' atualizada com sucesso!";
        $_SESSION['message_type'] = "success";
        header("Location: receitasDegustador.php"); // Redireciona para a lista principal
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['message'] = "Erro ao atualizar degustação: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        error_log("Erro ao atualizar degustação: " . $e->getMessage());
        header("Location: editarDegustacao.php?id_funcionario=" . htmlspecialchars($id_funcionario_original) . "&nome_receita=" . urlencode($nome_receita_original)); // Redireciona de volta para o formulário
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
    <link rel="stylesheet" href="../styles/edicaoADM.css">
    <title>Editar Degustação | Degustador</title>
    
</head>
<body>
    <div class="container">
        <div class="menu">
            <h1 class="logo">Código de Sabores</h1>
            <nav>
                <a href="receitasDegustador.php">Degustações</a>
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

        <h2>Editar Degustação</h2>
        <div class="insert-bar">
            <form method="post" action="">
                <input type="hidden" name="id_funcionario_original" value="<?= htmlspecialchars($degustacao_editando['id_funcionario']) ?>">
                <input type="hidden" name="nome_receita_original" value="<?= htmlspecialchars($degustacao_editando['nome_receita']) ?>">
                <input type="hidden" name="salvar_edicao" value="1"> <p><strong>Funcionário:</strong> <?= htmlspecialchars($degustacao_editando['nome_funcionario'] ?? 'N/A') ?> (ID: <?= htmlspecialchars($degustacao_editando['id_funcionario'] ?? 'N/A') ?>)</p>
                <p><strong>Receita:</strong> <?= htmlspecialchars($degustacao_editando['nome_receita'] ?? 'N/A') ?></p>

                <label for="data_degustacao">Data da Degustação:</label>
                <input type="date" id="data_degustacao" name="data_degustacao" required value="<?= htmlspecialchars($degustacao_editando['data_degustacao']) ?>">

                <label for="nota">Nota (0-10):</label>
                <input type="number" id="nota" name="nota" placeholder="Ex: 8.5" step="0.1" min="0" max="10" required value="<?= htmlspecialchars($degustacao_editando['nota']) ?>">

                <label for="descricao">Observações (Opcional):</label>
                <textarea id="descricao" name="descricao" placeholder="Escreva suas observações sobre a receita." rows="4"><?= htmlspecialchars($degustacao_editando['descricao'] ?? '') ?></textarea>

                <button type="submit">Salvar Alterações</button>
                <a href="receitasDegustador.php"><button type="button">Cancelar</button></a>
            </form>
        </div>
    </div>
</body>
</html>