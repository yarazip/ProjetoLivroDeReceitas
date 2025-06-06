<?php
require_once 'config.php'; // Inclui a configuração, sessão e conexão

// Pega o ID da URL. Se não houver, redireciona.
$id_livro = $_GET['id'] ?? null;
if (!$id_livro) {
    header("Location: listar_livros.php");
    exit;
}

// Processa o formulário quando ele for enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

        $stmt_delete = $conn->prepare("DELETE FROM livro_receita WHERE id_livro = ?");
        $stmt_delete->execute([$id_livro]);

        $stmt_insert = $conn->prepare("INSERT INTO livro_receita (id_livro, nome_receita) VALUES (?, ?)");
        foreach ($receitas as $nome_receita) {
            $stmt_insert->execute([$id_livro, $nome_receita]);
        }

        $conn->commit();
        set_flash_message("Livro atualizado com sucesso!");

    } catch (Exception $e) {
        $conn->rollBack();
        set_flash_message("Erro ao atualizar o livro: " . $e->getMessage(), 'error');
    }

    header("Location: listar_livros.php");
    exit;
}

// Busca os dados atuais do livro para preencher o formulário
$stmt = $conn->prepare("SELECT * FROM livros WHERE id_livro = ?");
$stmt->execute([$id_livro]);
$livro_editar = $stmt->fetch(PDO::FETCH_ASSOC);

// Se o livro não existir, redireciona
if (!$livro_editar) {
    set_flash_message("Livro não encontrado.", 'error');
    header("Location: listar_livros.php");
    exit;
}

// Busca as receitas já associadas e todas as receitas disponíveis
$stmt_receitas = $conn->prepare("SELECT nome_receita FROM livro_receita WHERE id_livro = ?");
$stmt_receitas->execute([$id_livro]);
$receitas_do_livro = $stmt_receitas->fetchAll(PDO::FETCH_COLUMN);

$receitasDisponiveis = $conn->query("SELECT nome_receita FROM receitas")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Livro</title>
    <link rel="stylesheet" href="../styles/func.css">
</head>
<body>
<div class="container">
    <h2>Editar Livro: <?= htmlspecialchars($livro_editar['titulo']) ?></h2>
    <form method="POST" action="editar_livro.php?id=<?= $id_livro ?>">
        <input type="text" name="titulo" placeholder="Título" value="<?= htmlspecialchars($livro_editar['titulo']) ?>" required>
        <input type="text" name="isbn" placeholder="ISBN" value="<?= htmlspecialchars($livro_editar['isbn']) ?>" required>
        <input type="text" name="descricao" placeholder="Descrição" value="<?= htmlspecialchars($livro_editar['descricao']) ?>" required>
        
        <label for="receitas">Selecione as Receitas:</label>
        <select name="receitas[]" id="receitas" multiple size="8" required>
            <?php foreach ($receitasDisponiveis as $receita): ?>
                <option value="<?= htmlspecialchars($receita['nome_receita']) ?>" 
                    <?= in_array($receita['nome_receita'], $receitas_do_livro) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($receita['nome_receita']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Atualizar Livro</button>
        <a href="listar_livros.php">Cancelar</a>
    </form>
</div>
</body>
</html>