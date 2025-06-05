<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../BancoDeDados/conexao.php';

// Verifica se o usuário está logado e se tem permissão de Administrador
if (!isset($_SESSION['id_login']) || $_SESSION['cargo'] !== 'Administrador') {
    $_SESSION['message'] = "Você não tem permissão para editar funcionários.";
    $_SESSION['message_type'] = "error";
    header("Location: ../LoginSenha/login.php");
    exit;
}

// Lógica de ATUALIZAR FUNCIONÁRIO (POST)
if (isset($_POST['salvar_edicao'])) { // Renomeado o name do botão para 'salvar_edicao'
    $id = $_POST['id_funcionario']; // Pegando o ID do input hidden
    $cpf = $_POST['cpf'];
    $nome = $_POST['nome'];
    $data_admissao = $_POST['data_admissao'];
    $salario = $_POST['salario'];
    $descricao = $_POST['descricao'];
    $id_cargo = $_POST['id_cargo'];
    $email = $_POST['email'];
    // Senha não é editada diretamente aqui, mas pode ser adicionada lógica para "trocar senha"

    $conn->beginTransaction();
    try {
        // Atualizar na tabela funcionarios
        $sqlFunc = "UPDATE funcionarios SET CPF = ?, nome = ?, salario = ?, data_admissao = ?, descricao = ?, id_cargo = ? WHERE id_funcionario = ?";
        $paramsFunc = [$cpf, $nome, $salario, $data_admissao, $descricao, $id_cargo, $id];

        // Se uma nova foto foi enviada, atualiza a foto também
        if (isset($_FILES['foto_funcionario']) && $_FILES['foto_funcionario']['error'] === UPLOAD_ERR_OK && !empty($_FILES['foto_funcionario']['tmp_name'])) {
            $foto = file_get_contents($_FILES['foto_funcionario']['tmp_name']);
            $sqlFunc = "UPDATE funcionarios SET CPF = ?, nome = ?, salario = ?, data_admissao = ?, foto_funcionario = ?, descricao = ?, id_cargo = ? WHERE id_funcionario = ?";
            array_splice($paramsFunc, 4, 0, [$foto]); // Insere a foto na posição correta do array
        }
        $stmtFunc = $conn->prepare($sqlFunc);
        $stmtFunc->execute($paramsFunc);

        // Atualizar ou criar login na tabela logins
        // Verifica se já existe login para este funcionário
        $sqlCheckLogin = "SELECT COUNT(*) FROM logins WHERE id_funcionario = ?";
        $stmtCheckLogin = $conn->prepare($sqlCheckLogin);
        $stmtCheckLogin->execute([$id]);
        $loginExists = $stmtCheckLogin->fetchColumn();

        if ($loginExists) {
            $sqlLogin = "UPDATE logins SET email = ? WHERE id_funcionario = ?";
            $stmtLogin = $conn->prepare($sqlLogin);
            $stmtLogin->execute([$email, $id]);
        } else {
            // Se não existe login, cria um. Senha será vazia ou padrão, ou solicitar nova senha aqui.
            // Para um caso real, você precisaria de um campo de senha obrigatório aqui ou um processo de recuperação.
            $sqlLogin = "INSERT INTO logins (email, id_funcionario) VALUES (?, ?)"; // Senha será NULL ou padrão
            $stmtLogin = $conn->prepare($sqlLogin);
            $stmtLogin->execute([$email, $id]);
        }

        $conn->commit();
        $_SESSION['message'] = "Funcionário '" . htmlspecialchars($nome) . "' atualizado com sucesso!";
        $_SESSION['message_type'] = "success";
        header("Location: funcionarioADM.php");
        exit;

    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Erro ao atualizar funcionário: " . $e->getMessage());
        $_SESSION['message'] = "Erro ao atualizar funcionário: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        if ($e->getCode() == '23000') { // Código para erro de chave duplicada
             $_SESSION['message'] = "Erro: CPF ou Email já cadastrado para outro funcionário.";
        }
        header("Location: editarFuncionario.php?id=" . htmlspecialchars($id)); // Retorna para a página de edição
        exit;
    }
}

// Lógica de BUSCAR FUNCIONÁRIO PARA EDIÇÃO (GET)
$funcionarioEditar = null;
$loginEditar = null; // Inicializa fora do escopo do if

