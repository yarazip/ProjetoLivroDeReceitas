<?php
session_start();
//Para mostrar mais detalhado o erro que está dando
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../BancoDeDados/conexao.php';

// INSERIR degustação
if (isset($_POST['adicionar'])) {
    $id_funcionario = $_SESSION['id_funcionario'];
    $nome_receita = $_POST['nome_receita'];
    $data_degustacao = $_POST['data_degustacao'];
    $nota = $_POST['nota'];
    $descricao = $_POST['descricao'];


    try {
        $sql = "INSERT INTO degustacoes (id_funcionario, nome_receita, data_degustacao, nota, descricao)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id_funcionario, $nome_receita, $data_degustacao, $nota, $descricao]);
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            echo "<p style='color:red;'>Erro: Já existe uma degustação para esse funcionário e receita.</p>";
        } else {
            die("Erro ao adicionar degustação: " . $e->getMessage());
        }
    }
}

// INICIAR EDIÇÃO
$id_funcionario_edit = $_GET['id_funcionario'] ?? null;
$nome_receita_edit = $_GET['nome_receita'] ?? null;
$degustacao_editando = null;

if ($id_funcionario_edit && $nome_receita_edit) {
    $stmt = $conn->prepare("SELECT * FROM degustacoes WHERE id_funcionario = ? AND nome_receita = ?");
    $stmt->execute([$id_funcionario_edit, $nome_receita_edit]);
    $degustacao_editando = $stmt->fetch(PDO::FETCH_ASSOC);
}

// SALVAR EDIÇÃO
if (isset($_POST['salvar_edicao'])) {
    $id_funcionario = $_POST['id_funcionario'];
    $nome_receita = $_POST['nome_receita'];
    $data_degustacao = $_POST['data_degustacao'];
    $nota = $_POST['nota'];
    $descricao = $_POST['descricao'];

    try {
        $sql = "UPDATE degustacoes 
                SET data_degustacao = ?, nota = ?, descricao = ? 
                WHERE id_funcionario = ? AND nome_receita = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$data_degustacao, $nota, $descricao, $id_funcionario, $nome_receita]);

        header("Location: receitasDegustador.php");
        exit;
    } catch (PDOException $e) {
        die("Erro ao editar degustação: " . $e->getMessage());
    }
}

// EXCLUIR degustação
if (isset($_GET['excluir_id_funcionario']) && isset($_GET['excluir_nome_receita'])) {
    $id = $_GET['excluir_id_funcionario'];
    $receita = $_GET['excluir_nome_receita'];

    $sql = "DELETE FROM degustacoes WHERE id_funcionario = ? AND nome_receita = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id, $receita]);

    header("Location: receitasDegustador.php");
    exit;
}

// PESQUISA
$termo = $_GET['pesquisa'] ?? '';
$sql = "SELECT d.*, f.nome AS nome_funcionario
        FROM degustacoes d
        JOIN funcionarios f ON d.id_funcionario = f.id_funcionario
        WHERE d.nome_receita LIKE ?
        ORDER BY d.data_degustacao DESC";
