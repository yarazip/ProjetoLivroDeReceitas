<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../BancoDeDados/conexao.php';

// Verifica se o usuário está logado e se tem permissão de Administrador
if (!isset($_SESSION['id_login']) || $_SESSION['cargo'] !== 'administrador') {
    $_SESSION['message'] = "Você não tem permissão para acessar esta área.";
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
    <link rel="stylesheet" href="../../styles/func.css"> <link rel="stylesheet" href="../../styles/cargos.css"> <title>Gerenciar Cargos | ADM</title>
    <style>
        /* Estilos para mensagens */
        .message-success, .message-error {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
        }
        .message-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Estilos da tabela e filtros para melhor visualização */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; vertical-align: top; }
        th { background-color: #f2f2f2; }
        .search-bar { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; margin-bottom: 20px; }
        .search-bar label { font-weight: bold; }
        .search-bar input[type="text"] { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
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
                <a href="cargosADM.php">Cargo</a>
                <a href="restauranteADM.php">Restaurantes</a>
                <a href="funcionarioADM.php">Funcionário</a>
                <a href="referenciaADM.php">Referência</a>
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
                                    <a href="editarCargo.php?id=<?= htmlspecialchars($cargo['id_cargo']) ?>">Editar</a> |
                                 <a href="/ADM/cargosAcoes/confirmarExclusaoCargo.php?id=<?= htmlspecialchars($cargo['id_cargo']) ?>">Excluir</a>




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