<?php
// Conexão com o banco
require_once '../BancoDeDados/conexao.php';

// INSERIR medida
if (isset($_POST['adicionar'])) {
    $descricao = $_POST['descricao'];
    $medida = $_POST['medida'];

    $sql = "INSERT INTO medidas (descricao, medida) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$descricao, $medida]);
}

// INICIAR EDIÇÃO
$editar_id = $_GET['editar'] ?? null;
$medida_editando = null;

if ($editar_id) {
    $stmt = $conn->prepare("SELECT * FROM medidas WHERE id_medida = ?");
    $stmt->execute([$editar_id]);
    $medida_editando = $stmt->fetch(PDO::FETCH_ASSOC);
}

// SALVAR EDIÇÃO
if (isset($_POST['salvar_edicao'])) {
    $id = $_POST['id_medida'];
    $descricao = $_POST['descricao'];
    $medida = $_POST['medida'];

    $sql = "UPDATE medidas SET descricao = ?, medida = ? WHERE id_medida = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$descricao, $medida, $id]);

    header("Location: medidasChef.php");
    exit;
}

// EXCLUIR medida
if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    $sql = "DELETE FROM medidas WHERE id_medida = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    header("Location: medidasChef.php");
    exit;
}

// PESQUISA
$termo = $_GET['pesquisa'] ?? '';
$sql = "SELECT * FROM medidas WHERE descricao LIKE ?";
$stmt = $conn->prepare($sql);
$stmt->execute(["%$termo%"]);
$medidas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Medidas do Cozinheiro</title>
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
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

    <!-- PESQUISA -->
    <div class="search-bar">
        <form method="GET">
            <input type="text" name="pesquisa" placeholder="Pesquisar medida..." value="<?= htmlspecialchars($termo) ?>">
            <button type="submit">Pesquisar</button>
        </form>
    </div>

    <!-- INSERIR ou EDITAR -->
    <div class="insert-bar">
        <form method="POST">
            <?php if ($medida_editando): ?>
                <input type="hidden" name="id_medida" value="<?= $medida_editando['id_medida'] ?>">
                <input type="text" name="descricao" value="<?= htmlspecialchars($medida_editando['descricao']) ?>" required>
                <input type="text" name="medida" value="<?= htmlspecialchars($medida_editando['medida']) ?>" required>
                <button type="submit" name="salvar_edicao">Salvar</button>
                <a href="medidasChef.php"><button type="button">Cancelar</button></a>
            <?php else: ?>
                <input type="text" name="descricao" placeholder="Descrição" required>
                <input type="text" name="medida" placeholder="Medida" required>
                <button type="submit" name="adicionar">Adicionar</button>
            <?php endif; ?>
        </form>
    </div>

    <!-- LISTAGEM -->
    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
        <tr>
            <th>ID</th>
            <th>Descrição</th>
            <th>Medida</th>
            <th>Ações</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($medidas): ?>
            <?php foreach ($medidas as $m): ?>
                <tr>
                    <td><?= $m['id_medida'] ?></td>
                    <td><?= htmlspecialchars($m['descricao']) ?></td>
                    <td><?= htmlspecialchars($m['medida']) ?></td>
                    <td>
                        <a href="?editar=<?= $m['id_medida'] ?>"><button>Editar</button></a>
                        <a href="?excluir=<?= $m['id_medida'] ?>" onclick="return confirm('Deseja excluir esta medida?');">
                            <button>Excluir</button>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4">Nenhuma medida encontrada.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
