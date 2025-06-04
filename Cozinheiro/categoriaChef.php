<?php
require_once '../BancoDeDados/conexao.php';

// INSERIR CATEGORIA
if (isset($_POST['adicionar'])) {
    $nome_categoria = $_POST['nome_categoria'];
    $descricao = $_POST['descricao'];

    $sql = "INSERT INTO categorias (nome_categoria, descricao) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nome_categoria, $descricao]);
}

// EXCLUIR CATEGORIA
if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    $sql = "DELETE FROM categorias WHERE id_categoria = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    header("Location: categoriaChef.php");
    exit;
}

// EDITAR CATEGORIA
if (isset($_POST['editar'])) {
    $id = $_POST['id'];
    $nome_categoria = $_POST['nome_categoria'];
    $descricao = $_POST['descricao'];

    $sql = "UPDATE categorias SET nome_categoria = ?, descricao = ? WHERE id_categoria = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nome_categoria, $descricao, $id]);
    header("Location: categoriaChef.php");
    exit;
}

// BUSCAR DADOS PARA EDIÇÃO
$categoriaEditar = null;
if (isset($_GET['editar'])) {
    $id = $_GET['editar'];
    $sql = "SELECT * FROM categorias WHERE id_categoria = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $categoriaEditar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// PESQUISA
$termo = $_GET['pesquisa'] ?? '';
$sql = "SELECT * FROM categorias WHERE nome_categoria LIKE ?";
$stmt = $conn->prepare($sql);
$stmt->execute(["%$termo%"]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="../styles/func.css">
    <title>Categorias Administração</title>
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

    <!-- PESQUISA -->
    <div class="search-bar">
        <form method="get">
            <input type="text" name="pesquisa" placeholder="Pesquisar categoria..." value="<?= htmlspecialchars($termo) ?>">
            <button type="submit">Pesquisar</button>
        </form>
    </div>

    <!-- INSERIR OU EDITAR -->
    <div class="insert-bar">
        <form method="post">
            <?php if ($categoriaEditar): ?>
                <input type="hidden" name="id" value="<?= $categoriaEditar['id_categoria'] ?>">
                <input type="text" name="nome_categoria" value="<?= htmlspecialchars($categoriaEditar['nome_categoria']) ?>" required>
                <input type="text" name="descricao" value="<?= htmlspecialchars($categoriaEditar['descricao']) ?>" required>
                <button type="submit" name="editar">Salvar Alterações</button>
                <a href="categoriaChef.php"><button type="button">Cancelar</button></a>
            <?php else: ?>
                <input type="text" name="nome_categoria" placeholder="Nome da categoria" required>
                <input type="text" name="descricao" placeholder="Descrição da categoria" required>
                <button type="submit" name="adicionar">Adicionar</button>
            <?php endif; ?>
        </form>
    </div>

    <!-- LISTAGEM -->
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
        <?php if ($categorias): ?>
            <?php foreach ($categorias as $c): ?>
                <tr>
                    <td><?= $c['id_categoria'] ?></td>
                    <td><?= htmlspecialchars($c['nome_categoria']) ?></td>
                    <td><?= htmlspecialchars($c['descricao']) ?></td>
                    <td>
                        <a href="?editar=<?= $c['id_categoria'] ?>"><button>Editar</button></a>
                        <a href="?excluir=<?= $c['id_categoria'] ?>" onclick="return confirm('Deseja excluir esta categoria?');"><button>Excluir</button></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4">Nenhuma categoria encontrada.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
