<?php
session_start();
if (!isset($_SESSION['id_funcionario'])) {
    header("Location: ../LoginSenha/login.php");
    exit();
}
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
    <link rel="stylesheet" href="../styles/ingredientesCHEF.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">

</head>

<body>
        <a href="../LoginSenha/logout.php" class="logout-button">
        <i class="fa-solid fa-right-from-bracket fa-lg gray-icon"></i>
    </a>
    <div class="container">
        <div class="menu">
            <h1 class="logo">Código de Sabores</h1>
            <?php
            // Get the current page filename
            $current_page = basename($_SERVER['PHP_SELF']);
            ?>
            <nav>
                <a href="receitasChef.php" <?php if ($current_page == 'receitasChef.php') echo 'class="active"'; ?>>Receitas</a>
                <a href="ingredientesChef.php" <?php if ($current_page == 'ingredientesChef.php') echo 'class="active"'; ?>>Ingredientes</a>
                <a href="medidasChef.php" <?php if ($current_page == 'medidasChef.php') echo 'class="active"'; ?>>Medidas</a>
                <a href="categoriaChef.php" <?php if ($current_page == 'categoriaChef.php') echo 'class="active"'; ?>>Categorias</a>
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?= htmlspecialchars($_SESSION['nome_funcionario'] ?? 'Desconhecido') ?></span>
                </div>
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
                    <tr>
                        <td colspan="5">Nenhum ingrediente encontrado.</td>
                    </tr>
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
                            <td class="action-buttons">
                                <a href="editarIngrediente.php?id=<?= htmlspecialchars($ing['id_ingrediente']) ?>" class="edit-button" title="Editar">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <a href="ingredientesAcoes/confirmarExclusaoIngrediente.php?id=<?= htmlspecialchars($ing['id_ingrediente']) ?>" class="delete-button" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </a>
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