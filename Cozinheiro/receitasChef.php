<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../BancoDeDados/conexao.php'; // Caminho ajustado


// Buscar categorias e medidas para o formulário de ADIÇÃO e selects de filtro
$categorias = $conn->query("SELECT id_categoria, nome_categoria FROM categorias")->fetchAll(PDO::FETCH_ASSOC);
// Continuamos buscando medidas, pois elas ainda são relevantes para as "porções" e "modo de preparo"
$medidas = $conn->query("SELECT id_medida, descricao, medida FROM medidas")->fetchAll(PDO::FETCH_ASSOC);
$funcionarios_para_filtro = $conn->query("SELECT id_funcionario, nome FROM funcionarios")->fetchAll(PDO::FETCH_ASSOC); // Para o filtro

// Lógica de INSERIR nova receita
if (isset($_POST['adicionar'])) {
    $nome = $_POST['nome_receita'];
    $data = $_POST['data_criacao'];
    $id_categoria = $_POST['id_categoria'];
    $modo_preparo = $_POST['modo_preparo'];
    $porcoes = $_POST['porcoes'];
    $tempo_preparo = $_POST['tempo_preparo'];
    $dificuldade = $_POST['dificuldade'];
    $descricao = $_POST['descricao'];
    $ingredientes_texto = $_POST['ingredientes_texto']; // NOVO CAMPO
    $id_funcionario = $_SESSION['id_funcionario'] ?? null; // Assume que id_funcionario está na sessão

    if (!$id_funcionario) {
        $_SESSION['message'] = "Erro: Funcionário não logado para adicionar receita.";
        $_SESSION['message_type'] = "error";
        header("Location: receitasChef.php");
        exit;
    }

    $conn->beginTransaction();

    try {
        // Inserir receita
        // ATENÇÃO: Se você moveu 'ingredientes' para um campo de texto livre,
        // a tabela 'receitas' precisará de uma nova coluna para armazenar esse texto.
        // Por exemplo, uma coluna `ingredientes_lista_texto` VARCHAR(1000) ou TEXT.
        // Vou assumir que você adicionará essa coluna na tabela `receitas`.
        // SE VOCÊ AINDA QUISER MANTER A RELAÇÃO N:N DE INGREDIENTES, ME AVISE.
        // Por enquanto, o campo 'ingredientes_texto' será armazenado na coluna 'descricao'
        // da tabela 'receitas' para demonstração. Mas o ideal é criar uma coluna nova.
        // Ajuste a query INSERT abaixo para sua coluna correta, se criar uma nova.
        
        $sql = "INSERT INTO receitas (nome_receita, data_criacao, id_categoria, modo_preparo, porcoes, tempo_preparo, dificuldade, descricao, id_funcionario)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        // Passe $ingredientes_texto para a coluna 'descricao' por enquanto (para teste)
        // O ideal é ter uma coluna específica para os ingredientes em texto livre.
        $stmt->execute([$nome, $data, $id_categoria, $modo_preparo, $porcoes, $tempo_preparo, $dificuldade, $ingredientes_texto, $id_funcionario]);


        // REMOVENDO A LÓGICA DE INSERIR INGREDIENTES DA RECEITA N:N (receita_ingrediente)
        // Pois agora os ingredientes são um campo de texto livre.
        // if (!empty($_POST['ingredientes'])) { ... }


        // Inserir foto receita (se houver)
        if (isset($_FILES['foto_receita']) && $_FILES['foto_receita']['error'] === UPLOAD_ERR_OK && !empty($_FILES['foto_receita']['tmp_name'])) {
            $foto = file_get_contents($_FILES['foto_receita']['tmp_name']);
            $sqlFoto = "INSERT INTO foto_receita (tipo, data_upload, id_funcionario, nome_receita) VALUES (?, NOW(), ?, ?)";
            $stmtFoto = $conn->prepare($sqlFoto);
            $stmtFoto->execute([$foto, $id_funcionario, $nome]);
        }

        $conn->commit();
        $_SESSION['message'] = "Receita '" . htmlspecialchars($nome) . "' adicionada com sucesso!";
        $_SESSION['message_type'] = "success";
        header("Location: receitasChef.php");
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Erro ao adicionar receita: " . $e->getMessage());
        $_SESSION['message'] = "Erro ao adicionar receita: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: receitasChef.php");
        exit;
    }
}


