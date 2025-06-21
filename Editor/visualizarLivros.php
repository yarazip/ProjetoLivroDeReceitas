<?php
session_start();
require_once './../BancoDeDados/conexao.php';

if (!isset($_GET['id'])) {
    header("Location: livrosEditor.php");
    exit;
}

$id_livro = $_GET['id'];

// Buscar dados do livro
$stmt = $conn->prepare("SELECT * FROM livros WHERE id_livro = ?");
$stmt->execute([$id_livro]);
$livro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$livro) {
    die("Livro não encontrado.");
}

// Buscar receitas associadas ao livro
$stmt = $conn->prepare("SELECT nome_receita FROM livro_receita WHERE id_livro = ?");
$stmt->execute([$id_livro]);
$receitas = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Visualizar Livro</title>
    <link rel="stylesheet" href="../styles/visualizarLIVROS.css">
    
</head>
<body>
     <div class="container">
        <div class="menu">
            <h1 class="logo">Código de Sabores</h1>
            <nav>
                <a href="livrosEditor.php">Livros</a>
                <a href="gerarPDF.php">Gerar PDF</a>
            </nav>
        </div>

    <div class="container">
        <h1><?= htmlspecialchars($livro['titulo']) ?></h1>
        <p><strong>ISBN:</strong> <?= htmlspecialchars($livro['isbn']) ?></p>
        <p><strong>Descrição:</strong></p>
        <p><?= nl2br(htmlspecialchars($livro['descricao'])) ?></p>

        <p><strong>Receitas associadas:</strong></p>
        <?php if (count($receitas) > 0): ?>
            <ul>
                <?php foreach ($receitas as $receita): ?>
                    <li><?= htmlspecialchars($receita) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Nenhuma receita associada.</p>
        <?php endif; ?>

        <button onclick="window.location.href='livrosEditor.php'">Voltar</button>
    </div>
</body>
</html>
