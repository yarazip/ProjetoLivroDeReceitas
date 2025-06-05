<?php
require_once '../BancoDeDados/conexao.php';

// INSERIR CARGO
if (isset($_POST['adicionar'])) {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    
    $sql = "INSERT INTO cargos (nome, descricao) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nome, $descricao]);
}

// EXCLUIR CARGO
if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    $sql = "DELETE FROM cargos WHERE id_cargo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    header("Location: cargosADM.php");
    exit;
}

// BUSCA CARGO PARA EDIÇÃO
$cargoParaEditar = null;
if (isset($_GET['editar'])) {
    $idEditar = $_GET['editar'];
    $sql = "SELECT * FROM cargos WHERE id_cargo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$idEditar]);
    $cargoParaEditar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// EDITAR CARGO
if (isset($_POST['editar'])) {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];

    $sql = "UPDATE cargos SET nome = ?, descricao = ? WHERE id_cargo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nome, $descricao, $id]);
    header("Location: cargosADM.php");
    exit;
}

// PESQUISA
$termo = $_GET['pesquisa'] ?? '';
$sql = "SELECT * FROM cargos WHERE nome LIKE ?";
$stmt = $conn->prepare($sql);
$stmt->execute(["%$termo%"]);
$cargos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../../styles/cargos.css">
    <title>Cargos Administração</title>
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
            <form method="get" action="">
                <input type="text" name="pesquisa" placeholder="Pesquisar cargo..." value="<?= htmlspecialchars($termo) ?>">
                <button type="submit">Pesquisar</button>
            </form>
        </div>

        <div class="insert-bar">
            <?php if ($cargoParaEditar): ?>
                <!-- FORMULÁRIO DE EDIÇÃO -->
                <h2>Editar Cargo</h2>
                <form method="post" action="">
                    <input type="hidden" name="id" value="<?= $cargoParaEditar['id_cargo'] ?>">
                    <input type="text" name="nome" value="<?= htmlspecialchars($cargoParaEditar['nome']) ?>" required>
                    <input type="text" name="descricao" value="<?= htmlspecialchars($cargoParaEditar['descricao']) ?>" required>
                    <button type="submit" name="editar">Salvar Alterações</button>
                    <a href="cargosADM.php">Cancelar</a>
                </form>
            <?php else: ?>
                <!-- FORMULÁRIO DE INSERÇÃO -->
                <form method="post" action="">
                    <input type="text" name="nome" placeholder="Nome do Cargo" required>
                    <input type="text" name="descricao" placeholder="Descrição do cargo" required>
                    <button type="submit" name="adicionar">Adicionar</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="lista-cargos">
            <h2>Cargos Cadastrados</h2>
            <?php foreach ($cargos as $cargo): ?>
                <div class="cargo-item" style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
                    <p><strong>ID:</strong> <?= htmlspecialchars($cargo['id_cargo']) ?></p>
                    <p><strong>Nome:</strong> <?= htmlspecialchars($cargo['nome']) ?></p>
                    <p><strong>Descrição:</strong> <?= htmlspecialchars($cargo['descricao']) ?></p>
                    
                    <a href="?editar=<?= $cargo['id_cargo'] ?>" style="margin-right:10px;">Editar</a>
                    <a href="?excluir=<?= $cargo['id_cargo'] ?>" onclick="return confirm('Deseja realmente excluir este cargo?')">Excluir</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
