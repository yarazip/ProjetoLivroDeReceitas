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

// Lógica de PESQUISAR RESTAURANTE
$termo = $_GET['pesquisa'] ?? '';
$sql = "SELECT * FROM restaurantes WHERE nome LIKE ?";
$stmt = $conn->prepare($sql);
$stmt->execute(["%$termo%"]);
$restaurantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/restaurante.css"> 
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
    <title>Gerenciar Restaurantes | ADM</title>
  
</head>
<body>
    <div class="container">
        <div class="menu">
            <h1 class="logo">Código de Sabores</h1>
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

        <h2>Pesquisar Restaurantes</h2>
        <div class="search-bar">
            <form method="get">
                <label for="pesquisa">Pesquisar por Nome:</label>
                <input type="text" id="pesquisa" name="pesquisa" placeholder="Pesquisar Restaurante..." value="<?= htmlspecialchars($termo) ?>">
                <button type="submit">Pesquisar</button>
                <?php if (!empty($termo)): ?>
                    <a href="restauranteADM.php" class="clear-filters-button">Limpar Pesquisa</a>
                <?php endif; ?>
            </form>
        </div>

        <hr>

        <div class="add-button-container">
            <a href="adicionarRestaurante.php" class="add-button">Adicionar Novo Restaurante</a>
        </div>

        <h2>Lista de Restaurantes</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr>
                    <th>ID</th> <th>Nome</th>
                    <th>Contato</th>
                    <th>Telefone</th>
                    <th>Descrição</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($restaurantes)): ?>
                    <tr><td colspan="6">Nenhum restaurante encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($restaurantes as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['id_restaurante']) ?></td>
                            <td><?= htmlspecialchars($r['nome']) ?></td>
                            <td><?= htmlspecialchars($r['contato']) ?></td>
                            <td><?= htmlspecialchars($r['telefone']) ?></td>
                            <td><?= htmlspecialchars($r['descricao']) ?></td>
                            <td>
    <div class="action-buttons">
        <a href="editarRestaurante.php?id=<?= htmlspecialchars($r['id_restaurante']) ?>" class="edit-button" title="Editar">
            <i class="fas fa-pencil-alt"></i>
        </a>
        <a href="restauranteAcoes/confirmarExclusaoRestaurante.php?id=<?= htmlspecialchars($r['id_restaurante']) ?>" class="delete-button" title="Excluir">
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
</body>
</html>