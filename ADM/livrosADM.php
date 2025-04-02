<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../conexao.php';

// Inicializa variáveis
$titulo = $isbn = $search = '';
$mensagem = '';
$modo_edicao = false;
$livro_editando = null;

// Operações CRUD
try {
    // CREATE - Adicionar novo livro
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar'])) {
        $titulo = trim($_POST['titulo']);
        $isbn = trim($_POST['isbn']);
        
        // Validação
        if (empty($titulo) || empty($isbn)) {
            throw new Exception("Título e ISBN são obrigatórios!");
        }
        
        // Verificar se ISBN já existe
        $stmt = $conn->prepare("SELECT COUNT(*) FROM Livro WHERE isbn = :isbn");
        $stmt->execute([':isbn' => $isbn]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Já existe um livro com este ISBN!");
        }
        
        // Inserir
        $stmt = $conn->prepare("INSERT INTO Livro (isbn, titulo, data_publicacao) VALUES (:isbn, :titulo, CURDATE())");
        $stmt->execute([':isbn' => $isbn, ':titulo' => $titulo]);
        
        $mensagem = "Livro adicionado com sucesso!";
        $titulo = $isbn = '';
    }
    
    // UPDATE - Carregar dados para edição
    if (isset($_GET['editar'])) {
        $isbn_editar = $_GET['editar'];
        $stmt = $conn->prepare("SELECT * FROM Livro WHERE isbn = :isbn");
        $stmt->execute([':isbn' => $isbn_editar]);
        $livro_editando = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($livro_editando) {
            $modo_edicao = true;
            $titulo = $livro_editando['titulo'];
            $isbn = $livro_editando['isbn'];
        }
    }
    
    // UPDATE - Salvar edição
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_edicao'])) {
        $isbn_original = $_POST['isbn_original'];
        $novo_isbn = trim($_POST['isbn']);
        $titulo = trim($_POST['titulo']);
        
        // Validação
        if (empty($titulo) || empty($novo_isbn)) {
            throw new Exception("Título e ISBN são obrigatórios!");
        }
        
        // Verificar se novo ISBN já existe (caso tenha mudado)
        if ($novo_isbn !== $isbn_original) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM Livro WHERE isbn = :isbn");
            $stmt->execute([':isbn' => $novo_isbn]);
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Já existe um livro com este ISBN!");
            }
        }
        
        // Atualizar
        $stmt = $conn->prepare("UPDATE Livro SET isbn = :novo_isbn, titulo = :titulo WHERE isbn = :isbn_original");
        $stmt->execute([
            ':novo_isbn' => $novo_isbn,
            ':titulo' => $titulo,
            ':isbn_original' => $isbn_original
        ]);
        
        $mensagem = "Livro atualizado com sucesso!";
        $modo_edicao = false;
        $titulo = $isbn = '';
    }
    
    // DELETE - Excluir livro
    // DELETE - Excluir livro (versão simplificada)
if (isset($_GET['excluir'])) {
    $isbn_excluir = $_GET['excluir'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM Livro WHERE isbn = :isbn");
        $stmt->execute([':isbn' => $isbn_excluir]);
        
        $mensagem = $stmt->rowCount() > 0 
            ? "Livro excluído com sucesso!" 
            : "Livro não encontrado!";
            
    } catch(PDOException $e) {
        $mensagem = "Erro ao excluir livro: " . $e->getMessage();
    }
}
    
    // READ - Buscar livros
    $livros = [];
    $search = $_GET['search'] ?? '';
    
    if (!empty($search)) {
        $stmt = $conn->prepare("SELECT * FROM Livro WHERE titulo LIKE :search OR isbn LIKE :search ORDER BY titulo ASC");
        $stmt->execute([':search' => "%$search%"]);
    } else {
        $stmt = $conn->prepare("SELECT * FROM Livro ORDER BY titulo ASC");
        $stmt->execute();
    }
    
    $livros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    <link rel="stylesheet" href="../styles/ADM.css">
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
    <title>Livros Administração</title>
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
                <a href="cargosADM.php">Cargo</a>
                <a href="restauranteADM.php">Restaurantes</a>
                <a href="livrosADM.php">Livros</a>
                <a href="receitasADM.php">Receitas</a>
                <a href="ingredientesADM.php">Ingredientes</a>
                <a href="medidasADM.php">Medidas</a>
            </nav>
        </div>
        
        <?php if (!empty($mensagem)): ?>
            <div class="mensagem <?= strpos($mensagem, 'sucesso') !== false ? 'sucesso' : 'erro' ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>
        
        <!-- Formulário de Busca -->
        <form method="GET" class="search-bar">
            <input type="text" name="search" placeholder="Pesquisar livro..." 
                   value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Pesquisar</button>
            <?php if ($modo_edicao): ?>
                <a href="livrosADM.php" class="btn-cancelar">Cancelar Edição</a>
            <?php endif; ?>
        </form>

        <!-- Formulário de Adição/Edição -->
        <div class="<?= $modo_edicao ? 'form-edicao' : '' ?>">
            <h2><?= $modo_edicao ? 'Editar Livro' : 'Adicionar Novo Livro' ?></h2>
            <form method="POST">
                <?php if ($modo_edicao): ?>
                    <input type="hidden" name="isbn_original" value="<?= htmlspecialchars($livro_editando['isbn']) ?>">
                <?php endif; ?>
                
                <div class="insert-bar">
                    <input type="text" name="titulo" placeholder="Nome do Livro" 
                           value="<?= htmlspecialchars($titulo) ?>" required>
                    <input type="text" name="isbn" placeholder="ISBN" 
                           value="<?= htmlspecialchars($isbn) ?>" required>
                    
                    <?php if ($modo_edicao): ?>
                        <button type="submit" name="salvar_edicao">Salvar Edição</button>
                        <a href="livrosADM.php" class="btn-cancelar">Cancelar</a>
                    <?php else: ?>
                        <button type="submit" name="adicionar">Adicionar</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Lista de Livros -->
        <?php if (!empty($livros)): ?>
        <div class="results">
            <h2>Livros Cadastrados</h2>
            <table>
                <thead>
                    <tr>
                        <th>ISBN</th>
                        <th>Título</th>
                        <th>Data Publicação</th>
                        <th class="acao">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($livros as $livro): ?>
                    <tr>
                        <td><?= htmlspecialchars($livro['isbn']) ?></td>
                        <td><?= htmlspecialchars($livro['titulo']) ?></td>
                        <td><?= date('d/m/Y', strtotime($livro['data_publicacao'])) ?></td>
                        <td class="acao">
                            <a href="livrosADM.php?editar=<?= urlencode($livro['isbn']) ?>">Editar</a> | 
                            <a href="livrosADM.php?excluir=<?= urlencode($livro['isbn']) ?>" 
                               onclick="return confirm('Tem certeza que deseja excluir este livro?')">Excluir</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p>Nenhum livro encontrado.</p>
        <?php endif; ?>
    </div>
</body>
</html>