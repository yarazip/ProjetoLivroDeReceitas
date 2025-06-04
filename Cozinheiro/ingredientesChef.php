<?php
require_once '../BancoDeDados/conexao.php';

// INSERIR INGREDIENTE
if (isset($_POST['adicionar'])) {
    $nome_ingrediente = $_POST['nome_ingrediente'];
    $descricao = $_POST['descricao'];
    
    $sql = "INSERT INTO ingredientes (nome, descricao) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nome_ingrediente, $descricao]);
}

// EXCLUIR INGREDIENTE
if (isset($_GET['excluir'])) {
    $id_ingrediente = $_GET['excluir'];
    $sql = "DELETE FROM ingredientes WHERE id_ingrediente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_ingrediente]);
    header("Location: ingredientesChef.php");
    exit;
}

// CARREGAR DADOS PARA EDIÇÃO
$editando = false;
$ingrediente_editar = null;
if (isset($_GET['editar'])) {
    $id = $_GET['editar'];
    $stmt = $conn->prepare("SELECT * FROM ingredientes WHERE id_ingrediente = ?");
    $stmt->execute([$id]);
    $ingrediente_editar = $stmt->fetch(PDO::FETCH_ASSOC);
    $editando = true;
}

// SALVAR EDIÇÃO
if (isset($_POST['editar'])) {
    $id_ingrediente = $_POST['id_ingrediente'];
    $nome_ingrediente = $_POST['nome_ingrediente'];
    $descricao = $_POST['descricao'];

    $sql = "UPDATE ingredientes SET nome = ?, descricao = ? WHERE id_ingrediente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nome_ingrediente, $descricao, $id_ingrediente]);
    header("Location: ingredientesChef.php");
    exit;
}

// PESQUISA
$termo = $_GET['pesquisa'] ?? '';
$sql = "SELECT * FROM ingredientes WHERE nome LIKE ?";
$stmt = $conn->prepare($sql);
$stmt->execute(["%$termo%"]);
$ingredientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Ingredientes do Cozinheiro</title>
    <link rel="stylesheet" href="../styles/func.css">
</head>
<body>
<div class="container">
    <div class="menu">
        <h1 class="logo">Código de Sabores</h1>
        <nav>
            <a href="receitasChef.php">Receitas</a>
            <a href="ingredientesChef.php">Ingredientes</a>
            <a href="medidasChef.php">Medidas</a>
            <a href="categoriaChef.php">Categorias</a>
        </nav>
    </div>

    <div class="search-bar">
        <form method="GET">
            <input type="text" name="pesquisa" placeholder="Pesquisar ingrediente..." value="<?= htmlspecialchars($termo) ?>">
            <button type="submit">Pesquisar</button>
        </form>
    </div>

    <div class="insert-bar">
        <form method="POST">
            <input type="hidden" name="<?= $editando ? 'editar' : 'adicionar' ?>" value="1">
            <?php if ($editando): ?>
                <input type="hidden" name="id_ingrediente" value="<?= $ingrediente_editar['id_ingrediente'] ?>">
            <?php endif; ?>
            <input type="text" name="nome_ingrediente" placeholder="Nome do ingrediente" required value="<?= $ingrediente_editar['nome'] ?? '' ?>">
            <input type="text" name="descricao" placeholder="Descrição" value="<?= $ingrediente_editar['descricao'] ?? '' ?>">
            <button type="submit"><?= $editando ? 'Salvar Edição' : 'Adicionar' ?></button>
            <?php if ($editando): ?>
                <a href="ingredientesChef.php"><button type="button">Cancelar</button></a>
            <?php endif; ?>
        </form>
    </div>

    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Descrição</th>
            <th>Ações</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($ingredientes as $ing): ?>
            <tr>
                <td><?= $ing['id_ingrediente'] ?></td>
                <td><?= htmlspecialchars($ing['nome']) ?></td>
                <td><?= htmlspecialchars($ing['descricao']) ?></td>
                <td>
                    <a href="?editar=<?= $ing['id_ingrediente'] ?>"><button>Editar</button></a>
                    <a href="?excluir=<?= $ing['id_ingrediente'] ?>" onclick="return confirm('Deseja excluir este ingrediente?');">
                        <button>Excluir</button>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($ingredientes)): ?>
            <tr><td colspan="4">Nenhum ingrediente encontrado.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
