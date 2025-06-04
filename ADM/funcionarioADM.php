<?php
require_once '../BancoDeDados/conexao.php';

//para ver com mais detalhe os erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ADICIONAR FUNCIONÁRIO
if (isset($_POST['adicionar'])) {
    $cpf = $_POST['cpf'];
    $nome = $_POST['nome'];
    $data_admissao = $_POST['data_admissao'];
    $salario = $_POST['salario'];
    $descricao = $_POST['descricao'];
    $id_cargo = $_POST['id_cargo'];
    //adicionei
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // Upload da foto em base64 (opcional)
    $foto = null;
    if (!empty($_FILES['foto_funcionario']['tmp_name'])) {
        $foto = file_get_contents($_FILES['foto_funcionario']['tmp_name']);
    }

    // Inserir na tabela funcionarios
    $sql = "INSERT INTO funcionarios (CPF, nome, salario, data_admissao, foto_funcionario, descricao, id_cargo)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$cpf, $nome, $salario, $data_admissao, $foto, $descricao, $id_cargo]);

    $id_funcionario = $conn->lastInsertId();

    //adicionei
    // Inserir na tabela login
    $sqlLogin = "INSERT INTO logins (email, senha, id_funcionario) VALUES (?, ?, ?)";
    $stmtLogin = $conn->prepare($sqlLogin);
    $stmtLogin->execute([$email, $senha, $id_funcionario]);

    header("Location: funcionarioADM.php");
    exit;
}

// EDITAR FUNCIONÁRIO
if (isset($_POST['editar'])) {
    $id = $_POST['id'];
    $cpf = $_POST['cpf'];
    $nome = $_POST['nome'];
    $data_admissao = $_POST['data_admissao'];
    $salario = $_POST['salario'];
    $descricao = $_POST['descricao'];
    $id_cargo = $_POST['id_cargo'];
    //adicionei
    $email = $_POST['email'];

    if (!empty($_FILES['foto_funcionario']['tmp_name'])) {
        $foto = file_get_contents($_FILES['foto_funcionario']['tmp_name']);
        $sql = "UPDATE funcionarios SET CPF = ?, nome = ?, salario = ?, data_admissao = ?, foto_funcionario = ?, descricao = ?, id_cargo = ? WHERE id_funcionario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$cpf, $nome, $salario, $data_admissao, $foto, $descricao, $id_cargo, $id]);
    } else {
        $sql = "UPDATE funcionarios SET CPF = ?, nome = ?, salario = ?, data_admissao = ?, descricao = ?, id_cargo = ? WHERE id_funcionario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$cpf, $nome, $salario, $data_admissao, $descricao, $id_cargo, $id]);
    }

    //adicionei
    // Verifica se já existe login
    $sqlCheck = "SELECT COUNT(*) FROM logins WHERE id_funcionario = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->execute([$id]);
    $exists = $stmtCheck->fetchColumn();

    //adicionei
    if ($exists) {
        $sqlEmail = "UPDATE logins SET email = ? WHERE id_funcionario = ?";
        $stmtEmail = $conn->prepare($sqlEmail);
        $stmtEmail->execute([$email, $id]);

        //adicionei
    } else {
        // Se não existe, cria
        $sqlLogin = "INSERT INTO logins (email, id_funcionario) VALUES (?, ?)";
        $stmtLogin = $conn->prepare($sqlLogin);
        $stmtLogin->execute([$email, $id]);
    }

    header("Location: funcionarioADM.php");
    exit;
}

if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];

    // Excluir registros relacionados em degustacoes
    $stmt = $conn->prepare("DELETE FROM degustacoes WHERE id_funcionario = ?");
    $stmt->execute([$id]);

    // Excluir registros relacionados em historico_restaurante
    $stmt = $conn->prepare("DELETE FROM historico_restaurante WHERE id_funcionario = ?");
    $stmt->execute([$id]);

    // Excluir login do funcionário
    $stmt = $conn->prepare("DELETE FROM logins WHERE id_funcionario = ?");
    $stmt->execute([$id]);

    // Agora exclui o funcionário
    $stmt = $conn->prepare("DELETE FROM funcionarios WHERE id_funcionario = ?");
    $stmt->execute([$id]);

    header("Location: funcionarioADM.php");
    exit;
}



// BUSCAR FUNCIONÁRIO PARA EDIÇÃO
$funcionarioEditar = null;
$loginEditar = null;

if (isset($_GET['editar'])) {
    $idEditar = $_GET['editar'];
    $sql = "SELECT * FROM funcionarios WHERE id_funcionario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$idEditar]);
    $funcionarioEditar = $stmt->fetch(PDO::FETCH_ASSOC);

    $sqlLogin = "SELECT * FROM logins WHERE id_funcionario = ?";
    $stmtLogin = $conn->prepare($sqlLogin);
    $stmtLogin->execute([$idEditar]);
    $loginEditar = $stmtLogin->fetch(PDO::FETCH_ASSOC);
}
//adicionei
// PESQUISAR FUNCIONÁRIO
$termo = trim($_GET['pesquisa'] ?? '');

