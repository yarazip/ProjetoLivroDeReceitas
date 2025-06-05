<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Caminho ajustado para a conexão com o banco de dados
require_once '../BancoDeDados/conexao.php';

// Verifica se o usuário está logado e se tem permissão (Cozinheiro ou Administrador)
if (!isset($_SESSION['id_login']) || ($_SESSION['cargo'] !== 'Cozinheiro' && $_SESSION['cargo'] !== 'Administrador')) {
    $_SESSION['message'] = "Você não tem permissão para acessar esta página.";
    $_SESSION['message_type'] = "error";
    header("Location: ../LoginSenha/login.php");
    exit;
}

// Lógica de INSERIR INGREDIENTE
if (isset($_POST['adicionar'])) {
    $nome_ingrediente = $_POST['nome_ingrediente'];
    $descricao = $_POST['descricao'];

    $conn->beginTransaction();
    try {
        $sql = "INSERT INTO ingredientes (nome, descricao) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nome_ingrediente, $descricao]);
        $conn->commit();
        $_SESSION['message'] = "Ingrediente '" . htmlspecialchars($nome_ingrediente) . "' adicionado com sucesso!";
        $_SESSION['message_type'] = "success";
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['message'] = "Erro ao adicionar ingrediente: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        error_log("Erro ao adicionar ingrediente: " . $e->getMessage());
    }
    header("Location: ingredientesChef.php");
    exit;
}

// Lógica para CARREGAR DADOS PARA EDIÇÃO
$editando = false;
$ingrediente_editar = null;
if (isset($_GET['editar'])) {
    $id = $_GET['editar'];
    $stmt = $conn->prepare("SELECT * FROM ingredientes WHERE id_ingrediente = ?");
    $stmt->execute([$id]);
    $ingrediente_editar = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($ingrediente_editar) {
        $editando = true;
    } else {
        $_SESSION['message'] = "Ingrediente não encontrado para edição.";
        $_SESSION['message_type'] = "error";
        header("Location: ingredientesChef.php");
        exit;
    }
}

// Lógica para SALVAR EDIÇÃO
if (isset($_POST['salvar_edicao'])) { // Mudança de nome para evitar conflito com 'editar' do GET
    $id_ingrediente = $_POST['id_ingrediente'];
    $nome_ingrediente = $_POST['nome_ingrediente'];
    $descricao = $_POST['descricao'];

    $conn->beginTransaction();
    try {
        $sql = "UPDATE ingredientes SET nome = ?, descricao = ? WHERE id_ingrediente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nome_ingrediente, $descricao, $id_ingrediente]);
        $conn->commit();
        $_SESSION['message'] = "Ingrediente '" . htmlspecialchars($nome_ingrediente) . "' atualizado com sucesso!";
        $_SESSION['message_type'] = "success";
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['message'] = "Erro ao atualizar ingrediente: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        error_log("Erro ao atualizar ingrediente: " . $e->getMessage());
    }
    header("Location: ingredientesChef.php");
    exit;
}

