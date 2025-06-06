<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../BancoDeDados/conexao.php';

// Verifica se o usuário está logado e se tem permissão (Cozinheiro ou Administrador)
if (!isset($_SESSION['id_login']) || ($_SESSION['cargo'] !== 'Cozinheiro' && $_SESSION['cargo'] !== 'Administrador')) {
    $_SESSION['message'] = "Você não tem permissão para acessar esta página.";
    $_SESSION['message_type'] = "error";
    header("Location: ../LoginSenha/login.php");
    exit;
}

// Lógica de PESQUISA
$termo = $_GET['pesquisa'] ?? '';
$sql = "SELECT * FROM categorias WHERE nome_categoria LIKE ? OR descricao LIKE ?";
$stmt = $conn->prepare($sql);
$stmt->execute(["%$termo%", "%$termo%"]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="../styles/func.css">
    <title>Gerenciar Categorias | Cozinheiro</title>
    <style>
        /* Seus estilos CSS */
        .message-success, .message-error { padding: 10px; margin-bottom: 20px; border-radius: 5px; font-weight: bold; }
        .message-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; vertical-align: top; }
        th { background-color: #f2f2f2; }

        .search-bar form { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; margin-bottom: 20px; }
        .search-bar label { font-weight: bold; }
        .search-bar input[type="text"] { flex-grow: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .search-bar button { padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .search-bar .clear-filters-button { margin-left: 10px; background-color: #dc3545; color: white; }

        .add-button-container { text-align: right; margin-bottom: 20px; }
        .add-button {
            padding: 10px 20px;
            background-color: #28a745; /* Verde para adicionar */
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1em;
            cursor: pointer;
        }
        .add-button:hover { opacity: 0.9; }
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
            <a href="categoriaChef.php">Categorias</a>
        </nav>
    </div>

    <?php
    if (isset($_SESSION['message'])): ?>
        <div class="message-<?= $_SESSION['message_type'] ?? 'info' ?>">
            <?= htmlspecialchars($_SESSION['message']) ?>
        </div>
        <?php
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    endif;
    ?>

    <h2>Pesquisar Categorias</h2>
    <div class="search-bar">
        <form method="get">
            <label for="pesquisa">Pesquisar categoria:</label>
            <input type="text" id="pesquisa" name="pesquisa" placeholder="Ex: Doces, Salgados" value="<?= htmlspecialchars($termo) ?>">
            <button type="submit">Pesquisar</button>
            <?php if (!empty($termo)): ?>
                <a href="categoriaChef.php" class="clear-filters-button"><button type="button">Limpar Pesquisa</button></a>
            <?php endif; ?>
        </form>
    </div>

    <hr>

    <div class="add-button-container">
        <a href="adicionarCategoria.php" class="add-button">Adicionar Nova Categoria</a>
    </div>

    <h2>Lista de Categorias</h2>
    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Descrição</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($categorias)): ?>
            <tr><td colspan="4">Nenhuma categoria encontrada.</td></tr>
        <?php else: ?>
            <?php foreach ($categorias as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['id_categoria']) ?></td>
                    <td><?= htmlspecialchars($c['nome_categoria']) ?></td>
                    <td><?= htmlspecialchars($c['descricao']) ?></td>
                    <td>
                        <a href="editarCategoria.php?id=<?= htmlspecialchars($c['id_categoria']) ?>">Editar</a> |
                        <a href="categoriaAcoes/confirmarExclusaoCategoria.php?id=<?= htmlspecialchars($c['id_categoria']) ?>">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php
$conn = null;
?>