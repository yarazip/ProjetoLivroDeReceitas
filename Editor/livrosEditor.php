<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../conexao.php';

// Variáveis de controle
$error = '';
$success = '';
$edicaoAtiva = false;
$livroEditando = null;

// Processar operações
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Modo edição
    if (isset($_GET['editar'])) {
        $isbn = intval($_GET['editar']);
        $stmt = $conn->prepare("SELECT * FROM Livro WHERE isbn = :isbn");
        $stmt->execute([':isbn' => $isbn]);
        $livroEditando = $stmt->fetch(PDO::FETCH_ASSOC);
        $edicaoAtiva = ($livroEditando !== false);
    }
    
    // Exclusão de livro
    if (isset($_GET['excluir'])) {
        $isbn = intval($_GET['excluir']);
        try {
            $conn->beginTransaction();
            
            // Primeiro excluir das tabelas relacionadas
            $conn->prepare("DELETE FROM LivroReceita WHERE livro_isbn = :isbn")->execute([':isbn' => $isbn]);
            
            // Depois excluir o livro
            $conn->prepare("DELETE FROM Livro WHERE isbn = :isbn")->execute([':isbn' => $isbn]);
            
            $conn->commit();
            $success = "Livro excluído com sucesso!";
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Erro ao excluir livro: " . $e->getMessage();
        }
    }
}

// Processar formulário (adição/edição)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isbn = trim($_POST['isbn']);
    $titulo = trim($_POST['titulo']);
    $data_publicacao = trim($_POST['data_publicacao']);
    $editor_email = trim($_POST['editor_email']);
    
    try {
        if ($edicaoAtiva && isset($_POST['editar_isbn'])) {
            // Modo edição
            $isbn_original = $_POST['editar_isbn'];
            
            $stmt = $conn->prepare("UPDATE Livro SET 
                                  isbn = :isbn,
                                  titulo = :titulo,
                                  data_publicacao = :data_publicacao
                                  WHERE isbn = :isbn_original");
            $stmt->execute([
                ':isbn' => $isbn,
                ':titulo' => $titulo,
                ':data_publicacao' => $data_publicacao,
                ':isbn_original' => $isbn_original
            ]);
            
            $success = "Livro atualizado com sucesso!";
        } else {
            // Modo adição
            $stmt = $conn->prepare("INSERT INTO Livro 
                                  (isbn, titulo, data_publicacao) 
                                  VALUES 
                                  (:isbn, :titulo, :data_publicacao)");
            $stmt->execute([
                ':isbn' => $isbn,
                ':titulo' => $titulo,
                ':data_publicacao' => $data_publicacao
            ]);
            
            $success = "Livro adicionado com sucesso!";
        }
        
        header("Location: livrosEditor.php?success=1");
        exit();
    } catch (Exception $e) {
        $error = "Erro ao salvar livro: " . $e->getMessage();
    }
}

// Obter lista de editores
$editores = $conn->query("SELECT u.email, u.nome FROM Usuario u JOIN Editor e ON u.email = e.email")
                ->fetchAll(PDO::FETCH_ASSOC);

// Obter livros (com pesquisa se existir)
$searchTerm = $_GET['search'] ?? '';
$query = "SELECT l.*, u.nome AS editor_nome 
          FROM Livro l
          LEFT JOIN Editor e ON 1=1
          LEFT JOIN Usuario u ON e.email = u.email
          WHERE 1=1";
$params = [];

if (!empty($searchTerm)) {
    $query .= " AND (l.titulo LIKE :search OR u.nome LIKE :search)";
    $params[':search'] = "%$searchTerm%";
}

$query .= " ORDER BY l.titulo";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$livros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/func.css">
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
    <title>Livros | Editor</title>
    <style>
        .cancelar-edicao {
            background-color: #f44336;
            margin-left: 10px;
        }
        .livros-list {
            margin-top: 20px;
        }
        .livros-list table {
            width: 100%;
            border-collapse: collapse;
        }
        .livros-list th, .livros-list td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .livros-list th {
            background-color: #f2f2f2;
        }
        .error {
            color: #e74c3c;
            margin: 10px 0;
            padding: 10px;
            background: #fdecea;
            border-radius: 4px;
        }
        .success {
            color: #27ae60;
            margin: 10px 0;
            padding: 10px;
            background: #e8f5e9;
            border-radius: 4px;
        }
        .insert-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0;
        }
        .insert-bar input, 
        .insert-bar select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .insert-bar button {
            padding: 8px 15px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
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
            </nav>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php elseif (isset($_GET['success'])): ?>
            <div class="success">Operação realizada com sucesso!</div>
        <?php endif; ?>
        
        <form method="GET" class="search-bar">
            <input type="text" name="search" placeholder="Pesquisar livro..." 
                   value="<?php echo htmlspecialchars($searchTerm); ?>">
            <button type="submit">Pesquisar</button>
            <?php if (!empty($searchTerm)): ?>
                <a href="livrosEditor.php" class="button">Limpar</a>
            <?php endif; ?>
        </form>

        <form method="POST" class="insert-bar">
            <?php if ($edicaoAtiva): ?>
                <input type="hidden" name="editar_isbn" value="<?php echo $livroEditando['isbn']; ?>">
                <h3>Editando Livro: <?php echo htmlspecialchars($livroEditando['titulo']); ?></h3>
            <?php endif; ?>
            
            <input type="number" name="isbn" placeholder="ISBN" 
                   value="<?php echo $edicaoAtiva ? htmlspecialchars($livroEditando['isbn']) : ''; ?>" required>
            
            <input type="text" name="titulo" placeholder="Título do Livro" 
                   value="<?php echo $edicaoAtiva ? htmlspecialchars($livroEditando['titulo']) : ''; ?>" required>
            
            <input type="date" name="data_publicacao" placeholder="Data de Publicação" 
                   value="<?php echo $edicaoAtiva ? htmlspecialchars($livroEditando['data_publicacao']) : ''; ?>">
            
            <select name="editor_email">
                <option value="">Selecione o Editor</option>
                <?php foreach ($editores as $editor): ?>
                    <option value="<?php echo htmlspecialchars($editor['email']); ?>">
                        <?php echo htmlspecialchars($editor['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit"><?php echo $edicaoAtiva ? 'Salvar Edição' : 'Adicionar'; ?></button>
            
            <?php if ($edicaoAtiva): ?>
                <a href="livrosEditor.php" class="button cancelar-edicao">Cancelar</a>
            <?php endif; ?>
        </form>

        <div class="livros-list">
            <h2>Livros Cadastrados</h2>
            <table>
                <thead>
                    <tr>
                        <th>ISBN</th>
                        <th>Título</th>
                        <th>Data de Publicação</th>
                        <th>Editor</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($livros as $livro): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($livro['isbn']); ?></td>
                        <td><?php echo htmlspecialchars($livro['titulo']); ?></td>
                        <td><?php echo htmlspecialchars($livro['data_publicacao']); ?></td>
                        <td><?php echo htmlspecialchars($livro['editor_nome'] ?? 'Não atribuído'); ?></td>
                        <td>
                            <a href="livrosEditor.php?editar=<?php echo $livro['isbn']; ?>">Editar</a>
                            <a href="livrosEditor.php?excluir=<?php echo $livro['isbn']; ?>" 
                               onclick="return confirm('Tem certeza que deseja excluir este livro?')">Excluir</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>