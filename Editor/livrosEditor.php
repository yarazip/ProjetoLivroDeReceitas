<?php
session_start();
require_once './../BancoDeDados/conexao.php';

// Verifica se funcionário está logado (id_funcionario)
if (!isset($_SESSION['id_funcionario'])) {
    header("Location: ../../LoginSenha/login.php");
    exit;
}

// Exclusão via POST (mais seguro que GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir'])) {
    $id_livro = $_POST['excluir'];

    $conn->beginTransaction();

    try {
        $stmt = $conn->prepare("DELETE FROM livro_receita WHERE id_livro = ?");
        $stmt->execute([$id_livro]);

        $stmt = $conn->prepare("DELETE FROM livros WHERE id_livro = ?");
        $stmt->execute([$id_livro]);

        $conn->commit();

        $_SESSION['message'] = "Livro excluído com sucesso.";
        $_SESSION['message_type'] = "success";
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['message'] = "Erro ao excluir livro: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }

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
        header("Location: livrosEditor.php");
        exit;
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
    <link rel="stylesheet" href="../styles/livrosEDITOR.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="shortcut icon" href="../assets/favicon.png">
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



        <a class="add-button" href="adicionar_livro.php" class="botao">Adicionar Livro</a>

        </form>
    </div>

    <div class="livros-lista">
        <h2>Livros Cadastrados</h2>
        <?php if (!empty($_SESSION['message'])): ?>
            <div class="message <?= htmlspecialchars($_SESSION['message_type']) ?>">
                <?= htmlspecialchars($_SESSION['message']) ?>
            </div>
            <?php
            unset($_SESSION['message'], $_SESSION['message_type']);
            ?>
        <?php endif; ?>
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
                        <td><?= htmlspecialchars($livro['id_livro']) ?></td>
                        <td><?= htmlspecialchars($livro['titulo']) ?></td>
                        <td><?= htmlspecialchars($livro['isbn']) ?></td>
                        <td><?= htmlspecialchars($livro['descricao']) ?></td>
                        <td><?= htmlspecialchars($livro['id_funcionario']) ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="visualizarLivros.php?id=<?= htmlspecialchars($livro['id_livro']) ?>" class="view-button" title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="editar_livro.php?id=<?= htmlspecialchars($livro['id_livro']) ?>" class="edit-button" title="Editar">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <a href="excluir_livro.php?id=<?= htmlspecialchars($livro['id_livro']) ?>" class="delete-button" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <!-- Novo link para gerar PDF -->
                                <a href="gerarPDF.php?id_livro=<?= htmlspecialchars($livro['id_livro']) ?>" target="_blank" class="pdf-button" title="Gerar PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
    </div>
</body>

</html>