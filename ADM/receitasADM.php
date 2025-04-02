<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../conexao.php';

// Variáveis para o modo de edição
$edicaoAtiva = false;
$receitaEditando = null;

// Verificar se estamos editando uma receita
if (isset($_GET['editar'])) {
    $id = $_GET['editar'];
    $stmt = $conn->prepare("SELECT * FROM Receita WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $receitaEditando = $stmt->fetch(PDO::FETCH_ASSOC);
    $edicaoAtiva = ($receitaEditando !== false);
}

// Processar exclusão de receita
if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    try {
        $conn->beginTransaction();
        
        // Primeiro excluir das tabelas relacionadas
        $conn->prepare("DELETE FROM ReceitaCategoria WHERE receita_id = :id")->execute([':id' => $id]);
        $conn->prepare("DELETE FROM ReceitaIngrediente WHERE receita_id = :id")->execute([':id' => $id]);
        
        // Depois excluir a receita
        $conn->prepare("DELETE FROM Receita WHERE id = :id")->execute([':id' => $id]);
        
        $conn->commit();
        header("Location: receitasADM.php?success=2"); // Código 2 para exclusão
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Erro ao excluir receita: " . $e->getMessage();
    }
}

// Processar pesquisa
$searchTerm = '';
$receitas = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $searchTerm = trim($_POST['searchTerm']);
    $stmt = $conn->prepare("SELECT r.*, u.nome AS cozinheiro_nome 
                          FROM Receita r 
                          JOIN Cozinheiro c ON r.cozinheiro_email = c.email 
                          JOIN Usuario u ON c.email = u.email 
                          WHERE r.nome LIKE :term");
    $stmt->bindValue(':term', "%$searchTerm%");
    $stmt->execute();
    $receitas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Listar todas as receitas por padrão
    $stmt = $conn->query("SELECT r.*, u.nome AS cozinheiro_nome 
                        FROM Receita r 
                        JOIN Cozinheiro c ON r.cozinheiro_email = c.email 
                        JOIN Usuario u ON c.email = u.email
                        ORDER BY r.data_fabricacao DESC");
    $receitas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Processar adição/edição de receita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add']) || isset($_POST['editar']))) {
    $nome = trim($_POST['nome']);
    $data = trim($_POST['data']);
    $categoria = trim($_POST['categoria']);
    $chefEmail = trim($_POST['chef']);
    
    try {
        $conn->beginTransaction();
        
        if ($edicaoAtiva && isset($_POST['editar'])) {
            // Modo edição
            $id = $receitaEditando['id'];
            $stmt = $conn->prepare("UPDATE Receita SET nome = :nome, data_fabricacao = :data, cozinheiro_email = :chef WHERE id = :id");
            $stmt->execute([':nome' => $nome, ':data' => $data, ':chef' => $chefEmail, ':id' => $id]);
            
            // Atualizar categoria (primeiro remove as antigas)
            $conn->prepare("DELETE FROM ReceitaCategoria WHERE receita_id = :id")->execute([':id' => $id]);
            if ($categoria) {
                $stmt = $conn->prepare("INSERT INTO ReceitaCategoria (receita_id, categoria_id) 
                                      VALUES (:receita_id, (SELECT id FROM Categoria WHERE nome = :categoria LIMIT 1))");
                $stmt->execute([':receita_id' => $id, ':categoria' => $categoria]);
            }
            
            $conn->commit();
            header("Location: receitasADM.php?success=3"); // Código 3 para edição
            exit();
        } else {
            // Modo adição
            $stmt = $conn->prepare("INSERT INTO Receita (nome, data_fabricacao, dificuldade, porcoes, cozinheiro_email) 
                                  VALUES (:nome, :data, 'Médio', 4, :chef)");
            $stmt->execute([':nome' => $nome, ':data' => $data, ':chef' => $chefEmail]);
            $receitaId = $conn->lastInsertId();
            
            // Associar categoria
            if ($categoria) {
                $stmt = $conn->prepare("INSERT INTO ReceitaCategoria (receita_id, categoria_id) 
                                      VALUES (:receita_id, (SELECT id FROM Categoria WHERE nome = :categoria LIMIT 1))");
                $stmt->execute([':receita_id' => $receitaId, ':categoria' => $categoria]);
            }
            
            $conn->commit();
            header("Location: receitasADM.php?success=1"); // Código 1 para adição
            exit();
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Erro ao " . ($edicaoAtiva ? "editar" : "adicionar") . " receita: " . $e->getMessage();
    }
}

// Obter lista de cozinheiros para o dropdown
$stmt = $conn->query("SELECT u.email, u.nome FROM Usuario u JOIN Cozinheiro c ON u.email = c.email");
$cozinheiros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obter lista de categorias
$stmt = $conn->query("SELECT nome FROM Categoria");
$categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/ADM.css">
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
    <title>Receitas Administração</title>
    <style>
        .cancelar-edicao {
            background-color: #f44336;
            margin-left: 10px;
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
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (isset($_GET['success'])): ?>
            <div class="success">
                <?php 
                switch($_GET['success']) {
                    case 1: echo "Receita adicionada com sucesso!"; break;
                    case 2: echo "Receita excluída com sucesso!"; break;
                    case 3: echo "Receita editada com sucesso!"; break;
                    default: echo "Operação realizada com sucesso!";
                }
                ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="search-bar">
            <input type="text" name="searchTerm" placeholder="Pesquisar receita..." value="<?php echo htmlspecialchars($searchTerm); ?>">
            <button type="submit" name="search">Pesquisar</button>
        </form>

        <form method="POST" class="insert-bar">
            <?php if ($edicaoAtiva): ?>
                <input type="hidden" name="editar" value="1">
                <h3>Editando Receita: <?php echo htmlspecialchars($receitaEditando['nome']); ?></h3>
            <?php endif; ?>
            
            <input type="text" name="nome" placeholder="Nome da Receita" 
                   value="<?php echo $edicaoAtiva ? htmlspecialchars($receitaEditando['nome']) : ''; ?>" required>
            
            <input type="date" name="data" placeholder="Data da Publicação" 
                   value="<?php echo $edicaoAtiva ? htmlspecialchars($receitaEditando['data_fabricacao']) : ''; ?>" required>
            
            <select name="categoria">
                <option value="">Selecione uma categoria</option>
                <?php 
                // Obter categoria atual da receita em edição
                $categoriaAtual = '';
                if ($edicaoAtiva) {
                    $stmt = $conn->prepare("SELECT c.nome FROM Categoria c 
                                           JOIN ReceitaCategoria rc ON c.id = rc.categoria_id 
                                           WHERE rc.receita_id = :id");
                    $stmt->execute([':id' => $receitaEditando['id']]);
                    $categoriaAtual = $stmt->fetchColumn();
                }
                
                foreach ($categorias as $cat): 
                    $selected = ($edicaoAtiva && $cat === $categoriaAtual) ? 'selected' : '';
                ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $selected; ?>>
                        <?php echo htmlspecialchars($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="chef" required>
                <option value="">Selecione o Chef</option>
                <?php foreach ($cozinheiros as $chef): 
                    $selected = ($edicaoAtiva && $chef['email'] === $receitaEditando['cozinheiro_email']) ? 'selected' : '';
                ?>
                    <option value="<?php echo htmlspecialchars($chef['email']); ?>" <?php echo $selected; ?>>
                        <?php echo htmlspecialchars($chef['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" name="<?php echo $edicaoAtiva ? 'editar' : 'add'; ?>">
                <?php echo $edicaoAtiva ? 'Salvar Edição' : 'Adicionar'; ?>
            </button>
            
            <?php if ($edicaoAtiva): ?>
                <a href="receitasADM.php" class="button cancelar-edicao">Cancelar</a>
            <?php endif; ?>
        </form>

        <div class="receitas-list">
            <h2>Receitas Cadastradas</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Data</th>
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
                        <td>
                            <a href="receitasADM.php?editar=<?php echo $receita['id']; ?>">Editar</a>
                            <a href="receitasADM.php?excluir=<?php echo $receita['id']; ?>" onclick="return confirm('Tem certeza que deseja excluir esta receita?')">Excluir</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>