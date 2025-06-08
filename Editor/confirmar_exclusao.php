<?php
session_start();
require_once '../BancoDeDados/conexao.php';

if (!isset($_SESSION['id_login']) || $_SESSION['cargo'] !== 'editor') {
    $_SESSION['message'] = "Você não tem permissão para realizar esta ação.";
    $_SESSION['message_type'] = "error";
    header("Location: ../../LoginSenha/login.php");
    exit;
}

$id_livro = $_GET['id'] ?? null;

if (!$id_livro) {
    $_SESSION['message'] = "ID do livro não especificado.";
    $_SESSION['message_type'] = "error";
    header("Location: livrosEditor.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM livros WHERE id_livro = ?");
$stmt->execute([$id_livro]);
$livro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$livro) {
    $_SESSION['message'] = "Livro não encontrado.";
    $_SESSION['message_type'] = "error";
    header("Location: livrosEditor.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8" /><title>Excluir Livro</title></head>
<body>
<h1>Excluir Livro</h1>
<p>Você tem certeza que deseja excluir o livro: <strong><?= htmlspecialchars($livro['titulo']) ?></strong>?</p>
<p><strong>ISBN:</strong> <?= htmlspecialchars($livro['isbn']) ?></p>
<p><strong>Descrição:</strong> <?= nl2br(htmlspecialchars($livro['descricao'])) ?></p>
<p><strong>ID do Funcionário:</strong> <?= htmlspecialchars($livro['id_funcionario']) ?></p>

<form method="GET" action="excluir_livro.php">
    <input type="hidden" name="id" value="<?= $id_livro ?>" />
    <input type="hidden" name="confirmar" value="sim" />
    <button type="submit">Sim, excluir</button>
    <a href="livrosEditor.php">Cancelar</a>
</form>
</body>
</html>
