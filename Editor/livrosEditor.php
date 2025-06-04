<?php
session_start();
require_once '../BancoDeDados/conexao.php';

// Excluir livro
if (isset($_GET['excluir'])) {
    $id_livro = $_GET['excluir'];

    $stmt = $conn->prepare("DELETE FROM livro_receita WHERE id_livro = ?");
    $stmt->execute([$id_livro]);

    $stmt = $conn->prepare("DELETE FROM livros WHERE id_livro = ?");
    $stmt->execute([$id_livro]);

    header("Location: livrosEditor.php");
    exit;
}

// Atualizar livro
if (isset($_POST['atualizar'])) {
    $id = $_POST['id_livro'];
    $titulo = $_POST['titulo'];
    $isbn = $_POST['isbn'];
    $descricao = $_POST['descricao'];
    $id_funcionario = $_SESSION['id_funcionario'];
    $receitas = $_POST['receitas'] ?? [];

    try {
        $conn->beginTransaction();

        $sql = "UPDATE livros SET titulo = ?, isbn = ?, descricao = ?, id_funcionario = ? WHERE id_livro = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$titulo, $isbn, $descricao, $id_funcionario, $id]);

        $stmt = $conn->prepare("DELETE FROM livro_receita WHERE id_livro = ?");
        $stmt->execute([$id]);

        $stmt = $conn->prepare("INSERT INTO livro_receita (id_livro, nome_receita) VALUES (?, ?)");
        foreach ($receitas as $nome_receita) {
            $stmt->execute([$id, $nome_receita]);
        }

        $conn->commit();
        header("Location: livrosEditor.php");
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        die("Erro ao atualizar livro: " . $e->getMessage());
    }
}

// Buscar todas as receitas disponíveis
$sql = "SELECT nome_receita FROM receitas";
$receitasDisponiveis = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Inserir novo livro
if (isset($_POST['adicionar'])) {
    $titulo = $_POST['titulo'];
    $isbn = $_POST['isbn'];
    $descricao = $_POST['descricao'];
    $id_funcionario = $_SESSION['id_funcionario'];
    $receitas = $_POST['receitas'] ?? [];

    try {
        $conn->beginTransaction();

        $sql = "INSERT INTO livros (titulo, isbn, descricao, id_funcionario)
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$titulo, $isbn, $descricao, $id_funcionario]);

        $id_livro = $conn->lastInsertId();

        $stmt = $conn->prepare("INSERT INTO livro_receita (id_livro, nome_receita) VALUES (?, ?)");
        foreach ($receitas as $nome_receita) {
            $stmt->execute([$id_livro, $nome_receita]);
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        die("Erro ao adicionar livro: " . $e->getMessage());
    }
}

// Obter dados de um livro para edição
$livro_editar = null;
$receitas_livro = [];
if (isset($_GET['editar'])) {
    $id_livro = $_GET['editar'];
    $stmt = $conn->prepare("SELECT * FROM livros WHERE id_livro = ?");
    $stmt->execute([$id_livro]);
    $livro_editar = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT nome_receita FROM livro_receita WHERE id_livro = ?");
    $stmt->execute([$id_livro]);
    $receitas_livro = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Buscar todos os livros cadastrados
$sql = "SELECT * FROM livros";
$stmt = $conn->query($sql);
$livros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Livros Editor</title>
    <link rel="stylesheet" href="../styles/func.css">
    <link rel="shortcut icon" href="../assets/favicon.png">
</head>
<body>
    <div class="container">
        <div class="menu">
            <h1 class="logo">Código de Sabores</h1>
            <nav>
                <a href="livrosEditor.php">Livros</a>
                <a href="visualizarLivros.php">Gerar PDF</a>
            </nav>
        </div>

        <div class="insert-bar">
            <form method="POST" action="livrosEditor.php">
                <input type="hidden" name="id_livro" value="<?= $livro_editar['id_livro'] ?? '' ?>">
                <input type="text" name="titulo" placeholder="Título" value="<?= $livro_editar['titulo'] ?? '' ?>" required>
                <input type="number" name="isbn" placeholder="ISBN" value="<?= $livro_editar['isbn'] ?? '' ?>" required>
                <input type="text" name="descricao" placeholder="Descrição" value="<?= $livro_editar['descricao'] ?? '' ?>" required>
                <?php
            
            // Supondo que você tenha guardado o nome do funcionário na sessão no login (pode adicionar isso)
            echo "Funcionário logado: " . htmlspecialchars($_SESSION['nome_funcionario'] ?? 'Desconhecido');
            ?>

                <label>Selecione as Receitas:</label>
                <select name="receitas[]" multiple size="5" required>
                    <?php foreach ($receitasDisponiveis as $receita): ?>
                        <option value="<?= $receita['nome_receita'] ?>" 
                            <?= in_array($receita['nome_receita'], $receitas_livro) ? 'selected' : '' ?>>
                            <?= $receita['nome_receita'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" name="<?= $livro_editar ? 'atualizar' : 'adicionar' ?>">
                    <?= $livro_editar ? 'Atualizar' : 'Adicionar' ?>
                </button>
            </form>
        </div>

        <div class="livros-lista">
            <h2>Livros Cadastrados</h2>
            <table border="1" cellpadding="8" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>ISBN</th>
                        <th>Descrição</th>
                        <th>ID Funcionário</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($livros as $livro): ?>
                        <tr>
                            <td><?= $livro['id_livro'] ?></td>
                            <td><?= htmlspecialchars($livro['titulo']) ?></td>
                            <td><?= htmlspecialchars($livro['isbn']) ?></td>
                            <td><?= htmlspecialchars($livro['descricao']) ?></td>
                            <td><?= $livro['id_funcionario'] ?></td>
                            <td>
                                <a href="?editar=<?= $livro['id_livro'] ?>">Editar</a> | 
                                <a href="?excluir=<?= $livro['id_livro'] ?>" onclick="return confirm('Deseja excluir?')">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
