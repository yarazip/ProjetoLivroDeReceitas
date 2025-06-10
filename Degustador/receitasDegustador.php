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
    <link rel="stylesheet" href="../styles/receitaDEG.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
   
</head>
<body>
<div class="container">
    <div class="menu">
        <h1 class="logo">Código de Sabores</h1>
        <nav>
            <a href="receitasDegustador.php" class="active">Degustações</a>
        </nav>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
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
            <div class="button-group">
                <input type="text" id="pesquisa" name="pesquisa" placeholder="Ex: Bolo de Chocolate, João" value="<?= htmlspecialchars($termo) ?>">
                <button type="submit">Pesquisar</button>
                <?php if (!empty($termo)): ?>
                    <a href="receitasDegustador.php" class="clear-filters-button"><button type="button">Limpar Pesquisa</button></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <hr>

    <div class="add-button-container">
        <a href="adicionarDegustacao.php" class="add-button">Adicionar Nova Degustação</a>
    </div>

    <h2>Degustações Realizadas</h2>
    <table>
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
       <div class="action-buttons">
        <a href="editarDegustacao.php?id_funcionario=<?= htmlspecialchars($deg['id_funcionario']) ?>&nome_receita=<?= urlencode($deg['nome_receita']) ?>" class="edit-button" title="Editar">
            <i class="fas fa-pencil-alt"></i>
        </a>
        <a href="degustacaoAcoes/confirmarExclusaoDegustacao.php?id_funcionario=<?= htmlspecialchars($deg['id_funcionario']) ?>&nome_receita=<?= urlencode($deg['nome_receita']) ?>" class="delete-button" title="Excluir">
            <i class="fas fa-trash"></i>
        </a>
       </div>
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