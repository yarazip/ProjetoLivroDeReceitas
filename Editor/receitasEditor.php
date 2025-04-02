<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../conexao.php';

// Verificar se o usuário é um editor
if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'editor') {
    header('Location: ../../loginSenha/login.php');
    exit();
}

// Processar pesquisa
$searchTerm = '';
$receitas = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $searchTerm = trim($_GET['search']);
    $stmt = $conn->prepare("SELECT r.id, r.nome, r.data_fabricacao, u.nome AS cozinheiro_nome 
                          FROM Receita r
                          JOIN Cozinheiro c ON r.cozinheiro_email = c.email
                          JOIN Usuario u ON c.email = u.email
                          WHERE r.nome LIKE :search
                          ORDER BY r.nome");
    $stmt->execute([':search' => "%$searchTerm%"]);
    $receitas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Listar todas as receitas se não houver pesquisa
    $stmt = $conn->query("SELECT r.id, r.nome, r.data_fabricacao, u.nome AS cozinheiro_nome 
                         FROM Receita r
                         JOIN Cozinheiro c ON r.cozinheiro_email = c.email
                         JOIN Usuario u ON c.email = u.email
                         ORDER BY r.nome");
    $receitas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Processar adição de nova receita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar'])) {
    $nome = trim($_POST['nome']);
    $data_fabricacao = trim($_POST['data_fabricacao']);
    $cozinheiro_email = trim($_POST['cozinheiro_email']);
    $dificuldade = trim($_POST['dificuldade']);
    $porcoes = intval($_POST['porcoes']);

    try {
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("INSERT INTO Receita 
                              (nome, data_fabricacao, dificuldade, porcoes, cozinheiro_email) 
                              VALUES 
                              (:nome, :data_fabricacao, :dificuldade, :porcoes, :cozinheiro_email)");
        $stmt->execute([
            ':nome' => $nome,
            ':data_fabricacao' => $data_fabricacao,
            ':dificuldade' => $dificuldade,
            ':porcoes' => $porcoes,
            ':cozinheiro_email' => $cozinheiro_email
        ]);
        
        $conn->commit();
        header("Location: receitasEditor.php?success=1");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Erro ao adicionar receita: " . $e->getMessage();
    }
}

// Obter lista de cozinheiros para o select
$cozinheiros = $conn->query("SELECT u.email, u.nome 
                            FROM Usuario u 
                            JOIN Cozinheiro c ON u.email = c.email
                            ORDER BY u.nome")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../styles/func.css">
    <link rel="shortcut icon" href="../../assets/favicon.png" type="image/x-icon">
    <title>Receitas Editor</title>
    <style>
        .receitas-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .receitas-table th, .receitas-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .receitas-table th {
            background-color: #f2f2f2;
        }
        .action-links a {
            margin-right: 10px;
            color: #0066cc;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="menu">
            <h1 class="logo">Código de Sabores</h1>
            <nav>
                <a href="livrosEditor.php">Livros</a>
                <a href="receitasEditor.php">Receitas</a>
                <a href="../../loginSenha/login.php?logout=1">Sair</a>
            </nav>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (isset($_GET['success'])): ?>
            <div class="success">Receita adicionada com sucesso!</div>
        <?php endif; ?>

        <form method="GET" class="search-bar">
            <input type="text" name="search" placeholder="Pesquisar receita..." 
                   value="<?php echo htmlspecialchars($searchTerm); ?>">
            <button type="submit">Pesquisar</button>
            <?php if (!empty($searchTerm)): ?>
                <a href="receitasEditor.php" class="button">Limpar</a>
            <?php endif; ?>
        </form>

        <form method="POST" class="insert-bar">
            <input type="text" name="nome" placeholder="Nome da Receita" required>
            <input type="date" name="data_fabricacao" required>
            <select name="dificuldade" required>
                <option value="">Dificuldade</option>
                <option value="Fácil">Fácil</option>
                <option value="Médio">Médio</option>
                <option value="Difícil">Difícil</option>
            </select>
            <input type="number" name="porcoes" placeholder="Porções" min="1" required>
            <select name="cozinheiro_email" required>
                <option value="">Selecione o Chef</option>
                <?php foreach ($cozinheiros as $cozinheiro): ?>
                    <option value="<?php echo htmlspecialchars($cozinheiro['email']); ?>">
                        <?php echo htmlspecialchars($cozinheiro['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="adicionar">Adicionar</button>
        </form>

        <div class="receitas-list">
            <h2>Receitas Cadastradas</h2>
            <?php if (count($receitas) > 0): ?>
                <table class="receitas-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Data de Fabricação</th>
                            <th>Chef</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($receitas as $receita): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($receita['id']); ?></td>
                            <td><?php echo htmlspecialchars($receita['nome']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($receita['data_fabricacao'])); ?></td>
                            <td><?php echo htmlspecialchars($receita['cozinheiro_nome']); ?></td>
                            <td class="action-links">
                                <a href="editarReceita.php?id=<?php echo $receita['id']; ?>">Editar</a>
                                <a href="excluirReceita.php?id=<?php echo $receita['id']; ?>" 
                                   onclick="return confirm('Tem certeza que deseja excluir esta receita?')">Excluir</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nenhuma receita encontrada.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>