if (isset($_GET['id'])) {
    $idEditar = $_GET['id'];

    // Buscar dados do funcionário
    $sqlFunc = "SELECT * FROM funcionarios WHERE id_funcionario = ?";
    $stmtFunc = $conn->prepare($sqlFunc);
    $stmtFunc->execute([$idEditar]);
    $funcionarioEditar = $stmtFunc->fetch(PDO::FETCH_ASSOC);

    // Buscar dados de login (se existirem)
    $sqlLogin = "SELECT email FROM logins WHERE id_funcionario = ?";
    $stmtLogin = $conn->prepare($sqlLogin);
    $stmtLogin->execute([$idEditar]);
    $loginEditar = $stmtLogin->fetch(PDO::FETCH_ASSOC);

    if (!$funcionarioEditar) {
        $_SESSION['message'] = "Funcionário não encontrado para edição.";
        $_SESSION['message_type'] = "error";
        header("Location: funcionarioADM.php");
        exit;
    }
} else {
    $_SESSION['message'] = "ID do funcionário não fornecido para edição.";
    $_SESSION['message_type'] = "error";
    header("Location: funcionarioADM.php");
    exit;
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
    <link rel="stylesheet" href="../../styles/func.css" />
    <link rel="stylesheet" href="../../styles/funcionario.css" />
    <title>Editar Funcionário | ADM</title>
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
            background-color: #007bff; /* Azul para salvar alterações */
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
        .current-photo {
            margin-top: 10px;
            text-align: center;
        }
        .current-photo img {
            max-width: 100px;
            height: auto;
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 4px;
        }
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

    <h2>Editar Funcionário</h2>
    <div class="insert-bar">
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="id_funcionario" value="<?= htmlspecialchars($funcionarioEditar['id_funcionario']) ?>" />
            <input type="hidden" name="salvar_edicao" value="1"> <div class="form-row">
                <label for="cpf">CPF:</label>
                <input type="text" id="cpf" name="cpf" placeholder="CPF (somente números)" required pattern="\d{11}" title="O CPF deve conter 11 dígitos numéricos." maxlength="11" value="<?= htmlspecialchars($funcionarioEditar['CPF'] ?? '') ?>" />
            </div>

            <div class="form-row">
                <label for="nome">Nome Completo:</label>
                <input type="text" id="nome" name="nome" placeholder="Nome do funcionário" required value="<?= htmlspecialchars($funcionarioEditar['nome'] ?? '') ?>" />
            </div>

            <div class="form-row">
                <label for="data_admissao">Data de Admissão:</label>
                <input type="date" id="data_admissao" name="data_admissao" required value="<?= htmlspecialchars($funcionarioEditar['data_admissao'] ?? '') ?>" />
            </div>

            <div class="form-row">
                <label for="salario">Salário:</label>
                <input type="number" id="salario" name="salario" placeholder="Salário (ex: 2500.00)" step="0.01" min="0" required value="<?= htmlspecialchars($funcionarioEditar['salario'] ?? '') ?>" />
            </div>

            <div class="form-row">
                <label for="id_cargo">Cargo:</label>
                <select id="id_cargo" name="id_cargo" required>
                    <option value="">Selecione o cargo</option>
                    <?php foreach ($cargos as $cargo): ?>
                        <option value="<?= htmlspecialchars($cargo['id_cargo']) ?>" <?= (isset($funcionarioEditar['id_cargo']) && $funcionarioEditar['id_cargo'] == $cargo['id_cargo']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cargo['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="Email (será o login)" required value="<?= htmlspecialchars($loginEditar['email'] ?? '') ?>" />
            </div>

            <div class="form-row">
                <label for="descricao">Descrição (Opcional):</label>
                <textarea id="descricao" name="descricao" placeholder="Breve descrição sobre o funcionário"><?= htmlspecialchars($funcionarioEditar['descricao'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <label for="foto_funcionario">Nova Foto do Funcionário (Opcional):</label>
                <input type="file" id="foto_funcionario" name="foto_funcionario" accept="image/*" />
                <?php if ($funcionarioEditar['foto_funcionario']): ?>
                    <div class="current-photo">
                        <p>Foto atual:</p>
                        <img src="data:image/jpeg;base64,<?= base64_encode($funcionarioEditar['foto_funcionario']) ?>" alt="Foto Atual" />
                    </div>
                <?php else: ?>
                    <p class="current-photo">Nenhuma foto atual.</p>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="submit">Salvar Alterações</button>
                <a href="funcionarioADM.php"><button type="button">Cancelar</button></a>
            </div>
        </form>
    </div>
</div>
</body>
</html>