<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../BancoDeDados/conexao.php';

// Pega o cargo da sessão, converte para minúsculas. Usa '' se não existir.
$cargo_usuario = strtolower($_SESSION['cargo'] ?? '');

// Lista de cargos permitidos nesta página
$cargos_permitidos = ['cozinheiro', 'cozinheira', 'administrador'];

// Verifica se o usuário está logado e se o cargo dele está na lista de permitidos
if (!isset($_SESSION['id_login']) || !in_array($cargo_usuario, $cargos_permitidos)) {
    $_SESSION['message'] = "Você não tem permissão para acessar esta página.";
    $_SESSION['message_type'] = "error";
    header("Location: ../LoginSenha/login.php");
    exit;
}

// Lógica de PESQUISA e BUSCA DE INGREDIENTES COM RECEITAS RELACIONADAS (VIA ID)
$termo = $_GET['pesquisa'] ?? '';
$sql = "
    SELECT
        i.id_ingrediente,
        i.nome AS nome_ingrediente,
        i.descricao AS descricao_ingrediente,
        GROUP_CONCAT(DISTINCT r.nome_receita ORDER BY r.nome_receita ASC SEPARATOR '|<br>|') AS receitas_associadas
    FROM
        ingredientes i
    LEFT JOIN
        receita_ingrediente ri ON i.id_ingrediente = ri.id_ingrediente
    LEFT JOIN
        receitas r ON ri.nome_receita = r.nome_receita
    WHERE
        i.nome LIKE ?
    GROUP BY
        i.id_ingrediente, i.nome, i.descricao
    ORDER BY
        i.nome ASC;
";
$stmt = $conn->prepare($sql);
$stmt->execute(["%$termo%"]);
$ingredientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Ingredientes | Cozinheiro</title>
    <link rel="stylesheet" href="../styles/func.css">
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
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

        .receitas-list { font-size: 0.9em; line-height: 1.4; }
        .receitas-list span { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .receitas-list span:hover { overflow: visible; white-space: normal; }
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

    <h2>Pesquisar Ingredientes</h2>
    <div class="search-bar">
        <form method="GET">
            <label for="pesquisa">Pesquisar ingrediente:</label>
            <input type="text" id="pesquisa" name="pesquisa" placeholder="Ex: Farinha de Trigo" value="<?= htmlspecialchars($termo) ?>">
            <button type="submit">Pesquisar</button>
            <?php if (!empty($termo)): ?>
                <a href="ingredientesChef.php" class="clear-filters-button"><button type="button">Limpar Pesquisa</button></a>
            <?php endif; ?>
        </form>
    </div>

    <hr>

    <div class="add-button-container">
        <a href="adicionarIngrediente.php" class="add-button">Adicionar Novo Ingrediente</a>
    </div>

    <h2>Lista de Ingredientes</h2>
    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Descrição</th>
                <th>Receitas que Utilizam</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($ingredientes)): ?>
            <tr><td colspan="5">Nenhum ingrediente encontrado.</td></tr>
        <?php else: ?>
            <?php foreach ($ingredientes as $ing): ?>
                <tr>
                    <td><?= htmlspecialchars($ing['id_ingrediente']) ?></td>
                    <td><?= htmlspecialchars($ing['nome_ingrediente']) ?></td>
                    <td><?= htmlspecialchars($ing['descricao_ingrediente'] ?? '') ?></td>
                    <td>
                        <?php
                        if ($ing['receitas_associadas']) {
                            $receitas_arr = explode('|<br>|', $ing['receitas_associadas']);
                            echo '<div class="receitas-list">';
                            foreach ($receitas_arr as $receita_nome) {
                                echo '<span><a href="receitasAcoes/consultar.php?nome=' . urlencode($receita_nome) . '">' . htmlspecialchars($receita_nome) . '</a></span>';
                            }
                            echo '</div>';
                        } else {
                            echo "Nenhuma";
                        }
                        ?>
                    </td>
                    <td>
                        <a href="editarIngrediente.php?id=<?= htmlspecialchars($ing['id_ingrediente']) ?>">Editar</a> |
                        <a href="ingredientesAcoes/confirmarExclusaoIngrediente.php?id=<?= htmlspecialchars($ing['id_ingrediente']) ?>">Excluir</a>
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