<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../BancoDeDados/conexao.php';

// Verifica se o usuário está logado e se tem permissão de Administrador
if (!isset($_SESSION['id_login']) || $_SESSION['cargo'] !== 'administrador') {
    $_SESSION['message'] = "Você não tem permissão para adicionar referências.";
    $_SESSION['message_type'] = "error";
    header("Location: ../LoginSenha/login.php");
    exit;
}

// Buscar funcionários e restaurantes para os selects
$funcionarios_disponiveis = $conn->query("SELECT id_funcionario, nome FROM funcionarios ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$restaurantes_disponiveis = $conn->query("SELECT id_restaurante, nome FROM restaurantes ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);


// Lógica de ADICIONAR REFERÊNCIA
if (isset($_POST['adicionar'])) {
    $id_funcionario = $_POST['id_funcionario'];
    $id_restaurante = $_POST['id_restaurante'];
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim']; // Pode ser uma string vazia ou null do formulário
    $descricao = $_POST['descricao'];

    // Validações básicas (você pode adicionar mais)
    if (empty($id_funcionario) || empty($id_restaurante) || empty($data_inicio)) {
        $_SESSION['message'] = "Erro: Campos obrigatórios (Funcionário, Restaurante, Data Início) não preenchidos.";
        $_SESSION['message_type'] = "error";
        header("Location: adicionarReferencia.php");
        exit;
    }

    $conn->beginTransaction();
    try {
        $sql = "INSERT INTO historico_restaurante (id_funcionario, id_restaurante, data_inicio, data_fim, descricao)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        // Converte string vazia de data_fim para NULL se o campo não foi preenchido
        $data_fim_db = (empty($data_fim) ? null : $data_fim);
        $descricao_db = (empty($descricao) ? null : $descricao);

        $stmt->execute([$id_funcionario, $id_restaurante, $data_inicio, $data_fim_db, $descricao_db]);
        $conn->commit();
        $_SESSION['message'] = "Referência adicionada com sucesso!";
        $_SESSION['message_type'] = "success";
        header("Location: referenciaADM.php"); // Redireciona para a lista principal
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['message'] = "Erro ao adicionar referência: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        error_log("Erro ao adicionar referência: " . $e->getMessage());
        if ($e->getCode() == '23000') { // Código para erro de chave duplicada (PK composta)
             $_SESSION['message'] = "Erro: Já existe um registro de histórico para este funcionário e restaurante com esta data de início.";
        }
        header("Location: adicionarReferencia.php"); // Redireciona de volta para o formulário
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
    <title>Adicionar Referência | ADM</title>
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
            background-color: #28a745; /* Verde para adicionar */
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

        <h2>Adicionar Nova Referência</h2>
        <div class="insert-bar">
            <form method="post" action="">
                <label for="id_funcionario">Funcionário:</label>
                <select id="id_funcionario" name="id_funcionario" required>
                    <option value="">Selecione um funcionário</option>
                    <?php foreach ($funcionarios_disponiveis as $func): ?>
                        <option value="<?= htmlspecialchars($func['id_funcionario']) ?>">
                            <?= htmlspecialchars($func['nome']) ?> (ID: <?= htmlspecialchars($func['id_funcionario']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="id_restaurante">Restaurante:</label>
                <select id="id_restaurante" name="id_restaurante" required>
                    <option value="">Selecione um restaurante</option>
                    <?php foreach ($restaurantes_disponiveis as $rest): ?>
                        <option value="<?= htmlspecialchars($rest['id_restaurante']) ?>">
                            <?= htmlspecialchars($rest['nome']) ?> (ID: <?= htmlspecialchars($rest['id_restaurante']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="data_inicio">Data de Início:</label>
                <input type="date" id="data_inicio" name="data_inicio" required>

                <label for="data_fim">Data de Fim (Opcional):</label>
                <input type="date" id="data_fim" name="data_fim">

                <label for="descricao">Descrição (Opcional):</label>
                <textarea id="descricao" name="descricao" placeholder="Detalhes sobre a atuação no restaurante." rows="4"></textarea>

                <button type="submit" name="adicionar">Adicionar Referência</button>
                <a href="referenciaADM.php"><button type="button">Cancelar</button></a>
            </form>
        </div>
    </div>
</body>
</html>