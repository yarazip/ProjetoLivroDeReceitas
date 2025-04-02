<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../conexao.php';

// Inicializa todas as variáveis para evitar undefined
$nome_ingrediente = isset($_POST['nome_ingrediente']) ? trim($_POST['nome_ingrediente']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$mensagem = '';

try {
    // Processar formulário de adição
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar'])) {
        $nome_ingrediente = trim($_POST['nome_ingrediente']);
        
        // Validar dados
        if (empty($nome_ingrediente)) {
            throw new Exception("O nome do ingrediente é obrigatório!");
        }
        
        // Inserir no banco de dados
        $stmt = $conn->prepare("INSERT INTO Ingrediente (nome) VALUES (:nome)");
        $stmt->execute([':nome' => $nome_ingrediente]);
        
        $mensagem = "Ingrediente adicionado com sucesso!";
        $nome_ingrediente = ''; // Limpa o campo após inserção
    }
    
    // Processar busca
    $ingredientes = [];
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
        $search = trim($_GET['search']);
        
        $stmt = $conn->prepare("SELECT * FROM Ingrediente WHERE nome LIKE :search ORDER BY nome ASC");
        $stmt->execute([':search' => "%$search%"]);
        $ingredientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Listar todos os ingredientes
        $stmt = $conn->prepare("SELECT * FROM Ingrediente ORDER BY nome ASC");
        $stmt->execute();
        $ingredientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch(PDOException $e) {
    $mensagem = "Erro no banco de dados: " . $e->getMessage();
} catch(Exception $e) {
    $mensagem = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="../styles/ADM.css">
    <title>Ingredientes Administração</title>
    <style>
        .mensagem {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .sucesso {
            background-color: #d4edda;
            color: #155724;
        }
        .erro {
            background-color: #f8d7da;
            color: #721c24;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .acao {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="menu">
            <h1 class="logo">Código de Sabores</h1>
            <nav>
                <a href="receitasChef.php">Receitas</a>
                <a href="ingredientesChef.php">Ingredientes</a>
                <a href="medidasChef.php">Medidas</a>
            </nav>
        </div>
        
        <?php if (!empty($mensagem)): ?>
            <div class="mensagem <?= strpos($mensagem, 'sucesso') !== false ? 'sucesso' : 'erro' ?>">
                <?= $mensagem ?>
            </div>
        <?php endif; ?>
        
        <form method="GET" class="search-bar">
            <input type="text" name="search" placeholder="Pesquisar ingrediente..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Pesquisar</button>
        </form>

        <form method="POST" class="insert-bar">
            <input type="text" name="nome_ingrediente" placeholder="Nome do ingrediente" value="<?= htmlspecialchars($nome_ingrediente) ?>" required>
            <button type="submit" name="adicionar">Adicionar</button>
        </form>
        
        <?php if (!empty($ingredientes)): ?>
        <div class="results">
            <h2>Ingredientes Cadastrados</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th class="acao">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ingredientes as $ingrediente): ?>
                    <tr>
                        <td><?= htmlspecialchars($ingrediente['id']) ?></td>
                        <td><?= htmlspecialchars($ingrediente['nome']) ?></td>
                        <td class="acao">
                            <a href="editar_ingrediente.php?id=<?= $ingrediente['id'] ?>">Editar</a> | 
                            <a href="excluir_ingrediente.php?id=<?= $ingrediente['id'] ?>" onclick="return confirm('Tem certeza que deseja excluir este ingrediente?')">Excluir</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p>Nenhum ingrediente encontrado.</p>
        <?php endif; ?>
    </div>
</body>
</html>