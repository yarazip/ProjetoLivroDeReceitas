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

// O ID do funcionário que está adicionando vem da sessão
$id_funcionario_logado = $_SESSION['id_funcionario'] ?? null;
$nome_funcionario_logado = $_SESSION['nome_funcionario'] ?? 'Desconhecido';

if (is_null($id_funcionario_logado)) {
    $_SESSION['message'] = "Erro: Funcionário não logado para adicionar degustação.";
    $_SESSION['message_type'] = "error";
    header("Location: receitasDegustador.php"); // Redireciona de volta para a lista se não houver ID na sessão
    exit;
}

// Buscar receitas disponíveis para o select
$receitas_disponiveis = $conn->query("SELECT nome_receita FROM receitas ORDER BY nome_receita ASC")->fetchAll(PDO::FETCH_COLUMN);

// Lógica de ADICIONAR DEGUSTAÇÃO
if (isset($_POST['adicionar'])) {
    $nome_receita = $_POST['nome_receita'];
    $data_degustacao = $_POST['data_degustacao'];
    $nota = $_POST['nota'];
    $descricao = $_POST['descricao'];

    $conn->beginTransaction();
    try {
        $sql = "INSERT INTO degustacoes (id_funcionario, nome_receita, data_degustacao, nota, descricao)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id_funcionario_logado, $nome_receita, $data_degustacao, $nota, $descricao]);
        $conn->commit();
        $_SESSION['message'] = "Degustação para '" . htmlspecialchars($nome_receita) . "' adicionada com sucesso!";
        $_SESSION['message_type'] = "success";
        header("Location: receitasDegustador.php"); // Redireciona para a lista principal
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['message'] = "Erro ao adicionar degustação: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        error_log("Erro ao adicionar degustação: " . $e->getMessage());
        if ($e->getCode() == '23000') { // Código para erro de chave duplicada (PK composta)
             $_SESSION['message'] = "Erro: Já existe uma degustação para este funcionário e receita.";
        }
        header("Location: adicionarDegustacao.php"); // Redireciona de volta para o formulário
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
    <link rel="stylesheet" href="../styles/func.css">
    <title>Adicionar Degustação | Degustador</title>
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
        .insert-bar label { display: block; margin-bottom: 5px; font-weight: bold; }
        .insert-bar input[type="text"],
        .insert-bar input[type="date"],
        .insert-bar input[type="number"],
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
        .message-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6fb; }
    </style>
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

        <h2>Adicionar Nova Degustação</h2>
        <div class="insert-bar">
            <form method="post" action="">
                <p><strong>Degustador logado:</strong> <?= htmlspecialchars($nome_funcionario_logado) ?></p>

                <label for="nome_receita">Receita Avaliada:</label>
                <select id="nome_receita" name="nome_receita" required>
                    <option value="">Selecione a Receita</option>
                    <?php foreach ($receitas_disponiveis as $receita): ?>
                        <option value="<?= htmlspecialchars($receita) ?>"><?= htmlspecialchars($receita) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="data_degustacao">Data da Degustação:</label>
                <input type="date" id="data_degustacao" name="data_degustacao" required value="<?= date('Y-m-d') ?>">

                <label for="nota">Nota (0-10):</label>
                <input type="number" id="nota" name="nota" placeholder="Ex: 8.5" step="0.1" min="0" max="10" required>

                <label for="descricao">Observações (Opcional):</label>
                <textarea id="descricao" name="descricao" placeholder="Escreva suas observações sobre a receita." rows="4"></textarea>

                <button type="submit" name="adicionar">Adicionar Degustação</button>
                <a href="receitasDegustador.php"><button type="button">Cancelar</button></a>
            </form>
        </div>
    </div>
</body>
</html>