<?php
require_once '../BancoDeDados/conexao.php';
require_once '../dompdf/autoload.inc.php';

// Pega o ID da URL. Se não houver ou for inválido, redireciona.
$id_livro = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_livro) {
    set_flash_message("ID do livro é inválido.", 'error');
    header("Location: visualizarLivros.php");
    exit;
}

// Busca os dados do livro para mostrar na confirmação
$stmt = $conn->prepare("SELECT id_livro, titulo, isbn FROM livros WHERE id_livro = ?");
$stmt->execute([$id_livro]);
$livro = $stmt->fetch(PDO::FETCH_ASSOC);

// Se o livro com o ID fornecido não for encontrado
if (!$livro) {
    set_flash_message("Livro não encontrado.", 'error');
    header("Location: visualizarLivros.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Confirmar Exclusão</title>
    <link rel="stylesheet" href="../styles/func.css"> <style>
        .confirmation-box {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
            text-align: center;
        }
        .confirmation-box h2 {
            color: #c0392b; /* Vermelho */
        }
        .book-details {
            background-color: #fff;
            padding: 15px;
            border: 1px solid #eee;
            margin: 20px 0;
            text-align: left;
        }
        .actions a, .actions button {
            display: inline-block;
            padding: 12px 25px;
            margin: 10px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .actions .confirm-btn {
            background-color: #c0392b; /* Vermelho */
        }
        .actions .cancel-btn {
            background-color: #7f8c8d; /* Cinza */
        }
    </style>
</head>
<body>
    <div class="confirmation-box">
        <h2>Confirmar Exclusão</h2>
        <p>Você tem certeza que deseja excluir permanentemente o livro abaixo? <strong>Esta ação não pode ser desfeita.</strong></p>

        <div class="book-details">
            <p><strong>ID:</strong> <?= htmlspecialchars($livro['id_livro']) ?></p>
            <p><strong>Título:</strong> <?= htmlspecialchars($livro['titulo']) ?></p>
            <p><strong>ISBN:</strong> <?= htmlspecialchars($livro['isbn']) ?></p>
        </div>

        <div class="actions">
            <form action="excluir_livro.php" method="POST" style="display: inline;">
                <input type="hidden" name="id_livro" value="<?= $livro['id_livro'] ?>">
                <button type="submit" class="confirm-btn">Sim, Excluir</button>
            </form>
            
            <a href="visualizarLivros.php" class="cancel-btn">Não, Cancelar</a>
        </div>
    </div>
</body>
</html>