// Lógica de PESQUISA e FILTROS (mantida)
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

    <h2>Adicionar Nova Receita</h2>
    <div class="insert-bar">
        <form method="POST" enctype="multipart/form-data">
            <p><strong>Cozinheiro logado:</strong> <?= htmlspecialchars($_SESSION['nome_funcionario'] ?? 'Desconhecido') ?></p>

            <fieldset>
                <legend>Informações Básicas da Receita</legend>
                <label for="nome_receita">Nome da Receita:</label>
                <input type="text" id="nome_receita" name="nome_receita" placeholder="Ex: Lasanha à Bolonhesa" required>

                <label for="data_criacao">Data de Criação:</label>
                <input type="date" id="data_criacao" name="data_criacao" required value="<?= date('Y-m-d') ?>">

                <label for="id_categoria">Categoria:</label>
                <select id="id_categoria" name="id_categoria" required>
                    <option value="">Selecione a Categoria</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= htmlspecialchars($categoria['id_categoria']) ?>">
                            <?= htmlspecialchars($categoria['nome_categoria']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="porcoes">Número de Porções:</label>
                <input type="number" id="porcoes" name="porcoes" placeholder="Ex: 4" min="1" required>

                <label for="tempo_preparo">Tempo de Preparo (Ex: 30 min, 1h 15min):</label>
                <input type="text" id="tempo_preparo" name="tempo_preparo" placeholder="Ex: 45 minutos" required>

                <label for="dificuldade">Dificuldade:</label>
                <select id="dificuldade" name="dificuldade" required>
                    <option value="">Selecione a Dificuldade</option>
                    <option value="Fácil">Fácil</option>
                    <option value="Médio">Médio</option>
                    <option value="Difícil">Difícil</option>
                </select>
            </fieldset>

            <fieldset>
                <legend>Ingredientes</legend>
                <label for="ingredientes_texto">Lista de Ingredientes:</label>
                <textarea id="ingredientes_texto" name="ingredientes_texto" placeholder="Liste os ingredientes, um por linha, ou separados por vírgula. Ex: 2 xícaras de farinha, 1 ovo grande, 1 colher de chá de fermento." rows="8" required></textarea>
            </fieldset>

            <fieldset>
                <legend>Instruções e Descrição</legend>
                <label for="modo_preparo">Modo de Preparo:</label>
                <textarea id="modo_preparo" name="modo_preparo" placeholder="Descreva passo a passo como preparar a receita." rows="6" required></textarea>

                <label for="descricao">Descrição Geral da Receita (Opcional):</label>
                <textarea id="descricao" name="descricao" placeholder="Uma breve descrição sobre a receita."></textarea>
            </fieldset>

            <fieldset>
                <legend>Foto da Receita</legend>
                <label for="foto_receita">Selecione uma foto para a receita:</label>
                <input type="file" id="foto_receita" name="foto_receita" accept="image/*">
            </fieldset>
            
            <button type="submit" name="adicionar">Salvar Receita</button>
        </form>
    </div>

    <hr>

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
                <th>Ingredientes</th> <th>Descrição Geral</th> <th>Foto</th>
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
                    <td><?= nl2br(htmlspecialchars($r['descricao'])) ?></td>
                    <td><?= nl2br(htmlspecialchars($r['descricao'])) ?></td>
                    <td>
                        <?php
                        $stmtFoto = $conn->prepare("SELECT tipo FROM foto_receita WHERE nome_receita = ? LIMIT 1");
                        $stmtFoto->execute([$r['nome_receita']]);
                        $foto = $stmtFoto->fetch(PDO::FETCH_ASSOC);
                        if ($foto): ?>
                            <img src="data:image/jpeg;base64,<?= base64_encode($foto['tipo']) ?>" alt="Foto da Receita" class="foto-receita" style="max-width: 100px; height: auto;" />
                        <?php else: ?>
                            Sem foto
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="receitasAcoes/consultar.php?nome=<?= urlencode($r['nome_receita']) ?>">Consultar</a> |
                        <a href="receitasAcoes/editar.php?nome=<?= urlencode($r['nome_receita']) ?>">Editar</a> |
                        <a href="receitasAcoes/excluir.php?excluir=<?= urlencode($r['nome_receita']) ?>" onclick="return confirm('Tem certeza que deseja excluir esta receita?')">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
// Se você não precisa mais da funcionalidade de adicionar/remover campos de ingrediente dinamicamente,
// todo o bloco de script JavaScript pode ser removido ou limpo,
// já que o campo agora é um textarea único.
</script>
</body>
</html>