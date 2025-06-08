<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../BancoDeDados/conexao.php';

// Verifica se o usuário está logado e se tem permissão de Administrador
if (!isset($_SESSION['id_login']) || $_SESSION['cargo'] !== 'administrador') {
    // $_SESSION['message'] = "Você não tem permissão para editar referências.";
    $_SESSION['message_type'] = "error";
    header("Location: ../LoginSenha/login.php");
    exit;
}

// Buscar funcionários e restaurantes para os selects (para exibir nomes)
$funcionarios_disponiveis = $conn->query("SELECT id_funcionario, nome FROM funcionarios ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$restaurantes_disponiveis = $conn->query("SELECT id_restaurante, nome FROM restaurantes ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);


// Lógica para CARREGAR DADOS PARA EDIÇÃO (GET)
$registroEditar = null;
$id_func_editar = $_GET['id_func'] ?? null;
$id_rest_editar = $_GET['id_rest'] ?? null;

if (is_null($id_func_editar) || is_null($id_rest_editar)) {
    $_SESSION['message'] = "Parâmetros incompletos para edição de referência.";
    $_SESSION['message_type'] = "error";
    header("Location: referenciaADM.php");
    exit;
}

try {
    $sql = "SELECT * FROM historico_restaurante WHERE id_funcionario = ? AND id_restaurante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_func_editar, $id_rest_editar]);
    $registroEditar = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$registroEditar) {
        $_SESSION['message'] = "Referência não encontrada para edição.";
        $_SESSION['message_type'] = "error";
        header("Location: referenciaADM.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar referência para edição: " . $e->getMessage());
    $_SESSION['message'] = "Erro ao carregar detalhes da referência.";
    $_SESSION['message_type'] = "error";
    header("Location: referenciaADM.php");
    exit;
}


// Lógica para SALVAR EDIÇÃO (POST)
if (isset($_POST['atualizar'])) { // O name do botão no formulário é 'atualizar'
    $id_funcionario = $_POST['id_funcionario'];
    $id_restaurante = $_POST['id_restaurante']; // Estes são os IDs originais (readonly)
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    $descricao = $_POST['descricao'];

    $conn->beginTransaction();
    try {
        $sql = "UPDATE historico_restaurante SET data_inicio = ?, data_fim = ?, descricao = ?
                WHERE id_funcionario = ? AND id_restaurante = ?"; // WHERE usa os IDs originais
        $stmt = $conn->prepare($sql);
        $data_fim_db = (empty($data_fim) ? null : $data_fim);
        $descricao_db = (empty($descricao) ? null : $descricao);

        $stmt->execute([$data_inicio, $data_fim_db, $descricao_db, $id_funcionario, $id_restaurante]);
        $conn->commit();
        $_SESSION['message'] = "Referência atualizada com sucesso!";
        $_SESSION['message_type'] = "success";
        header("Location: referenciaADM.php"); // Redireciona para a lista principal
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['message'] = "Erro ao atualizar referência: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        error_log("Erro ao atualizar referência: " . $e->getMessage());
        header("Location: editarReferencia.php?id_func=" . htmlspecialchars($id_funcionario) . "&id_rest=" . htmlspecialchars($id_restaurante)); // Redireciona de volta para o formulário
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
    <link rel="stylesheet" href="../../styles/func.css">
    <link rel="stylesheet" href="../styles/referencia.css">
    <title>Editar Referência | ADM</title>
    <style>
        /* Estilos do formulário */
        .insert-bar form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
        }
        .insert-bar input[type="text"],
        .insert-bar input[type="number"],
        .insert-bar input[type="date"],
        .insert-bar select,
        .insert-bar textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .insert-bar button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
        }
        .insert-bar button[type="submit"] {
            background-color: #007bff; /* Azul para salvar */
            color: white;
        }
        .insert-bar a { text-decoration: none; }
        .insert-bar a button {
            background-color: #dc3545; /* Vermelho para cancelar */
            color: white;
            margin-left: 10px;
        }
        h2 { text-align: center; margin-bottom: 20px; color: #333; }
        .message-success, .message-error {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
        }
        .message-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
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

        <h2>Editar Referência</h2>
        <div class="insert-bar">
            <form method="post" action="">
                <input type="hidden" name="id_funcionario" value="<?= htmlspecialchars($registroEditar['id_funcionario']) ?>">
                <input type="hidden" name="id_restaurante" value="<?= htmlspecialchars($registroEditar['id_restaurante']) ?>">
                <input type="hidden" name="atualizar" value="1"> <label for="id_funcionario_display">Funcionário (ID):</label>
                <input type="text" id="id_funcionario_display" value="<?= htmlspecialchars($registroEditar['id_funcionario']) ?>" readonly>
                <?php
                $funcionario_nome = '';
                foreach ($funcionarios_disponiveis as $func) {
                    if ($func['id_funcionario'] == $registroEditar['id_funcionario']) {
                        $funcionario_nome = $func['nome'];
                        break;
                    }
                }
                if ($funcionario_nome): ?>
                    <p style="margin-top:-10px; font-size:0.9em;">(<?= htmlspecialchars($funcionario_nome) ?>)</p>
                <?php endif; ?>

                <label for="id_restaurante_display">Restaurante (ID):</label>
                <input type="text" id="id_restaurante_display" value="<?= htmlspecialchars($registroEditar['id_restaurante']) ?>" readonly>
                <?php
                $restaurante_nome = '';
                foreach ($restaurantes_disponiveis as $rest) {
                    if ($rest['id_restaurante'] == $registroEditar['id_restaurante']) {
                        $restaurante_nome = $rest['nome'];
                        break;
                    }
                }
                if ($restaurante_nome): ?>
                    <p style="margin-top:-10px; font-size:0.9em;">(<?= htmlspecialchars($restaurante_nome) ?>)</p>
                <?php endif; ?>

                <label for="data_inicio">Data de Início:</label>
                <input type="date" id="data_inicio" name="data_inicio" required value="<?= htmlspecialchars($registroEditar['data_inicio']) ?>">

                <label for="data_fim">Data de Fim (Opcional):</label>
                <input type="date" id="data_fim" name="data_fim" value="<?= htmlspecialchars($registroEditar['data_fim'] ?? '') ?>">

                <label for="descricao">Descrição (Opcional):</label>
                <textarea id="descricao" name="descricao" placeholder="Detalhes sobre a atuação no restaurante." rows="4"><?= htmlspecialchars($registroEditar['descricao'] ?? '') ?></textarea>

                <button type="submit">Atualizar</button>
                <a href="referenciaADM.php"><button type="button">Cancelar</button></a>
            </form>
        </div>
    </div>
</body>
</html>