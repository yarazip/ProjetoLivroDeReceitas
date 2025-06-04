<?php
require_once '../BancoDeDados/conexao.php';

// INSERIR RESTAURANTE
if (isset($_POST['adicionar'])) {
    $nome = $_POST['nome'];
    $contato = $_POST['contato'];
    $telefone = $_POST['telefone'];
    $descricao = $_POST['descricao'];

    $sql = "INSERT INTO restaurantes (nome, contato, telefone, descricao) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nome, $contato, $telefone, $descricao]);
}

// EXCLUIR RESTAURANTE
if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    $sql = "DELETE FROM restaurantes WHERE id_restaurante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    header("Location: restauranteADM.php");
    exit;
}

// EDITAR RESTAURANTE (submissão do formulário)
if (isset($_POST['editar'])) {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $contato = $_POST['contato'];
    $telefone = $_POST['telefone'];
    $descricao = $_POST['descricao'];

    $sql = "UPDATE restaurantes SET nome = ?, contato = ?, telefone = ?, descricao = ? WHERE id_restaurante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nome, $contato, $telefone, $descricao, $id]);
    header("Location: restauranteADM.php");
    exit;
}

// BUSCAR DADOS PARA EDIÇÃO
$restauranteEditar = null;
if (isset($_GET['editar'])) {
    $idEditar = $_GET['editar'];
    $sql = "SELECT * FROM restaurantes WHERE id_restaurante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$idEditar]);
    $restauranteEditar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// PESQUISAR
$termo = $_GET['pesquisa'] ?? '';
$sql = "SELECT * FROM restaurantes WHERE nome LIKE ?";
$stmt = $conn->prepare($sql);
$stmt->execute(["%$termo%"]);
$restaurantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/restaurante.css">
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
    <title>Restaurantes Administração</title>
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

        <div class="search-bar">
            <form method="get">
                <input type="text" name="pesquisa" placeholder="Pesquisar Restaurante..." value="<?= htmlspecialchars($termo) ?>">
                <button type="submit">Pesquisar</button>
            </form>
        </div>

        <div class="insert-bar">
            <h2><?= $restauranteEditar ? 'Editar Restaurante' : 'Adicionar Restaurante' ?></h2>
            <form method="post">
                <?php if ($restauranteEditar): ?>
                    <input type="hidden" name="id" value="<?= $restauranteEditar['id_restaurante'] ?>">
                <?php endif; ?>
<div class="compact-form">
    <input type="text" name="nome" placeholder="Nome do Restaurante" required 
           value="<?= $restauranteEditar['nome'] ?? '' ?>" class="compact-input">
    
    <input type="text" name="contato" placeholder="Nome do Contato" required 
           value="<?= $restauranteEditar['contato'] ?? '' ?>" class="compact-input">
    
    <input type="tel" name="telefone" placeholder="Telefone" required 
           value="<?= $restauranteEditar['telefone'] ?? '' ?>" class="compact-input">
    
    <textarea name="descricao" placeholder="Descrição do Restaurante" 
              class="compact-input compact-textarea"><?= $restauranteEditar['descricao'] ?? '' ?></textarea>
</div>
                <?php if ($restauranteEditar): ?>
                    <button type="submit" name="editar">Salvar Alterações</button>
                    <a href="restauranteADM.php">Cancelar</a>
                <?php else: ?>
                    <button type="submit" name="adicionar">Adicionar</button>
                <?php endif; ?>
            </form>
        </div>

        <table border="1" cellpadding="5">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Contato</th>
                    <th>Telefone</th>
                    <th>Descrição</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($restaurantes as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['nome']) ?></td>
                        <td><?= htmlspecialchars($r['contato']) ?></td>
                        <td><?= htmlspecialchars($r['telefone']) ?></td>
                        <td><?= htmlspecialchars($r['descricao']) ?></td>
                        <td>
                            <a href="?editar=<?= $r['id_restaurante'] ?>">Editar</a> |
                            <a href="?excluir=<?= $r['id_restaurante'] ?>" onclick="return confirm('Deseja excluir este restaurante?')">Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
