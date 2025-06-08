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

// Lógica de FILTRO
$termo_pesquisa = $_GET['pesquisa'] ?? '';
$filtro_funcionario = $_GET['funcionario'] ?? '';
$filtro_restaurante = $_GET['restaurante'] ?? '';
$filtro_data_inicio = $_GET['data_inicio'] ?? '';
$filtro_data_fim = $_GET['data_fim'] ?? '';


// Busque funcionários e restaurantes para popular os selects de filtro
$funcionarios_para_filtro = $conn->query("SELECT id_funcionario, nome FROM funcionarios ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$restaurantes_para_filtro = $conn->query("SELECT id_restaurante, nome FROM restaurantes ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);


// Lógica de LISTAR HISTÓRICOS com filtros
$sql = "SELECT hr.*, f.nome AS nome_funcionario, r.nome AS nome_restaurante
        FROM historico_restaurante hr
        JOIN funcionarios f ON hr.id_funcionario = f.id_funcionario
        JOIN restaurantes r ON hr.id_restaurante = r.id_restaurante
        WHERE 1=1"; // Cláusula base para facilitar a adição de condições

$params = [];

if (!empty($termo_pesquisa)) {
    $sql .= " AND (f.nome LIKE ? OR r.nome LIKE ? OR hr.descricao LIKE ?)";
    $params[] = "%$termo_pesquisa%";
    $params[] = "%$termo_pesquisa%";
    $params[] = "%$termo_pesquisa%";
}

if (!empty($filtro_funcionario)) {
    $sql .= " AND hr.id_funcionario = ?";
    $params[] = $filtro_funcionario;
}

if (!empty($filtro_restaurante)) {
    $sql .= " AND hr.id_restaurante = ?";
    $params[] = $filtro_restaurante;
}

if (!empty($filtro_data_inicio)) {
    $sql .= " AND hr.data_inicio >= ?";
    $params[] = $filtro_data_inicio;
}

if (!empty($filtro_data_fim)) {
    $sql .= " AND hr.data_fim <= ?";
    $params[] = $filtro_data_fim;
}

$sql .= " ORDER BY hr.data_inicio DESC"; // Ordena por data mais recente

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$historicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Referências | ADM</title>
    <link rel="stylesheet" href="../../styles/func.css">
    <link rel="stylesheet" href="../styles/referencia.css">
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
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

        /* Estilos da tabela e botões */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; vertical-align: top; }
        th { background-color: #f2f2f2; }
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

        /* Estilos para a barra de pesquisa/filtro */
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: flex-end; /* Alinha os itens na parte inferior */
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            background-color: #fcfcfc;
        }
        .filter-form label {
            font-weight: bold;
            margin-bottom: 5px;
            width: 100%; /* Faz o label ocupar a largura total antes do input */
        }
        .filter-form input[type="text"],
        .filter-form input[type="date"],
        .filter-form select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            flex-grow: 1; /* Permite que os campos cresçam */
            min-width: 150px; /* Garante um tamanho mínimo */
        }
        .filter-form button {
            padding: 8px 15px;
            background-color: #007bff; /* Azul para aplicar filtros */
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            min-width: 100px;
        }
        .filter-form .clear-filters-button {
            background-color: #dc3545; /* Vermelho para limpar */
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            min-width: 180px; /* Ajusta largura mínima para os grupos de filtro */
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

        <h2>Filtrar Histórico de Referências</h2>
        <div class="filter-form">
            <form method="GET">
                <div class="filter-group">
                    <label for="pesquisa">Pesquisa Geral:</label>
                    <input type="text" id="pesquisa" name="pesquisa" placeholder="Nome funcionário, restaurante ou descrição" value="<?= htmlspecialchars($termo_pesquisa) ?>">
                </div>

                <div class="filter-group">
                    <label for="funcionario">Funcionário:</label>
                    <select id="funcionario" name="funcionario">
                        <option value="">Todos os Funcionários</option>
                        <?php foreach ($funcionarios_para_filtro as $func): ?>
                            <option value="<?= htmlspecialchars($func['id_funcionario']) ?>"
                                <?= ($filtro_funcionario == $func['id_funcionario']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($func['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="restaurante">Restaurante:</label>
                    <select id="restaurante" name="restaurante">
                        <option value="">Todos os Restaurantes</option>
                        <?php foreach ($restaurantes_para_filtro as $rest): ?>
                            <option value="<?= htmlspecialchars($rest['id_restaurante']) ?>"
                                <?= ($filtro_restaurante == $rest['id_restaurante']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rest['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="data_inicio">Data Início (a partir de):</label>
                    <input type="date" id="data_inicio" name="data_inicio" value="<?= htmlspecialchars($filtro_data_inicio) ?>">
                </div>

                <div class="filter-group">
                    <label for="data_fim">Data Fim (até):</label>
                    <input type="date" id="data_fim" name="data_fim" value="<?= htmlspecialchars($filtro_data_fim) ?>">
                </div>
                
                <button type="submit">Aplicar Filtros</button>
                <?php if (!empty($termo_pesquisa) || !empty($filtro_funcionario) || !empty($filtro_restaurante) || !empty($filtro_data_inicio) || !empty($filtro_data_fim)): ?>
                    <a href="referenciaADM.php" class="clear-filters-button"><button type="button">Limpar Filtros</button></a>
                <?php endif; ?>
            </form>
        </div>

        <hr>

        <div class="add-button-container">
            <a href="adicionarReferencia.php" class="add-button">Adicionar Nova Referência</a>
        </div>

        <h2>Lista de Históricos de Referências</h2>

        <table border="1" cellpadding="5">
            <thead>
                <tr>
                    <th>Funcionário</th>
                    <th>Restaurante</th>
                    <th>Data Início</th>
                    <th>Data Fim</th>
                    <th>Descrição</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($historicos)): ?>
                    <tr><td colspan="6">Nenhum histórico encontrado com os filtros aplicados.</td></tr>
                <?php else: ?>
                    <?php foreach ($historicos as $h): ?>
                        <tr>
                            <td><?= htmlspecialchars($h['nome_funcionario']) ?> (ID: <?= htmlspecialchars($h['id_funcionario']) ?>)</td>
                            <td><?= htmlspecialchars($h['nome_restaurante']) ?> (ID: <?= htmlspecialchars($h['id_restaurante']) ?>)</td>
                            <td><?= htmlspecialchars($h['data_inicio']) ?></td>
                            <td><?= htmlspecialchars($h['data_fim'] ?? 'Atual') ?></td>
                            <td><?= htmlspecialchars($h['descricao'] ?? 'N/A') ?></td>
                            <td>
                                <a href="editarReferencia.php?id_func=<?= htmlspecialchars($h['id_funcionario']) ?>&id_rest=<?= htmlspecialchars($h['id_restaurante']) ?>">Editar</a> |
                                <a href="referenciaAcoes/confirmarExclusaoReferencia.php?id_func=<?= htmlspecialchars($h['id_funcionario']) ?>&id_rest=<?= htmlspecialchars($h['id_restaurante']) ?>">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>