$stmt = $conn->prepare($sql);
$stmt->execute(["%$termo%"]);
$degustacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar receitas e funcionários para os selects
$receitas = $conn->query("SELECT nome_receita FROM receitas")->fetchAll(PDO::FETCH_COLUMN);
$funcionarios = $conn->query("
    SELECT f.id_funcionario, f.nome
    FROM funcionarios f
    JOIN cargos c ON f.id_cargo = c.id_cargo
    WHERE c.nome = 'Degustador'
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Receitas | Avaliação</title>
    <link rel="stylesheet" href="../styles/func.css">
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
</head>
<body>
<div class="container">
    <div class="menu">
        <h1 class="logo">Código de Sabores</h1>
        <nav>
            <a href="receitasDegustador.php">Receitas</a>
        </nav>
    </div>

    <!-- PESQUISA -->
    <div class="search-bar">
        <form method="GET" action="receitasDegustador.php">
            <input type="text" name="pesquisa" placeholder="Pesquisar receita..." value="<?= htmlspecialchars($termo) ?>">
            <button type="submit">Pesquisar</button>
            <?php if($termo): ?>
                <a href="receitasDegustador.php"><button type="button">Limpar</button></a>
            <?php endif; ?>
        </form>
    </div>

    <!-- FORMULÁRIO INSERIR OU EDITAR -->
    <div class="insert-bar">
        <form method="POST" action="receitasDegustador.php">
            <?php if ($degustacao_editando): ?>
                <input type="hidden" name="id_funcionario" value="<?= $degustacao_editando['id_funcionario'] ?>">
                <input type="hidden" name="nome_receita" value="<?= htmlspecialchars($degustacao_editando['nome_receita']) ?>">

                <p><strong>Funcionário:</strong> <?= htmlspecialchars($degustacao_editando['id_funcionario']) ?></p>
                <p><strong>Receita:</strong> <?= htmlspecialchars($degustacao_editando['nome_receita']) ?></p>

                <input type="date" name="data_degustacao" value="<?= $degustacao_editando['data_degustacao'] ?>" required>
                <input type="text" name="nota" placeholder="Nota (ex: 8.5)" value="<?= htmlspecialchars($degustacao_editando['nota']) ?>" required>
                <input type="text" name="descricao" placeholder="Descrição (opcional)" value="<?= htmlspecialchars($degustacao_editando['descricao']) ?>">

                <button type="submit" name="salvar_edicao">Salvar</button>
                <a href="receitasDegustador.php"><button type="button">Cancelar</button></a>

            <?php else: ?>
                 <?php
            
            // Supondo que você tenha guardado o nome do funcionário na sessão no login (pode adicionar isso)
            echo "Funcionário logado: " . htmlspecialchars($_SESSION['nome_funcionario'] ?? 'Desconhecido');
            ?>

                <select name="nome_receita" required>
                    <option value="">Selecione a Receita</option>
                    <?php foreach ($receitas as $receita): ?>
                        <option value="<?= htmlspecialchars($receita) ?>"><?= htmlspecialchars($receita) ?></option>
                    <?php endforeach; ?>
                </select>

                <input type="date" name="data_degustacao" required>
                <input type="text" name="nota" placeholder="Nota (ex: 8.5)" required>
                <input type="text" name="descricao" placeholder="Descrição (opcional)">
                <button type="submit" name="adicionar">Adicionar</button>
            <?php endif; ?>
        </form>
    </div>

    <!-- LISTAGEM -->
    <div class="lista-degustacoes">
        <h2>Degustações Realizadas</h2>
        <table border="1" cellpadding="8">
            <thead>
                <tr>
                    <th>Funcionário</th>
                    <th>Receita</th>
                    <th>Data</th>
                    <th>Nota</th>
                    <th>Descrição</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($degustacoes): ?>
                    <?php foreach ($degustacoes as $deg): ?>
                        <tr>
                            <td><?= htmlspecialchars($deg['nome_funcionario']) ?></td>
                            <td><?= htmlspecialchars($deg['nome_receita']) ?></td>
                            <td><?= htmlspecialchars($deg['data_degustacao']) ?></td>
                            <td><?= htmlspecialchars($deg['nota']) ?></td>
                            <td><?= htmlspecialchars($deg['descricao']) ?></td>
                            <td>
                                <a href="?id_funcionario=<?= $deg['id_funcionario'] ?>&nome_receita=<?= urlencode($deg['nome_receita']) ?>">
                                    <button>Editar</button>
                                </a>
                                <a href="?excluir_id_funcionario=<?= $deg['id_funcionario'] ?>&excluir_nome_receita=<?= urlencode($deg['nome_receita']) ?>"
                                   onclick="return confirm('Deseja excluir esta degustação?');">
                                    <button>Excluir</button>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">Nenhuma degustação encontrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
