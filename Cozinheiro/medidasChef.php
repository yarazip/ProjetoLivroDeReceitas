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

// Lógica de PESQUISA
$termo = $_GET['pesquisa'] ?? '';
$sql = "SELECT * FROM medidas WHERE descricao LIKE ? OR medida LIKE ?"; // Pesquisa por descrição ou medida
$stmt = $conn->prepare($sql);
$stmt->execute(["%$termo%", "%$termo%"]);
$medidas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Gerenciar Medidas | Cozinheiro</title>
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="../styles/medidaCHEF.css">

</head>

<body>
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

        <h2>Pesquisar Medidas</h2>
        <div class="search-bar">
            <form method="GET">
                <label for="pesquisa">Pesquisar medida:</label>
                <input type="text" id="pesquisa" name="pesquisa" placeholder="Ex: Gramas, g" value="<?= htmlspecialchars($termo) ?>">
                <button type="submit">Pesquisar</button>
                <?php if (!empty($termo)): ?>
                    <a href="medidasChef.php" class="clear-filters-button"><button type="button">Limpar Pesquisa</button></a>
                <?php endif; ?>
            </form>
        </div>

        <hr>

        <div class="add-button-container">
            <a href="adicionarMedida.php" class="add-button">Adicionar Nova Medida</a>
        </div>

        <h2>Lista de Medidas</h2>
        <table border="1" cellpadding="10" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Descrição</th>
                    <th>Medida (símbolo)</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($medidas)): ?>
                    <tr>
                        <td colspan="4">Nenhuma medida encontrada.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($medidas as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['id_medida']) ?></td>
                            <td><?= htmlspecialchars($m['descricao']) ?></td>
                            <td><?= htmlspecialchars($m['medida']) ?></td>
                            <td class="action-buttons">
                                <a href="editarMedida.php?id=<?= htmlspecialchars($m['id_medida']) ?>" class="edit-button" title="Editar">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <a href="medidasAcoes/confirmarExclusaoMedida.php?id=<?= htmlspecialchars($m['id_medida']) ?>" class="delete-button" title="Excluir">
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