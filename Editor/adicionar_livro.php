<?php
require_once '../BancoDeDados/conexao.php';
require_once '../dompdf/autoload.inc.php';

// Processa o formulário quando ele for enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'];
    $isbn = $_POST['isbn'];
    $descricao = $_POST['descricao'];
    $id_funcionario = $_SESSION['id_funcionario'];
    $receitas = $_POST['receitas'] ?? [];

    try {
        $conn->beginTransaction();

        $sql = "INSERT INTO livros (titulo, isbn, descricao, id_funcionario) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$titulo, $isbn, $descricao, $id_funcionario]);

        $id_livro_novo = $conn->lastInsertId();

        $stmt_receita = $conn->prepare("INSERT INTO livro_receita (id_livro, nome_receita) VALUES (?, ?)");
        foreach ($receitas as $nome_receita) {
            $stmt_receita->execute([$id_livro_novo, $nome_receita]);
        }

        $conn->commit();
        set_flash_message("Livro adicionado com sucesso!");

    } catch (Exception $e) {
        $conn->rollBack();
        set_flash_message("Erro ao adicionar o livro: " . $e->getMessage(), 'error');
    }

    header("Location: listar_livros.php");
    exit;
}

// Busca todas as receitas disponíveis para o formulário
$receitasDisponiveis = $conn->query("SELECT nome_receita FROM receitas")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Livro</title>
    <link rel="stylesheet" href="../styles/func.css">
</head>
<body>
<div class="container">
    <h2>Adicionar Novo Livro</h2>
    <form method="POST" action="adicionar_livro.php">
        <input type="text" name="titulo" placeholder="Título do Livro" required>
        <input type="text" name="isbn" placeholder="ISBN" required>
        <input type="text" name="descricao" placeholder="Descrição" required>
        
        <label for="receitas">Selecione as Receitas:</label>
        <select name="receitas[]" id="receitas" multiple size="8" required>
            <?php foreach ($receitasDisponiveis as $receita): ?>
                <option value="<?= htmlspecialchars($receita['nome_receita']) ?>">
                    <?= htmlspecialchars($receita['nome_receita']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Adicionar Livro</button>
        <a href="listar_livros.php">Cancelar</a>
    </form>
</div>
</body>
</html>