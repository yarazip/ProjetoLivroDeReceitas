<?php
session_start();
require_once '../BancoDeDados/conexao.php';

if (!isset($_GET['id'])) {
    header("Location: livrosEditor.php");
    exit;
}

$id_livro = $_GET['id'];

// Buscar o título do livro para mostrar na confirmação
$stmt = $conn->prepare("SELECT titulo FROM livros WHERE id_livro = ?");
$stmt->execute([$id_livro]);
$livro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$livro) {
    die("Livro não encontrado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("DELETE FROM livro_receita WHERE id_livro = ?");
        $stmt->execute([$id_livro]);

        $stmt = $conn->prepare("DELETE FROM livros WHERE id_livro = ?");
        $stmt->execute([$id_livro]);

        $conn->commit();

        header("Location: livrosEditor.php");
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        die("Erro ao excluir livro: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Confirmar Exclusão</title>
    <link rel="stylesheet" href="../styles/excluirLIVRO.css">
</head>
<body>
    <h1>Confirmar Exclusão</h1>
    <p>Tem certeza que deseja excluir o livro: <strong><?= htmlspecialchars($livro['titulo']) ?></strong>?</p>

    <form method="POST" action="">
        <button type="submit" name="confirmar">Sim, Excluir</button>
        <a href="livrosEditor.php">Cancelar</a>
    </form>
</body>
</html>
