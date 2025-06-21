<?php
session_start();
require_once '../BancoDeDados/conexao.php';
require_once '../dompdf/autoload.inc.php';

// Pega o ID da URL. Se não houver, redireciona.
$id_livro = $_GET['id'] ?? null;

if (!$id_livro) {
    header("Location: ../livrosEditor.php");
    session_start();
    require_once '../BancoDeDados/conexao.php';
}

if (!isset($_GET['id'])) {
    header("Location: livrosEditor.php");
    exit;
}

$id_livro = $_GET['id'];

// Buscar dados do livro para edição
$stmt = $conn->prepare("SELECT * FROM livros WHERE id_livro = ?");
$stmt->execute([$id_livro]);
$livro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$livro) {
    die("Livro não encontrado.");
}

// Buscar receitas associadas ao livro
$stmt = $conn->prepare("SELECT nome_receita FROM livro_receita WHERE id_livro = ?");
$stmt->execute([$id_livro]);
$receitas_livro = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Buscar todas as receitas disponíveis para seleção
$sql = "SELECT nome_receita FROM receitas";
$receitasDisponiveis = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar'])) {
    $titulo = $_POST['titulo'];
    $isbn = $_POST['isbn'];
    $descricao = $_POST['descricao'];
    $id_funcionario = $_SESSION['id_funcionario'];
    $receitas = $_POST['receitas'] ?? [];

    try {
        $conn->beginTransaction();

        $sql = "UPDATE livros SET titulo = ?, isbn = ?, descricao = ?, id_funcionario = ? WHERE id_livro = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$titulo, $isbn, $descricao, $id_funcionario, $id_livro]);

        $stmt = $conn->prepare("DELETE FROM livro_receita WHERE id_livro = ?");
        $stmt->execute([$id_livro]);

        $stmt = $conn->prepare("INSERT INTO livro_receita (id_livro, nome_receita) VALUES (?, ?)");
        foreach ($receitas as $nome_receita) {
            $stmt->execute([$id_livro, $nome_receita]);
        }

        $conn->commit();

        header("Location: livrosEditor.php");
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        die("Erro ao atualizar livro: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Editar Livro</title>
    <link rel="stylesheet" href="../styles/livrosEDITOR.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="../styles/edicaoLIVRO.css">
</head>

<body>
    <div class="container">
        <div class="menu">
            <div class="menu-content">
                <h1 class="logo">Código de Sabores</h1>
                <nav>
                    <a href="livrosEditor.php" class="active">Livros</a>
                    <!-- <a href="listar_receitas_editor.php">Receitas</a> não funciona -->
                    <div class="user-info">
                        <i class="fas fa-user"></i>
                        <span><?= htmlspecialchars($_SESSION['nome_funcionario'] ?? 'Desconhecido') ?></span>
                    </div>

                </nav>
            </div>
        </div>

        <h1>Editar Livro</h1>
        <form method="POST" action="">
            <input type="text" name="titulo" placeholder="Título" value="<?= htmlspecialchars($livro['titulo']) ?>" required>
            <input type="number" name="isbn" placeholder="ISBN" value="<?= htmlspecialchars($livro['isbn']) ?>" required>
            <input type="text" name="descricao" placeholder="Descrição" value="<?= htmlspecialchars($livro['descricao']) ?>" required>

          <label>Receitas:</label>
<div class="checkbox-list">
    <?php foreach ($receitasDisponiveis as $receita): ?>
        <label class="checkbox-item">
            <input type="checkbox" name="receitas[]" value="<?= htmlspecialchars($receita['nome_receita']) ?>"
                <?= in_array($receita['nome_receita'], $receitas_livro) ? 'checked' : '' ?>>
            <?= htmlspecialchars($receita['nome_receita']) ?>
        </label>
    <?php endforeach; ?>
</div>

            </select>
            <button type="submit" name="atualizar">Atualizar</button>
        </form>
        <a class="voltar" href="livrosEditor.php">Voltar</a>
</body>

</html>