// Lógica de EXCLUIR INGREDIENTE (com confirmação e tratamento de dependências)
if (isset($_GET['excluir'])) {
    $id_ingrediente_para_excluir = $_GET['excluir'];
    $confirmacao = $_GET['confirmar'] ?? null;

    if ($confirmacao === 'sim') {
        $conn->beginTransaction();
        try {
            // Verificar dependências na tabela 'receita_ingrediente'
            $stmt_check_deps = $conn->prepare("SELECT COUNT(*) FROM receita_ingrediente WHERE id_ingrediente = ?");
            $stmt_check_deps->execute([$id_ingrediente_para_excluir]);
            $num_dependencias = $stmt_check_deps->fetchColumn();

            if ($num_dependencias > 0) {
                $conn->rollBack();
                $_SESSION['message'] = "Não foi possível excluir o ingrediente. Ele está sendo usado em " . $num_dependencias . " receita(s).";
                $_SESSION['message_type'] = "error";
            } else {
                $sql = "DELETE FROM ingredientes WHERE id_ingrediente = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$id_ingrediente_para_excluir]);
                $conn->commit();
                $_SESSION['message'] = "Ingrediente excluído com sucesso!";
                $_SESSION['message_type'] = "success";
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $_SESSION['message'] = "Erro ao excluir ingrediente: " . $e->getMessage();
            $_SESSION['message_type'] = "error";
            error_log("Erro ao excluir ingrediente: " . $e->getMessage());
        }
        header("Location: ingredientesChef.php");
        exit;
    } else {
        $stmt_nome_ingrediente = $conn->prepare("SELECT nome FROM ingredientes WHERE id_ingrediente = ?");
        $stmt_nome_ingrediente->execute([$id_ingrediente_para_excluir]);
        $nome_ingrediente_excluir = $stmt_nome_ingrediente->fetchColumn();
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>Confirmar Exclusão</title>
            <link rel="stylesheet" href="../styles/func.css">
            <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
        </head>
        <body>
            <script>
                function confirmarExclusao() {
                    var confirmar = confirm("Tem certeza que deseja excluir o ingrediente '<?= htmlspecialchars($nome_ingrediente_excluir) ?>'? Esta ação é irreversível e só será possível se o ingrediente não estiver em nenhuma receita.");
                    if (confirmar) {
                        window.location.href = "ingredientesChef.php?excluir=<?= urlencode($id_ingrediente_para_excluir) ?>&confirmar=sim";
                    } else {
                        window.location.href = "ingredientesChef.php";
                    }
                }
                confirmarExclusao();
            </script>
        </body>
        </html>
        <?php
        exit;
    }
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
    <link rel="stylesheet" href="../styles/func.css">
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
    <style>
        /* Seus estilos CSS */
        .insert-bar form { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .insert-bar input[type="text"] { flex-grow: 1; min-width: 150px; }
        .insert-bar button { padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; background-color: #4CAF50; color: white; }
        .insert-bar a button { background-color: #f44336; }
        .message-success, .message-error { padding: 10px; margin-bottom: 20px; border-radius: 5px; font-weight: bold; }
        .message-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
        /* Novos estilos para a coluna de receitas */
        .receitas-list { font-size: 0.9em; line-height: 1.4; }
        .receitas-list span { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .receitas-list span:hover { overflow: visible; white-space: normal; }
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

    <h2>Pesquisar Ingredientes</h2>
    <div class="search-bar">
        <form method="GET">
            <input type="text" name="pesquisa" placeholder="Pesquisar ingrediente..." value="<?= htmlspecialchars($termo) ?>">
            <button type="submit">Pesquisar</button>
            <?php if (!empty($termo)): ?>
                <a href="ingredientesChef.php" class="clear-filters-button"><button type="button">Limpar Pesquisa</button></a>
            <?php endif; ?>
        </form>
    </div>

    <hr>

    <h2><?= $editando ? 'Editar Ingrediente' : 'Adicionar Novo Ingrediente' ?></h2>
    <div class="insert-bar">
        <form method="POST">
            <input type="hidden" name="<?= $editando ? 'salvar_edicao' : 'adicionar' ?>" value="1">
            <?php if ($editando): ?>
                <input type="hidden" name="id_ingrediente" value="<?= htmlspecialchars($ingrediente_editar['id_ingrediente']) ?>">
            <?php endif; ?>
            <label for="nome_ingrediente" class="sr-only">Nome:</label>
            <input type="text" id="nome_ingrediente" name="nome_ingrediente" placeholder="Nome do ingrediente" required value="<?= htmlspecialchars($ingrediente_editar['nome'] ?? '') ?>">
            <label for="descricao_ing" class="sr-only">Descrição:</label>
            <input type="text" id="descricao_ing" name="descricao" placeholder="Descrição (opcional)" value="<?= htmlspecialchars($ingrediente_editar['descricao'] ?? '') ?>">
            <button type="submit"><?= $editando ? 'Salvar Edição' : 'Adicionar Ingrediente' ?></button>
            <?php if ($editando): ?>
                <a href="ingredientesChef.php"><button type="button">Cancelar Edição</button></a>
            <?php endif; ?>
        </form>
    </div>

    <hr>

    <h2>Lista de Ingredientes</h2>
    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Descrição</th>
                <th>Receitas que Utilizam</th> <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($ingredientes)): ?>
            <tr><td colspan="5">Nenhum ingrediente encontrado.</td></tr> <?php else: ?>
            <?php foreach ($ingredientes as $ing): ?>
                <tr>
                    <td><?= htmlspecialchars($ing['id_ingrediente']) ?></td>
                    <td><?= htmlspecialchars($ing['nome_ingrediente']) ?></td>
                    <td><?= htmlspecialchars($ing['descricao_ingrediente']) ?></td>
                    <td>
                        <?php
                        if ($ing['receitas_associadas']) {
                            // Divide a string de receitas por '|<br>|' e exibe como links
                            $receitas_arr = explode('|<br>|', $ing['receitas_associadas']);
                            echo '<div class="receitas-list">';
                            foreach ($receitas_arr as $receita_nome) {
                                // Link para a página de consulta da receita (se você tiver uma)
                                // Assumindo que receitasChef.php ou uma subpágina tem um link para consultar
                                echo '<span><a href="receitasAcoes/consultar.php?nome=' . urlencode($receita_nome) . '">' . htmlspecialchars($receita_nome) . '</a></span>';
                            }
                            echo '</div>';
                        } else {
                            echo "Nenhuma";
                        }
                        ?>
                    </td>
                    <td>
                        <a href="?editar=<?= htmlspecialchars($ing['id_ingrediente']) ?>"><button>Editar</button></a>
                        <a href="?excluir=<?= htmlspecialchars($ing['id_ingrediente']) ?>">
                            <button>Excluir</button>
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
$conn = null; // Fechar a conexão
?>