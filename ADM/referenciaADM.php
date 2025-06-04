<?php
require_once '../BancoDeDados/conexao.php';

$editando = false;
$id_func_editar = $id_rest_editar = $data_inicio_editar = $data_fim_editar = $descricao_editar = '';

// INSERIR
if (isset($_POST['adicionar'])) {
    $id_funcionario = $_POST['id_funcionario'];
    $id_restaurante = $_POST['id_restaurante'];
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'] ?? null;
    $descricao = $_POST['descricao'] ?? null;

    try {
        $sql = "INSERT INTO historico_restaurante (id_funcionario, id_restaurante, data_inicio, data_fim, descricao) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id_funcionario, $id_restaurante, $data_inicio, $data_fim, $descricao]);
    } catch (PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
}

// EXCLUIR
if (isset($_GET['excluir_func']) && isset($_GET['excluir_rest'])) {
    $id_func = $_GET['excluir_func'];
    $id_rest = $_GET['excluir_rest'];
    $sql = "DELETE FROM historico_restaurante WHERE id_funcionario = ? AND id_restaurante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_func, $id_rest]);
    header("Location: referenciaADM.php");
    exit;
}

// EDITAR (CARREGAR DADOS)
if (isset($_GET['editar_func']) && isset($_GET['editar_rest'])) {
    $editando = true;
    $id_func_editar = $_GET['editar_func'];
    $id_rest_editar = $_GET['editar_rest'];

    $sql = "SELECT * FROM historico_restaurante WHERE id_funcionario = ? AND id_restaurante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_func_editar, $id_rest_editar]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($registro) {
        $data_inicio_editar = $registro['data_inicio'];
        $data_fim_editar = $registro['data_fim'];
        $descricao_editar = $registro['descricao'];
    }
}

// ATUALIZAR
if (isset($_POST['atualizar'])) {
    $id_funcionario = $_POST['id_funcionario'];
    $id_restaurante = $_POST['id_restaurante'];
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'] ?? null;
    $descricao = $_POST['descricao'] ?? null;

    $sql = "UPDATE historico_restaurante SET data_inicio = ?, data_fim = ?, descricao = ? 
            WHERE id_funcionario = ? AND id_restaurante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$data_inicio, $data_fim, $descricao, $id_funcionario, $id_restaurante]);
    header("Location: referenciaADM.php");
    exit;
}

// LISTAR
$sql = "SELECT * FROM historico_restaurante";
$stmt = $conn->query($sql);
$historicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Histórico Restaurante ADM</title>
    <link rel="stylesheet" href="../styles/referencia.css">
        <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">

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


    <h2>Referência do Funcionário</h2>

    <form method="post">
        <input type="number" name="id_funcionario" placeholder="ID Funcionário" required
               value="<?= htmlspecialchars($id_func_editar) ?>" <?= $editando ? 'readonly' : '' ?>>
        <input type="number" name="id_restaurante" placeholder="ID Restaurante" required
               value="<?= htmlspecialchars($id_rest_editar) ?>" <?= $editando ? 'readonly' : '' ?>>
        <input type="date" name="data_inicio" required value="<?= htmlspecialchars($data_inicio_editar) ?>">
        <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim_editar) ?>">
        <textarea name="descricao" placeholder="Descrição"><?= htmlspecialchars($descricao_editar) ?></textarea>
        <?php if ($editando): ?>
            <button type="submit" name="atualizar">Atualizar</button>
        <?php else: ?>
            <button type="submit" name="adicionar">Adicionar</button>
        <?php endif; ?>
    </form>

    <table border="1" cellpadding="5">
        <thead>
        <tr>
            <th>ID Funcionário</th>
            <th>ID Restaurante</th>
            <th>Data Início</th>
            <th>Data Fim</th>
            <th>Descrição</th>
            <th>Ações</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($historicos as $h): ?>
            <tr>
                <td><?= htmlspecialchars($h['id_funcionario']) ?></td>
                <td><?= htmlspecialchars($h['id_restaurante']) ?></td>
                <td><?= htmlspecialchars($h['data_inicio']) ?></td>
                <td><?= htmlspecialchars($h['data_fim']) ?></td>
                <td><?= htmlspecialchars($h['descricao']) ?></td>
                <td>
                    <a href="?editar_func=<?= $h['id_funcionario'] ?>&editar_rest=<?= $h['id_restaurante'] ?>">Editar</a> |
                    <a href="?excluir_func=<?= $h['id_funcionario'] ?>&excluir_rest=<?= $h['id_restaurante'] ?>"
                       onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
