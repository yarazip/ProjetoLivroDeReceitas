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

// Buscar categorias e funcionários para os selects de filtro
$categorias = $conn->query("SELECT id_categoria, nome_categoria FROM categorias")->fetchAll(PDO::FETCH_ASSOC);
$funcionarios_para_filtro = $conn->query("SELECT id_funcionario, nome FROM funcionarios")->fetchAll(PDO::FETCH_ASSOC);

// Lógica de PESQUISA e FILTROS
$termo = $_GET['pesquisa'] ?? '';
$filtro_categoria = $_GET['categoria'] ?? '';
$filtro_funcionario = $_GET['funcionario'] ?? '';
$filtro_ineditas = isset($_GET['ineditas']);

$sql = "
SELECT r.*, f.nome AS nome_funcionario, c.nome_categoria
FROM receitas r
JOIN funcionarios f ON r.id_funcionario = f.id_funcionario
JOIN categorias c ON r.id_categoria = c.id_categoria
WHERE r.nome_receita LIKE ?
";

$params = ["%$termo%"];

if (!empty($filtro_categoria)) {
    $sql .= " AND r.id_categoria = ?";
    $params[] = $filtro_categoria;
}

if (!empty($filtro_funcionario)) {
    $sql .= " AND r.id_funcionario = ?";
    $params[] = $filtro_funcionario;
}

if ($filtro_ineditas) {
    $sql .= " AND r.publicada = 0"; // Supondo uma coluna 'publicada'
}

