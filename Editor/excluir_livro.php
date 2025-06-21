<?php
session_start();
require_once '../BancoDeDados/conexao.php';
require_once '../dompdf/autoload.inc.php';

// Pega o ID da URL. Se não houver, redireciona.
$id_livro = $_GET['id'] ?? null;

if (!$id_livro) {
    $_SESSION['message'] = "Livro não encontrado.";
    $_SESSION['message_type'] = "error";
    header("Location: livrosEditor.php");
    exit;
}

// Buscar dados do livro
$stmt = $conn->prepare("SELECT * FROM livros WHERE id_livro = ?");
$stmt->execute([$id_livro]);
$livro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$livro) {
    $_SESSION['message'] = "Livro não encontrado.";
    $_SESSION['message_type'] = "error";
    header("Location: livrosEditor.php");
    exit;
}

// Buscar receitas associadas
$stmt = $conn->prepare("SELECT nome_receita FROM livro_receita WHERE id_livro = ?");
$stmt->execute([$id_livro]);
$receitas_livro = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Excluir livro ao confirmar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_exclusao'])) {
    try {
        $conn->beginTransaction();

        // Remove receitas associadas
        $stmt = $conn->prepare("DELETE FROM livro_receita WHERE id_livro = ?");
        $stmt->execute([$id_livro]);

        // Remove livro principal
        $stmt = $conn->prepare("DELETE FROM livros WHERE id_livro = ?");
        $stmt->execute([$id_livro]);

        $conn->commit();

        $_SESSION['message'] = "Livro excluído com sucesso.";
        $_SESSION['message_type'] = "success";
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['message'] = "Erro ao excluir o livro: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }

    header("Location: livrosEditor.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Excluir Livro</title>
        <link rel="stylesheet" href="../styles/excluirLIVRO.css">
            <link rel="stylesheet" href="../styles/livrosEDITOR.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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
    <h1>Excluir Livro</h1>

    <form method="POST">
        <p><strong>Título:</strong> <?= htmlspecialchars($livro['titulo']) ?></p>
        <p><strong>ISBN:</strong> <?= htmlspecialchars($livro['isbn']) ?></p>
        <p><strong>Descrição:</strong> <?= htmlspecialchars($livro['descricao']) ?></p>

        <p><strong>Receitas associadas:</strong></p>
        <ul>
            <?php foreach ($receitas_livro as $receita): ?>
                <li><?= htmlspecialchars($receita) ?></li>
            <?php endforeach; ?>
        </ul>

        <button type="submit" name="confirmar_exclusao" style="background-color: red; color: white;">Confirmar Exclusão</button>
        <a href="livrosEditor.php">Cancelar</a>
    </form>
</body>
</html>
