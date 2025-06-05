<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../BancoDeDados/conexao.php';

// Verifica se o usuário está logado e se tem permissão de Administrador
if (!isset($_SESSION['id_login']) || $_SESSION['cargo'] !== 'Administrador') {
    $_SESSION['message'] = "Você não tem permissão para adicionar funcionários.";
    $_SESSION['message_type'] = "error";
    header("Location: ../LoginSenha/login.php");
    exit;
}

// Lógica de ADICIONAR FUNCIONÁRIO
if (isset($_POST['adicionar'])) {
    $cpf = $_POST['cpf'];
    $nome = $_POST['nome'];
    $data_admissao = $_POST['data_admissao'];
    $salario = $_POST['salario'];
    $descricao = $_POST['descricao'];
    $id_cargo = $_POST['id_cargo'];
    $email = $_POST['email'];
    $senha = $_POST['senha']; // Senha em texto puro! HASH AQUI EM PRODUÇÃO!

    // Upload da foto em base64 (opcional)
    $foto = null;
    if (isset($_FILES['foto_funcionario']) && $_FILES['foto_funcionario']['error'] === UPLOAD_ERR_OK && !empty($_FILES['foto_funcionario']['tmp_name'])) {
        $foto = file_get_contents($_FILES['foto_funcionario']['tmp_name']);
    }

    $conn->beginTransaction();
    try {
        // 1. Inserir na tabela funcionarios
        $sqlFunc = "INSERT INTO funcionarios (CPF, nome, salario, data_admissao, foto_funcionario, descricao, id_cargo)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtFunc = $conn->prepare($sqlFunc);
        $stmtFunc->execute([$cpf, $nome, $salario, $data_admissao, $foto, $descricao, $id_cargo]);

        $id_funcionario = $conn->lastInsertId(); // Pega o ID do funcionário recém-inserido

        // 2. Inserir na tabela logins
        // IMPORTANTE: Em um ambiente de produção, SEMPRE use password_hash() para armazenar a senha!
        // Ex: $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $sqlLogin = "INSERT INTO logins (email, senha, id_funcionario) VALUES (?, ?, ?)";
        $stmtLogin = $conn->prepare($sqlLogin);
        $stmtLogin->execute([$email, $senha, $id_funcionario]); // Use $senha_hash aqui em produção

        $conn->commit();
        $_SESSION['message'] = "Funcionário '" . htmlspecialchars($nome) . "' adicionado com sucesso!";
        $_SESSION['message_type'] = "success";
        header("Location: funcionarioADM.php");
        exit;

    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Erro ao adicionar funcionário: " . $e->getMessage());
        $_SESSION['message'] = "Erro ao adicionar funcionário: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        // Se for um erro de CPF ou email duplicado, você pode dar uma mensagem mais específica
        if ($e->getCode() == '23000') { // Código para erro de chave duplicada
             $_SESSION['message'] = "Erro: CPF ou Email já cadastrado.";
        }
        header("Location: adicionarFuncionario.php"); // Redireciona de volta para o formulário de adição
        exit;
    }
}

// Buscar cargos para o select
$cargos = $conn->query("SELECT id_cargo, nome FROM cargos")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="../../styles/func.css" /> <link rel="stylesheet" href="../../styles/funcionario.css" /> <title>Adicionar Funcionário | ADM</title>
    <style>
        /* Estilos adicionais para o layout do formulário */
        .insert-bar form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-width: 600px; /* Limita a largura do formulário */
            margin: 20px auto; /* Centraliza */
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
        }
        .form-row input, .form-row select, .form-row textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Inclui padding e borda na largura */
        }
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end; /* Alinha botões à direita */
        }
        .form-actions button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
        }
        .form-actions button[type="submit"] {
            background-color: #28a745; /* Verde para adicionar */
            color: white;
        }
        .form-actions a {
            text-decoration: none;
        }
        .form-actions a button {
            background-color: #dc3545; /* Vermelho para cancelar */
            color: white;
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

    <h2>Adicionar Novo Funcionário</h2>
    <div class="insert-bar">
        <form method="post" enctype="multipart/form-data">
            <div class="form-row">
                <label for="cpf">CPF:</label>
                <input type="text" id="cpf" name="cpf" placeholder="CPF (somente números)" required pattern="\d{11}" title="O CPF deve conter 11 dígitos numéricos." maxlength="11" />
            </div>

            <div class="form-row">
                <label for="nome">Nome Completo:</label>
                <input type="text" id="nome" name="nome" placeholder="Nome do funcionário" required />
            </div>

            <div class="form-row">
                <label for="data_admissao">Data de Admissão:</label>
                <input type="date" id="data_admissao" name="data_admissao" required />
            </div>

            <div class="form-row">
                <label for="salario">Salário:</label>
                <input type="number" id="salario" name="salario" placeholder="Salário (ex: 2500.00)" step="0.01" min="0" required />
            </div>

            <div class="form-row">
                <label for="id_cargo">Cargo:</label>
                <select id="id_cargo" name="id_cargo" required>
                    <option value="">Selecione o cargo</option>
                    <?php foreach ($cargos as $cargo): ?>
                        <option value="<?= htmlspecialchars($cargo['id_cargo']) ?>">
                            <?= htmlspecialchars($cargo['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="Email (será o login)" required />
            </div>

            <div class="form-row">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" placeholder="Senha" required />
            </div>

            <div class="form-row">
                <label for="descricao">Descrição (Opcional):</label>
                <textarea id="descricao" name="descricao" placeholder="Breve descrição sobre o funcionário"></textarea>
            </div>

            <div class="form-row">
                <label for="foto_funcionario">Foto do Funcionário (Opcional):</label>
                <input type="file" id="foto_funcionario" name="foto_funcionario" accept="image/*" />
            </div>

            <div class="form-actions">
                <button type="submit" name="adicionar">Adicionar Funcionário</button>
                <a href="funcionarioADM.php"><button type="button">Cancelar</button></a>
            </div>
        </form>
    </div>
</div>
</body>
</html>