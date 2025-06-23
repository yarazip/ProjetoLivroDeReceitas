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
    <link rel="stylesheet" href="../styles/consultaCHEF.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon" />

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
                <a href="../../Receitas/listarReceitas.php">Página de Receitas</a>

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

            <!-- NÃO FUNCIONA!
        <label for="ineditas">
            <input type="checkbox" id="ineditas" name="ineditas" <?= $filtro_ineditas ? 'checked' : '' ?>> Receitas Inéditas
        </label> -->

            <button type="submit">Aplicar Filtros</button>
            <?php if ($termo || $filtro_categoria || $filtro_funcionario || $filtro_ineditas): ?>
                <a href="receitasChef.php" class="clear-filters-button">Limpar Filtros</a>
            <?php endif; ?>
        </form>


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
                    <th>Ingredientes</th>
                    <th>Descrição Geral</th>
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
                                            <?php foreach ($ingredientes_receita_info as $ing_info): ?>
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
                            <td><?= nl2br(htmlspecialchars($r['descricao'] ?? '')) ?></td>
                            <td>
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
                                <div class="action-buttons">

                                    <a href="receitasAcoes/consultar.php?nome=<?= htmlspecialchars(urlencode($r['nome_receita'] ?? '')) ?>" class="view-button" title="Consultar">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="receitasAcoes/editar.php?nome=<?= htmlspecialchars(urlencode($r['nome_receita'] ?? '')) ?>" class="edit-button" title="Editar">
                                        <i class="fas fa-pencil-alt"></i>
                                    </a>
                                    <a href="receitasAcoes/confirmarExclusaoReceita.php?nome=<?= htmlspecialchars(urlencode($r['nome_receita'] ?? '')) ?>" class="delete-button" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
    </div>
    </tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>

</body>

</html>