$sql .= " ORDER BY r.data_criacao DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$receitas = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <title>Receitas do Cozinheiro</title>
    <link rel="stylesheet" href="../styles/func.css" />
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon" />
    <style>
        /* Seus estilos CSS */
        .message-success, .message-error { padding: 10px; margin-bottom: 20px; border-radius: 5px; font-weight: bold; }
        .message-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; vertical-align: top; } /* Adicionado vertical-align */
        th { background-color: #f2f2f2; }
        .filter-form { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; margin-bottom: 20px; }
        .filter-form label { font-weight: bold; }
        .filter-form input[type="text"], .filter-form select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .filter-form button { padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .filter-form .clear-filters-button { margin-left: 10px; background-color: #dc3545; color: white; }
        .foto-receita { max-width: 100px; height: auto; display: block; margin: 0 auto; }
        .add-recipe-button-container { text-align: right; margin-bottom: 20px; }
        .add-recipe-button {
            padding: 10px 20px;
            background-color: #28a745; /* Verde para adicionar */
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1em;
            cursor: pointer;
        }
        .add-recipe-button:hover { opacity: 0.9; }

        /* Estilos para a lista de ingredientes na tabela */
        .ingredientes-lista-tabela ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .ingredientes-lista-tabela li {
            margin-bottom: 5px;
        }
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

    <h2>Filtrar Receitas</h2>
    <form method="GET" class="filter-form">
        <label for="pesquisa">Pesquisar por Nome:</label>
        <input type="text" id="pesquisa" name="pesquisa" placeholder="Ex: Bolo de Chocolate" value="<?= htmlspecialchars($termo) ?>">

        <label for="categoria">Filtrar por Categoria:</label>
        <select id="categoria" name="categoria">
            <option value="">Todas as Categorias</option>
            <?php foreach ($categorias as $categoria): ?>
                <option value="<?= htmlspecialchars($categoria['id_categoria']) ?>"
                    <?= ($filtro_categoria == $categoria['id_categoria']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($categoria['nome_categoria']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="funcionario">Filtrar por Cozinheiro:</label>
        <select id="funcionario" name="funcionario">
            <option value="">Todos os Cozinheiros</option>
            <?php foreach ($funcionarios_para_filtro as $func): ?>
                <option value="<?= htmlspecialchars($func['id_funcionario']) ?>"
                    <?= ($filtro_funcionario == $func['id_funcionario']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($func['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="ineditas">
            <input type="checkbox" id="ineditas" name="ineditas" <?= $filtro_ineditas ? 'checked' : '' ?>> Receitas Inéditas
        </label>

        <button type="submit">Aplicar Filtros</button>
        <?php if($termo || $filtro_categoria || $filtro_funcionario || $filtro_ineditas): ?>
            <a href="receitasChef.php" class="clear-filters-button">Limpar Filtros</a>
        <?php endif; ?>
    </form>

    <hr>

    <div class="add-recipe-button-container">
        <a href="adicionarReceitas.php" class="add-recipe-button">Adicionar Nova Receita</a>
    </div>

    <h2>Receitas Cadastradas</h2>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>Cozinheiro</th>
                <th>Nome da Receita</th>
                <th>Data de Criação</th>
                <th>Categoria</th>
                <th>Porções</th>
                <th>Tempo de Preparo</th>
                <th>Dificuldade</th>
                <th>Ingredientes</th> <th>Descrição Geral</th>
                <th>Foto</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($receitas)): ?>
            <tr>
                <td colspan="11">Nenhuma receita encontrada com os filtros aplicados.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($receitas as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['nome_funcionario']) ?></td>
                    <td><?= htmlspecialchars($r['nome_receita']) ?></td>
                    <td><?= htmlspecialchars($r['data_criacao']) ?></td>
                    <td><?= htmlspecialchars($r['nome_categoria']) ?></td>
                    <td><?= htmlspecialchars($r['porcoes']) ?></td>
                    <td><?= htmlspecialchars($r['tempo_preparo']) ?></td>
                    <td><?= htmlspecialchars($r['dificuldade']) ?></td>
                    <td>
                        <?php
                        // Buscar ingredientes da receita atual (QUERY N+1 AQUI, OTIMIZAR SE NECESSÁRIO)
                        $stmtIng = $conn->prepare("SELECT ri.quantidade_ingrediente, i.nome, m.descricao AS medida_descricao, m.medida, ri.descricao AS item_descricao
                                                 FROM receita_ingrediente ri
                                                 JOIN ingredientes i ON ri.id_ingrediente = i.id_ingrediente
                                                 JOIN medidas m ON ri.id_medida = m.id_medida
                                                 WHERE ri.nome_receita = ?");
                        $stmtIng->execute([$r['nome_receita']]);
                        $ingredientes_receita_info = $stmtIng->fetchAll(PDO::FETCH_ASSOC);

                        if (!empty($ingredientes_receita_info)): ?>
                            <div class="ingredientes-lista-tabela">
                                <ul>
                                <?php foreach($ingredientes_receita_info as $ing_info): ?>
                                    <li>
                                        <?= htmlspecialchars($ing_info['quantidade_ingrediente']) ?>
                                        <?= htmlspecialchars($ing_info['medida_descricao']) ?>
                                        (<?= htmlspecialchars($ing_info['medida']) ?>) de
                                        <strong><?= htmlspecialchars($ing_info['nome']) ?></strong>
                                        <?php if (!empty($ing_info['item_descricao'])): ?>
                                            (<?= htmlspecialchars($ing_info['item_descricao']) ?>)
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php else: ?>
                            Nenhum ingrediente detalhado.
                        <?php endif; ?>
                    </td>
                    <td><?= nl2br(htmlspecialchars($r['descricao'] ?? '')) ?></td> <td>
                        <?php
                        $stmtFoto = $conn->prepare("SELECT tipo FROM foto_receita WHERE nome_receita = ? LIMIT 1");
                        $stmtFoto->execute([$r['nome_receita']]);
                        $foto = $stmtFoto->fetch(PDO::FETCH_ASSOC);
                        if ($foto && $foto['tipo']): ?>
                            <img src="data:image/jpeg;base64,<?= base64_encode($foto['tipo']) ?>" alt="Foto da Receita" class="foto-receita" />
                        <?php else: ?>
                            Sem foto
                        <?php endif; ?>
                    </td>
     <td>
    <a href="receitasAcoes/consultar.php?nome=<?= urlencode($r['nome_receita']) ?>">Consultar</a> |
    <a href="adicionarReceitas.php?nome=<?= urlencode($r['nome_receita']) ?>">Adicionar</a> |
    <a href="receitasAcoes/editar.php?nome=<?= urlencode($r['nome_receita']) ?>">Editar</a> |
    <a href="receitasAcoes/confirmarExclusaoReceita.php?nome=<?= urlencode($r['nome_receita']) ?>">Excluir</a>
</td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>