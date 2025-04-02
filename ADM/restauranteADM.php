<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../conexao.php';

// Variáveis de controle
$edicaoAtiva = false;
$restauranteEditando = null;
$error = '';
$success = '';

// Processar operações
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Modo edição
    if (isset($_GET['editar'])) {
        $id = intval($_GET['editar']);
        $stmt = $conn->prepare("SELECT r.*, u.nome AS cozinheiro_nome 
                              FROM Restaurante r
                              JOIN Cozinheiro c ON r.cozinheiro_email = c.email
                              JOIN Usuario u ON c.email = u.email
                              WHERE r.id = :id");
        $stmt->execute([':id' => $id]);
        $restauranteEditando = $stmt->fetch(PDO::FETCH_ASSOC);
        $edicaoAtiva = ($restauranteEditando !== false);
    }
    
    // Exclusão de restaurante
    if (isset($_GET['excluir'])) {
        $id = intval($_GET['excluir']);
        try {
            $conn->beginTransaction();
            
            // Verificar se existem receitas associadas ao cozinheiro do restaurante
            $stmt = $conn->prepare("SELECT COUNT(*) FROM Receita 
                                  WHERE cozinheiro_email = 
                                  (SELECT cozinheiro_email FROM Restaurante WHERE id = :id)");
            $stmt->execute([':id' => $id]);
            $totalReceitas = $stmt->fetchColumn();
            
            if ($totalReceitas > 0) {
                throw new Exception("Não é possível excluir - cozinheiro tem receitas associadas");
            }
            
            // Excluir o restaurante
            $conn->prepare("DELETE FROM Restaurante WHERE id = :id")->execute([':id' => $id]);
            
            $conn->commit();
            $success = "Restaurante excluído com sucesso!";
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Erro ao excluir restaurante: " . $e->getMessage();
        }
    }
}

// Processar formulário (adição/edição)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $cnpj = trim($_POST['cnpj']);
    $endereco = trim($_POST['endereco']);
    $telefone = trim($_POST['telefone']);
    $cozinheiro_email = trim($_POST['cozinheiro_email']);
    
    try {
        $conn->beginTransaction();
        
        if (isset($_POST['editar_id'])) {
            // Modo edição
            $id = intval($_POST['editar_id']);
            $stmt = $conn->prepare("UPDATE Restaurante SET 
                                  nome = :nome, 
                                  cnpj = :cnpj, 
                                  endereco = :endereco, 
                                  telefone = :telefone,
                                  cozinheiro_email = :cozinheiro_email
                                  WHERE id = :id");
            $stmt->execute([
                ':nome' => $nome,
                ':cnpj' => $cnpj,
                ':endereco' => $endereco,
                ':telefone' => $telefone,
                ':cozinheiro_email' => $cozinheiro_email,
                ':id' => $id
            ]);
            
            $conn->commit();
            $success = "Restaurante atualizado com sucesso!";
            header("Location: restauranteADM.php?success=1");
            exit();
        } else {
            // Modo adição
            $stmt = $conn->prepare("INSERT INTO Restaurante 
                                  (nome, cnpj, endereco, telefone, cozinheiro_email, data_cadastro) 
                                  VALUES 
                                  (:nome, :cnpj, :endereco, :telefone, :cozinheiro_email, CURDATE())");
            $stmt->execute([
                ':nome' => $nome,
                ':cnpj' => $cnpj,
                ':endereco' => $endereco,
                ':telefone' => $telefone,
                ':cozinheiro_email' => $cozinheiro_email
            ]);
            
            $conn->commit();
            $success = "Restaurante adicionado com sucesso!";
            header("Location: restauranteADM.php?success=1");
            exit();
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Erro: " . $e->getMessage();
    }
}

// Obter dados para os selects
$cozinheiros = $conn->query("SELECT u.email, u.nome FROM Usuario u JOIN Cozinheiro c ON u.email = c.email")->fetchAll(PDO::FETCH_ASSOC);

// Obter restaurantes (com pesquisa se existir)
$searchTerm = $_GET['search'] ?? '';
$query = "SELECT r.*, u.nome AS cozinheiro_nome 
          FROM Restaurante r
          JOIN Cozinheiro c ON r.cozinheiro_email = c.email
          JOIN Usuario u ON c.email = u.email
          WHERE 1=1";
$params = [];

if (!empty($searchTerm)) {
    $query .= " AND (r.nome LIKE :search OR u.nome LIKE :search)";
    $params[':search'] = "%$searchTerm%";
}

$query .= " ORDER BY r.nome";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$restaurantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Para cada restaurante, obter suas receitas através do cozinheiro
foreach ($restaurantes as &$restaurante) {
    $stmt = $conn->prepare("SELECT id, nome FROM Receita 
                           WHERE cozinheiro_email = :email");
    $stmt->execute([':email' => $restaurante['cozinheiro_email']]);
    $restaurante['receitas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($restaurante); // Quebrar a referência
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/ADM.css">
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
    <title>Restaurantes Administração</title>
    <style>
        .cancelar-edicao {
            background-color: #f44336;
            margin-left: 10px;
        }
        .receitas-list {
            margin-top: 20px;
        }
        .receitas-list table {
            width: 100%;
            border-collapse: collapse;
        }
        .receitas-list th, .receitas-list td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .receitas-list th {
            background-color: #f2f2f2;
        }
        .receita-tag {
            display: inline-block;
            background: #e0e0e0;
            padding: 2px 6px;
            margin: 2px;
            border-radius: 3px;
            font-size: 0.9em;
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
                <a href="editorADM.php">Editor</a>
                <a href="desgustadorADM.php">Degustador</a>
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
            <input type="text" name="search" placeholder="Pesquisar Restaurante..." 
                   value="<?php echo htmlspecialchars($searchTerm); ?>">
            <button type="submit">Pesquisar</button>
            <?php if (!empty($searchTerm)): ?>
                <a href="restauranteADM.php" class="button">Limpar</a>
            <?php endif; ?>
        </form>

        <form method="POST" class="insert-bar">
            <?php if ($edicaoAtiva): ?>
                <input type="hidden" name="editar_id" value="<?php echo $restauranteEditando['id']; ?>">
                <h3>Editando Restaurante: <?php echo htmlspecialchars($restauranteEditando['nome']); ?></h3>
            <?php endif; ?>
            
            <input type="text" name="nome" placeholder="Nome do Restaurante" 
                   value="<?php echo $edicaoAtiva ? htmlspecialchars($restauranteEditando['nome']) : ''; ?>" required>
            
            <input type="text" name="cnpj" placeholder="CNPJ" 
                   value="<?php echo $edicaoAtiva ? htmlspecialchars($restauranteEditando['cnpj']) : ''; ?>" required
                   pattern="\d{2}\.\d{3}\.\d{3}/\d{4}-\d{2}" 
                   title="Formato: 99.999.999/9999-99">
            
            <input type="text" name="endereco" placeholder="Endereço" 
                   value="<?php echo $edicaoAtiva ? htmlspecialchars($restauranteEditando['endereco']) : ''; ?>">
            
            <input type="text" name="telefone" placeholder="Telefone" 
                   value="<?php echo $edicaoAtiva ? htmlspecialchars($restauranteEditando['telefone']) : ''; ?>"
                   pattern="\(?\d{2}\)?[\s-]?\d{4,5}[\s-]?\d{4}" 
                   title="Formato: (99) 99999-9999 ou 99 9999-9999">
            
            <select name="cozinheiro_email" required>
                <option value="">Selecione o Cozinheiro</option>
                <?php foreach ($cozinheiros as $cozinheiro): 
                    $selected = $edicaoAtiva && $cozinheiro['email'] === $restauranteEditando['cozinheiro_email'] ? 'selected' : '';
                ?>
                    <option value="<?php echo $cozinheiro['email']; ?>" <?php echo $selected; ?>>
                        <?php echo htmlspecialchars($cozinheiro['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit"><?php echo $edicaoAtiva ? 'Salvar Edição' : 'Adicionar'; ?></button>
            
            <?php if ($edicaoAtiva): ?>
                <a href="restauranteADM.php" class="button cancelar-edicao">Cancelar</a>
            <?php endif; ?>
        </form>

        <div class="receitas-list">
            <h2>Restaurantes Cadastrados</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>CNPJ</th>
                        <th>Cozinheiro</th>
                        <th>Receitas</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($restaurantes as $restaurante): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($restaurante['id']); ?></td>
                        <td><?php echo htmlspecialchars($restaurante['nome']); ?></td>
                        <td><?php echo htmlspecialchars($restaurante['cnpj']); ?></td>
                        <td><?php echo htmlspecialchars($restaurante['cozinheiro_nome']); ?></td>
                        <td>
                            <?php foreach ($restaurante['receitas'] as $receita): ?>
                                <span class="receita-tag"><?php echo htmlspecialchars($receita['nome']); ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <a href="restauranteADM.php?editar=<?php echo $restaurante['id']; ?>">Editar</a>
                            <a href="restauranteADM.php?excluir=<?php echo $restaurante['id']; ?>" 
                               onclick="return confirm('Tem certeza que deseja excluir este restaurante?')">Excluir</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>