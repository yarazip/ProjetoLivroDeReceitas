<?php
session_start();
if (!isset($_SESSION['id_funcionario'])) {
    header("Location: ../LoginSenha/login.php");
    exit();
}
require_once '../BancoDeDados/conexao.php';
require_once '../dompdf/autoload.inc.php';

// Verifica se o funcionário está logado
if (!isset($_SESSION['id_funcionario'])) {
    header("Location: ../../LoginSenha/login.php");
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

    header("Location: livrosEditor.php");
    exit;
}

// Busca todas as receitas disponíveis para exibir no formulário
$receitasDisponiveis = $conn->query("SELECT nome_receita FROM receitas")->fetchAll(PDO::FETCH_ASSOC);

// Para evitar erro de variável indefinida
$livro_editar = $livro_editar ?? [];
$receitas_livro = $receitas_livro ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Livro</title>
    <link rel="stylesheet" href="../styles/adicionarADM.css">
    <link rel="stylesheet" href="../styles/livrosEDITOR.css">
    <link rel="stylesheet" href="../assets/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>
        <a href="../LoginSenha/logout.php" class="logout-button">
        <i class="fa-solid fa-right-from-bracket fa-lg gray-icon"></i>
    </a>
     <div class="container">
        <div class="menu">
            <div class="menu-content">
                <h1 class="logo">Código de Sabores</h1>
                <nav>
                    <a href="livrosEditor.php" class="active">Livros</a>
                    <!-- <a href="listar_receitas_editor.php">Receitas</a> -->
                    <div class="user-info">
                        <i class="fas fa-user"></i>
                        <span><?= htmlspecialchars($_SESSION['nome_funcionario'] ?? 'Desconhecido') ?></span>
                    </div>

                </nav>
            </div>
        </div>
    <div class="insert-bar">
        <h2>Adicionar Novo Livro</h2>
        <form method="POST" action="adicionar_livro.php">
            <input type="hidden" name="id_livro" value="<?= htmlspecialchars($livro_editar['id_livro'] ?? '') ?>">

            <input type="text" name="titulo" placeholder="Título" 
                   value="<?= htmlspecialchars($livro_editar['titulo'] ?? '') ?>" required>

            <input type="number" name="isbn" placeholder="ISBN" 
                   value="<?= htmlspecialchars($livro_editar['isbn'] ?? '') ?>" required>

            <input type="text" name="descricao" placeholder="Descrição" 
                   value="<?= htmlspecialchars($livro_editar['descricao'] ?? '') ?>" required>

       <label>Selecione as Receitas:</label>
<?php foreach ($receitasDisponiveis as $receita): ?>
    <div class="checkbox-item">
        <input type="checkbox" name="receitas[]" value="<?= htmlspecialchars($receita['nome_receita']) ?>"
            <?= in_array($receita['nome_receita'], $receitas_livro) ? 'checked' : '' ?>>
        <label><?= htmlspecialchars($receita['nome_receita']) ?></label>
    </div>
<?php endforeach; ?>

            </select>

            <button type="submit">Adicionar Livro</button>
            <a class="add-button" href="livrosEditor.php">Cancelar</a>
        </form>
    </div>
</body>
</html>