$sql = "SELECT f.*, c.nome AS nome_cargo, l.email 
        FROM funcionarios f
        JOIN cargos c ON f.id_cargo = c.id_cargo
        LEFT JOIN logins l ON f.id_funcionario = l.id_funcionario
        WHERE f.nome LIKE ?";
$stmt = $conn->prepare($sql);
$stmt->execute(["%$termo%"]);
$funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);


$cargos = $conn->query("SELECT id_cargo, nome FROM cargos")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="../../styles/funcionario.css" />
    <title>Funcionários Administração</title>
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

    <div class="insert-bar">
    <h2><?= $funcionarioEditar ? 'Editar Funcionário' : 'Adicionar Funcionário' ?></h2>
    <form method="post" enctype="multipart/form-data">
        <?php if ($funcionarioEditar): ?>
            <input type="hidden" name="id" value="<?= $funcionarioEditar['id_funcionario'] ?>" />
        <?php endif; ?>

        <!-- CPF -->
        <div class="form-row">
            <input type="text" name="cpf" placeholder="CPF" required value="<?= $funcionarioEditar['CPF'] ?? '' ?>" />
        </div>

        <!-- Nome -->
        <div class="form-row">
            <input type="text" name="nome" placeholder="Nome do funcionário" required value="<?= $funcionarioEditar['nome'] ?? '' ?>" />
        </div>

        <!-- Data de Admissão -->
        <div class="form-row">
            <input type="date" name="data_admissao" placeholder="Data de Admissão" required value="<?= $funcionarioEditar['data_admissao'] ?? '' ?>" />
        </div>

        <!-- Salário -->
        <div class="form-row">
            <input type="text" step="0.01" name="salario" placeholder="Salário" required value="<?= $funcionarioEditar['salario'] ?? '' ?>" />
        </div>

        <!-- Foto -->
        <div class="form-row">
            <input type="file" name="foto_funcionario" accept="image/*" />
        </div>

        <!-- Cargo -->
        <div class="form-row">
            <select name="id_cargo" required>
                <option value="">Selecione o cargo</option>
                <?php foreach ($cargos as $cargo): ?>
                    <option value="<?= $cargo['id_cargo'] ?>" <?= (isset($funcionarioEditar['id_cargo']) && $funcionarioEditar['id_cargo'] == $cargo['id_cargo']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cargo['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Descrição -->
        <div class="form-row">
            <textarea name="descricao" placeholder="Descrição"><?= $funcionarioEditar['descricao'] ?? '' ?></textarea>
        </div>
        
        <!-- Email -->
        <input type="email" name="email" placeholder="Email" required value="<?= $loginEditar['email'] ?? '' ?>" />

        <!-- Senha -->
        <?php if (!$funcionarioEditar): ?>
            <input type="password" name="senha" placeholder="Senha" required />
        <?php endif; ?>

        <!-- Actions -->
        <div class="form-actions">
            <?php if ($funcionarioEditar): ?>
                <button type="submit" name="editar">Salvar Alterações</button> 
                <a href="funcionarioADM.php">Cancelar</a>
            <?php else: ?>
                <button type="submit" name="adicionar">Adicionar</button>
            <?php endif; ?>
        </div>
    </form>
</div>
    <table border="1" cellpadding="5">
        <thead>
            <tr>
                <th>ID</th>
                <th>CPF</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Data de Admissão</th>
                <th>Salário</th>
                <th>Cargo</th>
                <th>Descrição</th>
                <th>Foto</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($funcionarios as $f): ?>
                <tr>
                    <td><?= $f['id_funcionario'] ?></td>
                    <td><?= isset($f['cpf']) ? htmlspecialchars($f['cpf']) : '' ?></td>
                    <td><?= htmlspecialchars($f['nome']) ?></td>
                    <td><?= htmlspecialchars($f['email'] ?? 'Sem email') ?></td>
                    <td><?= htmlspecialchars($f['data_admissao']) ?></td>
                    <td>R$ <?= number_format($f['salario'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($f['nome_cargo']) ?></td>
                    <td><?= htmlspecialchars($f['descricao']) ?></td>
                    <td>
                        <?php if ($f['foto_funcionario']): ?>
                            <img src="data:image/jpeg;base64,<?= base64_encode($f['foto_funcionario']) ?>" alt="Foto" width="60" />
                        <?php else: ?>
                            Sem foto
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?editar=<?= $f['id_funcionario'] ?>">Editar</a> 
                        
                        <a href="?excluir=<?= $f['id_funcionario'] ?>" onclick="return confirm('Deseja excluir este funcionário?')">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
