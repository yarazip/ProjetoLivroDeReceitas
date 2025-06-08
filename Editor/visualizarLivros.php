<?php
session_start();
require_once '../BancoDeDados/conexao.php';

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
    <link rel="stylesheet" href="../styles/func.css">
    <style>
        /* Estilo simples para visualização limpa */
        body {
            font-family: Arial, sans-serif;
            margin: 2rem;
            background-color: #f9f9f9;
        }
        .container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            max-width: 600px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            margin-bottom: 1rem;
            color: #333;
        }
        p {
            font-size: 1.1rem;
            margin: 0.5rem 0;
        }
        ul {
            margin-left: 1.5rem;
            margin-bottom: 1.5rem;
        }
        button {
            padding: 0.6rem 1.2rem;
            font-size: 1rem;
            background-color: #0066cc;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #004d99;
        }
    </style>
</head>
<body>
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
