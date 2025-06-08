<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../BancoDeDados/conexao.php';


// Pega o cargo da sessão e converte para minúsculas. Usa '' se não existir.
$cargo_usuario = strtolower($_SESSION['cargo'] ?? '');

// Lista de cargos permitidos para esta página
$cargos_permitidos = ['degustador', 'degustadora', 'administrador'];

// Verifica se o usuário está logado e se o cargo dele está na lista de permitidos
if (!isset($_SESSION['id_login']) || !in_array($cargo_usuario, $cargos_permitidos)) {
    // Define a mensagem de erro antes de redirecionar
    $_SESSION['message'] = "Você não tem permissão para acessar esta página.";
    $_SESSION['message_type'] = "error";
    header("Location: ../LoginSenha/login.php");
    exit; // Para a execução do script imediatamente
}

// Lógica de PESQUISA
$termo = $_GET['pesquisa'] ?? '';
$sql = "SELECT d.*, f.nome AS nome_funcionario, r.nome_receita AS nome_receita_completo
        FROM degustacoes d
        JOIN funcionarios f ON d.id_funcionario = f.id_funcionario
        JOIN receitas r ON d.nome_receita = r.nome_receita
        WHERE d.nome_receita LIKE ? OR f.nome LIKE ?
        ORDER BY d.data_degustacao DESC";
$stmt = $conn->prepare($sql);
$stmt->execute(["%$termo%", "%$termo%"]); // Pesquisa por nome da receita ou nome do funcionário
$degustacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Degustações | Degustador</title>
    <link rel="stylesheet" href="../styles/func.css">
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
    <style>
        /* Seus estilos CSS */
        .message-success, .message-error { padding: 10px; margin-bottom: 20px; border-radius: 5px; font-weight: bold; }
        .message-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6fb; }
        
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
            <a href="receitasDegustador.php">Degustações</a>
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

    <h2>Pesquisar Degustações</h2>
    <div class="search-bar">
        <form method="GET">
            <label for="pesquisa">Pesquisar por Receita ou Avaliador:</label>
            <input type="text" id="pesquisa" name="pesquisa" placeholder="Ex: Bolo de Chocolate, João" value="<?= htmlspecialchars($termo) ?>">
            <button type="submit">Pesquisar</button>
            <?php if (!empty($termo)): ?>
                <a href="receitasDegustador.php" class="clear-filters-button"><button type="button">Limpar Pesquisa</button></a>
            <?php endif; ?>
        </form>
    </div>

    <hr>

    <div class="add-button-container">
        <a href="adicionarDegustacao.php" class="add-button">Adicionar Nova Degustação</a>
    </div>

    <h2>Degustações Realizadas</h2>
    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>Funcionário</th>
                <th>Receita</th>
                <th>Data</th>
                <th>Nota</th>
                <th>Descrição</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($degustacoes)): ?>
            <tr><td colspan="6">Nenhuma degustação encontrada.</td></tr>
        <?php else: ?>
            <?php foreach ($degustacoes as $deg): ?>
                <tr>
                    <td><?= htmlspecialchars($deg['nome_funcionario'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($deg['nome_receita_completo'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($deg['data_degustacao'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($deg['nota'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($deg['descricao'] ?? 'N/A') ?></td>
                    <td>
                        <a href="editarDegustacao.php?id_funcionario=<?= htmlspecialchars($deg['id_funcionario']) ?>&nome_receita=<?= urlencode($deg['nome_receita']) ?>">Editar</a> |
                        
                        <a href="degustacaoAcoes/confirmarExclusaoDegustacao.php?id_funcionario=<?= htmlspecialchars($deg['id_funcionario']) ?>&nome_receita=<?= urlencode($deg['nome_receita']) ?>">Excluir</a>
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