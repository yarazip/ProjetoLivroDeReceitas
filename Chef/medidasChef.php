<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../conexao.php';

// Inicializa variáveis
$descricao = $tipo = $search = '';
$mensagem = '';
$modo_edicao = false;
$medida_editando = null;

// Operações CRUD
try {
    // CREATE - Adicionar nova medida
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar'])) {
        $descricao = trim($_POST['descricao']);
        $tipo = trim($_POST['tipo']);
        
        // Validação
        if (empty($descricao)) {
            throw new Exception("A descrição da medida é obrigatória!");
        }
        
        // Inserir
        $stmt = $conn->prepare("INSERT INTO Medida (descricao, tipo) VALUES (:descricao, :tipo)");
        $stmt->execute([':descricao' => $descricao, ':tipo' => $tipo]);
        
        $mensagem = "Medida adicionada com sucesso!";
        $descricao = $tipo = '';
    }
    
    // UPDATE - Carregar dados para edição
    if (isset($_GET['editar'])) {
        $id_editar = $_GET['editar'];
        $stmt = $conn->prepare("SELECT * FROM Medida WHERE id = :id");
        $stmt->execute([':id' => $id_editar]);
        $medida_editando = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($medida_editando) {
            $modo_edicao = true;
            $descricao = $medida_editando['descricao'];
            $tipo = $medida_editando['tipo'];
        }
    }
    
    // UPDATE - Salvar edição
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_edicao'])) {
        $id = $_POST['id'];
        $descricao = trim($_POST['descricao']);
        $tipo = trim($_POST['tipo']);
        
        // Validação
        if (empty($descricao)) {
            throw new Exception("A descrição da medida é obrigatória!");
        }
        
        // Atualizar
        $stmt = $conn->prepare("UPDATE Medida SET descricao = :descricao, tipo = :tipo WHERE id = :id");
        $stmt->execute([
            ':descricao' => $descricao,
            ':tipo' => $tipo,
            ':id' => $id
        ]);
        
        $mensagem = "Medida atualizada com sucesso!";
        $modo_edicao = false;
        $descricao = $tipo = '';
    }
    
    // DELETE - Excluir medida
    if (isset($_GET['excluir'])) {
        $id_excluir = $_GET['excluir'];
        
        // Verificar se a medida está sendo usada em ReceitaIngrediente
        $stmt = $conn->prepare("SELECT COUNT(*) FROM ReceitaIngrediente WHERE quantidade_id = :id");
        $stmt->execute([':id' => $id_excluir]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Não é possível excluir - esta medida está sendo usada em receitas!");
        }
        
        // Verificar se está sendo usada na tabela Quantidade
        $stmt = $conn->prepare("SELECT COUNT(*) FROM Quantidade WHERE id = :id");
        $stmt->execute([':id' => $id_excluir]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Não é possível excluir - esta medida está vinculada à tabela Quantidade!");
        }
        
        // Excluir
        $stmt = $conn->prepare("DELETE FROM Medida WHERE id = :id");
        $stmt->execute([':id' => $id_excluir]);
        
        $mensagem = "Medida excluída com sucesso!";
    }
    
    // READ - Buscar medidas
    $medidas = [];
    $search = $_GET['search'] ?? '';
    
    if (!empty($search)) {
        $stmt = $conn->prepare("SELECT * FROM Medida WHERE descricao LIKE :search OR tipo LIKE :search ORDER BY descricao ASC");
        $stmt->execute([':search' => "%$search%"]);
    } else {
        $stmt = $conn->prepare("SELECT * FROM Medida ORDER BY descricao ASC");
        $stmt->execute();
    }
    
    $medidas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    <title>Medidas Administração</title>
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
        .form-edicao {
            background-color: #f0f8ff;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
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
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>
        
        <!-- Formulário de Busca -->
        <form method="GET" class="search-bar">
            <input type="text" name="search" placeholder="Pesquisar medida..." 
                   value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Pesquisar</button>
            <?php if ($modo_edicao): ?>
                <a href="medidasChef.php" class="btn-cancelar">Cancelar Edição</a>
            <?php endif; ?>
        </form>

        <!-- Formulário de Adição/Edição -->
        <div class="<?= $modo_edicao ? 'form-edicao' : '' ?>">
            <h2><?= $modo_edicao ? 'Editar Medida' : 'Adicionar Nova Medida' ?></h2>
            <form method="POST">
                <?php if ($modo_edicao): ?>
                    <input type="hidden" name="id" value="<?= htmlspecialchars($medida_editando['id']) ?>">
                <?php endif; ?>
                
                <div class="insert-bar">
                    <input type="text" name="descricao" placeholder="Descrição (ex: Colher de sopa)" 
                           value="<?= htmlspecialchars($descricao) ?>" required>
                    <input type="text" name="tipo" placeholder="Tipo (ex: volume, peso)" 
                           value="<?= htmlspecialchars($tipo) ?>">
                    
                    <?php if ($modo_edicao): ?>
                        <button type="submit" name="salvar_edicao">Salvar Edição</button>
                        <a href="medidasChef.php" class="btn-cancelar">Cancelar</a>
                    <?php else: ?>
                        <button type="submit" name="adicionar">Adicionar</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Lista de Medidas -->
        <?php if (!empty($medidas)): ?>
        <div class="results">
            <h2>Medidas Cadastradas</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Descrição</th>
                        <th>Tipo</th>
                        <th class="acao">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medidas as $medida): ?>
                    <tr>
                        <td><?= htmlspecialchars($medida['id']) ?></td>
                        <td><?= htmlspecialchars($medida['descricao']) ?></td>
                        <td><?= htmlspecialchars($medida['tipo']) ?></td>
                        <td class="acao">
                            <a href="medidasChef.php?editar=<?= $medida['id'] ?>">Editar</a> | 
                            <a href="medidasChef.php?excluir=<?= $medida['id'] ?>" 
                               onclick="return confirm('Tem certeza que deseja excluir esta medida?')">Excluir</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p>Nenhuma medida encontrada.</p>
        <?php endif; ?>
    </div>
</body>
</html>