<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../BancoDeDados/conexao.php';

// Verifica se o usuário está logado e se tem permissão de Administrador
if (!isset($_SESSION['id_login']) || $_SESSION['cargo'] !== 'administrador') {
    // $_SESSION['message'] = "Você não tem permissão para acessar esta área.";
    $_SESSION['message_type'] = "error";
    header("Location: ../LoginSenha/login.php");
    exit;
}

// Lógica de PESQUISA
$termo = $_GET['pesquisa'] ?? '';
$sql = "SELECT * FROM cargos WHERE nome LIKE ?";
$stmt = $conn->prepare($sql);
$stmt->execute(["%$termo%"]);
$cargos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../styles/cargosADM.css">
</head>
<body>
    <div class="container">
        <div class="menu">
            <h1>Código de Sabores</h1>
           <?php
function isActive($page) {
    return basename($_SERVER['PHP_SELF']) == $page ? 'active' : '';
}
?>

<nav>
    <a href="cargosADM.php" class="<?= isActive('cargosADM.php') ?>">Cargo</a>
    <a href="restauranteADM.php" class="<?= isActive('restauranteADM.php') ?>">Restaurantes</a>
    <a href="funcionarioADM.php" class="<?= isActive('funcionarioADM.php') ?>">Funcionário</a>
    <a href="referenciaADM.php" class="<?= isActive('referenciaADM.php') ?>">Referência</a>
</nav>
        </div>

        <?php
        // Exibir mensagens de feedback
        if (isset($_SESSION['message'])): ?>
            <div class="message-<?= $_SESSION['message_type'] ?? 'info' ?>">
                <?= htmlspecialchars($_SESSION['message']) ?>
            </div>
            <?php
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        endif;
        ?>

        <h2>Pesquisar Cargos</h2>
        <div class="search-bar">
            <form method="get" action="">
                <label for="pesquisa">Pesquisar por Nome:</label>
                <input type="text" id="pesquisa" name="pesquisa" placeholder="Pesquisar cargo..." value="<?= htmlspecialchars($termo) ?>">
                <button type="submit">Pesquisar</button>
                <?php if (!empty($termo)): ?>
                    <a href="cargosADM.php" class="clear-filters-button">Limpar Pesquisa</a>
                <?php endif; ?>
            </form>
        </div>

        <hr>

        <div class="add-button-container">
            <a href="adicionarCargo.php" class="add-button">Adicionar Novo Cargo</a>
        </div>

        <h2>Lista de Cargos</h2>
        <div class="lista-cargos">
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
                    <?php if (empty($cargos)): ?>
                        <tr><td colspan="4">Nenhum cargo encontrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($cargos as $cargo): ?>
                            <tr>
                             <td><?= htmlspecialchars($cargo['id_cargo']) ?></td>
<td><?= htmlspecialchars($cargo['nome']) ?></td>
<td><?= htmlspecialchars($cargo['descricao']) ?></td>
<td>
    <div class="action-buttons">
        <a href="editarCargo.php?id=<?= htmlspecialchars($cargo['id_cargo']) ?>" class="edit-button" title="Editar">
            <i class="fas fa-pencil-alt"></i>
        </a>
        <a href="/ADM/cargosAcoes/confirmarExclusaoCargo.php?id=<?= htmlspecialchars($cargo['id_cargo']) ?>" class="delete-button" title="Excluir">
            <i class="fas fa-trash"></i>
        </a>
    </div>
</td>




                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>