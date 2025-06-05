<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../BancoDeDados/conexao.php'; // Caminho ajustado

// Buscar categorias e medidas para os selects (medidas podem ser úteis para modo de preparo ou descrição)
$categorias = $conn->query("SELECT id_categoria, nome_categoria FROM categorias")->fetchAll(PDO::FETCH_ASSOC);
// As variáveis $ingredientes e $medidas (para os selects de ingredientes) não são mais necessárias se for texto livre
// $ingredientes = $conn->query("SELECT id_ingrediente, nome FROM ingredientes")->fetchAll(PDO::FETCH_ASSOC);
// $medidas = $conn->query("SELECT id_medida, descricao, medida FROM medidas")->fetchAll(PDO::FETCH_ASSOC);

// Lógica para processar a ATUALIZAÇÃO (se o formulário foi submetido)
if (isset($_POST['atualizar'])) {
    $nome_original = $_POST['nome_original'];
    $nome = $_POST['nome_receita'];
    $data = $_POST['data_criacao'];
    $id_funcionario = $_POST['id_funcionario']; // Pegando do hidden input
    $id_categoria = $_POST['id_categoria'];
    $modo_preparo = $_POST['modo_preparo'];
    $porcoes = $_POST['porcoes'];
    $tempo_preparo = $_POST['tempo_preparo'];
    $dificuldade = $_POST['dificuldade'];
    $descricao = $_POST['descricao'];
    $ingredientes_texto = $_POST['ingredientes_texto']; // NOVO CAMPO DE TEXTO LIVRE

    $conn->beginTransaction();

    try {
        // Atualizar dados da receita principal, incluindo o campo de texto livre para ingredientes
        // Certifique-se de que a coluna 'ingredientes_lista_texto' existe na sua tabela 'receitas'
        $sql = "UPDATE receitas SET
            nome_receita = ?, data_criacao = ?, id_funcionario = ?, id_categoria = ?,
            modo_preparo = ?, porcoes = ?, tempo_preparo = ?, dificuldade = ?, descricao = ?,
            ingredientes_lista_texto = ? -- Adicionando a nova coluna aqui
            WHERE nome_receita = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $nome, $data, $id_funcionario, $id_categoria,
            $modo_preparo, $porcoes, $tempo_preparo, $dificuldade, $descricao,
            $ingredientes_texto, // Valor para a nova coluna
            $nome_original
        ]);

        // **REMOVENDO A LÓGICA DE EXCLUIR E INSERIR INGREDIENTES DA RECEITA N:N**
        // Pois agora os ingredientes são um campo de texto livre.
        // Se você não usa mais a tabela 'receita_ingrediente', pode apagar estas linhas.
        /*
        $stmt = $conn->prepare("DELETE FROM receita_ingrediente WHERE nome_receita = ?");
        $stmt->execute([$nome_original]);

        if (!empty($_POST['ingredientes'])) { // Este bloco não será mais usado
            $ingredientes_post = $_POST['ingredientes'];
            $medidas_post = $_POST['medidas'];
            $quantidades = $_POST['quantidades'];
            $descricoes_ing = $_POST['descricao_ingrediente'] ?? [];

            $sqlIng = "INSERT INTO receita_ingrediente (nome_receita, id_ingrediente, id_medida, quantidade_ingrediente, descricao) VALUES (?, ?, ?, ?, ?)";
            $stmtIng = $conn->prepare($sqlIng);

            foreach ($ingredientes_post as $index => $id_ingrediente) {
                $id_medida = $medidas_post[$index] ?? null;
                $quantidade = $quantidades[$index] ?? 0;
                $desc_ing = $descricoes_ing[$index] ?? null;
                if ($id_medida !== null) {
                    $stmtIng->execute([$nome, $id_ingrediente, $id_medida, $quantidade, $desc_ing]);
                }
            }
        }
        */

        // Atualizar foto, se houver um novo upload
        if (isset($_FILES['foto_receita']) && $_FILES['foto_receita']['error'] === UPLOAD_ERR_OK && !empty($_FILES['foto_receita']['tmp_name'])) {
            $foto = file_get_contents($_FILES['foto_receita']['tmp_name']);

            $stmtFotoCheck = $conn->prepare("SELECT id_foto_receita FROM foto_receita WHERE nome_receita = ? AND id_funcionario = ?");
            $stmtFotoCheck->execute([$nome, $id_funcionario]);
            $fotoExistente = $stmtFotoCheck->fetch(PDO::FETCH_ASSOC);

            if ($fotoExistente) {
                $stmtFotoUpdate = $conn->prepare("UPDATE foto_receita SET tipo = ?, data_upload = NOW() WHERE id_foto_receita = ?");
                $stmtFotoUpdate->execute([$foto, $fotoExistente['id_foto_receita']]);
            } else {
                $stmtFotoInsert = $conn->prepare("INSERT INTO foto_receita (tipo, data_upload, id_funcionario, nome_receita) VALUES (?, NOW(), ?, ?)");
                $stmtFotoInsert->execute([$foto, $id_funcionario, $nome]);
            }
        }

        $conn->commit();
        $_SESSION['message'] = "Receita '" . htmlspecialchars($nome) . "' atualizada com sucesso!";
        $_SESSION['message_type'] = "success";
        header("Location: ../receitasChef.php"); // Redireciona para a lista principal
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Erro ao atualizar receita: " . $e->getMessage());
        $_SESSION['message'] = "Erro ao atualizar receita: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
       header("Location: ../receitasChef.php"); // Redireciona com erro
        exit;
    }
}

// Lógica para BUSCAR RECEITA PARA EDIÇÃO (se o formulário não foi submetido, mas a página foi acessada via GET)
$receita_editar = null;
if (isset($_GET['nome'])) { // Usando 'nome' como parâmetro, como você já faz
    $nome_receita_param = $_GET['nome'];

    // Buscar receita (agora incluindo 'ingredientes_lista_texto')
    $stmt = $conn->prepare("SELECT nome_receita, data_criacao, id_funcionario, id_categoria, modo_preparo, porcoes, tempo_preparo, dificuldade, descricao, ingredientes_lista_texto
                            FROM receitas WHERE nome_receita = ?");
    $stmt->execute([$nome_receita_param]);
    $receita_editar = $stmt->fetch(PDO::FETCH_ASSOC);

    // **REMOVENDO A BUSCA DE INGREDIENTES DA TABELA N:N**
    /*
    if ($receita_editar) {
        $stmtIng = $conn->prepare("SELECT * FROM receita_ingrediente WHERE nome_receita = ?");
        $stmtIng->execute([$nome_receita_param]);
        $receita_editar['ingredientes'] = $stmtIng->fetchAll(PDO::FETCH_ASSOC);
    */

    // Buscar foto da receita (mantido)
    if ($receita_editar) { // Mantém a verificação
        $stmtFoto = $conn->prepare("SELECT tipo FROM foto_receita WHERE nome_receita = ? LIMIT 1");
        $stmtFoto->execute([$nome_receita_param]);
        $foto = $stmtFoto->fetch(PDO::FETCH_ASSOC);
        if ($foto) {
            $receita_editar['foto_receita'] = $foto['tipo'];
        }
    } else {
        // Redireciona se a receita não for encontrada
        $_SESSION['message'] = "Receita não encontrada para edição.";
        $_SESSION['message_type'] = "error";
        header("Location: ../../receitasChef.php");
        exit;
    }
} else {
    // Redireciona se a página foi acessada sem o parâmetro 'nome'
    $_SESSION['message'] = "Parâmetro 'nome' da receita ausente para edição.";
    $_SESSION['message_type'] = "error";
    header("Location: ../../receitasChef.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <title>Editar Receita</title>
    <link rel="stylesheet" href="../../styles/func.css" />
    <link rel="shortcut icon" href="../../assets/favicon.png" type="image/x-icon" />
    <style>
        /* Remova estilos específicos de .ingrediente-item se não for mais usado
           ou ajuste para o novo layout. */
        .foto-receita { max-width: 100px; max-height: 100px; }
        textarea { width: calc(100% - 22px); padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        fieldset { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        legend { font-weight: bold; padding: 0 10px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="date"], input[type="number"], select {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button[type="submit"], button[type="button"] {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
        }
        button[type="submit"] { background-color: #4CAF50; color: white; }
        button[type="button"] { background-color: #f44336; color: white; } /* Cancelar */
        .insert-bar form { display: flex; flex-direction: column; gap: 10px; } /* Ajuste de layout */
    </style>
</head>
<body>
<div class="container">
    <div class="menu">
        <h1 class="logo">Código de Sabores</h1>
        <nav>
            <a href="../receitasChef.php">Receitas</a>
            <a href="../ingredientesChef.php">Ingredientes</a>
            <a href="../medidasChef.php">Medidas</a>
            <a href="../categoriaChef.php">Categorias</a>
        </nav>
    </div>

    <div class="insert-bar">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="nome_original" value="<?= htmlspecialchars($receita_editar['nome_receita'] ?? '') ?>">
            <input type="hidden" name="id_funcionario" value="<?= htmlspecialchars($receita_editar['id_funcionario'] ?? ($_SESSION['id_funcionario'] ?? '')) ?>">

            <p>Funcionário logado: <strong><?= htmlspecialchars($_SESSION['nome_funcionario'] ?? 'Desconhecido') ?></strong></p>

            <fieldset>
                <legend>Informações Básicas da Receita</legend>
                <label for="nome_receita">Nome da Receita:</label>
                <input type="text" id="nome_receita" name="nome_receita" placeholder="Nome da Receita" required
                        value="<?= htmlspecialchars($receita_editar['nome_receita'] ?? '') ?>">

                <label for="data_criacao">Data de Criação:</label>
                <input type="date" id="data_criacao" name="data_criacao" required
                        value="<?= htmlspecialchars($receita_editar['data_criacao'] ?? date('Y-m-d')) ?>">

                <label for="id_categoria">Categoria:</label>
                <select id="id_categoria" name="id_categoria" required>
                    <option value="">Selecione a Categoria</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= htmlspecialchars($categoria['id_categoria']) ?>"
                            <?= (isset($receita_editar) && $receita_editar['id_categoria'] == $categoria['id_categoria']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($categoria['nome_categoria']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="porcoes">Número de Porções:</label>
                <input type="number" id="porcoes" name="porcoes" placeholder="Porções" min="1" required
                        value="<?= htmlspecialchars($receita_editar['porcoes'] ?? '') ?>">

                <label for="tempo_preparo">Tempo de Preparo (Ex: 30 min, 1h 15min):</label>
                <input type="text" id="tempo_preparo" name="tempo_preparo" placeholder="Tempo Preparo" required
                        value="<?= htmlspecialchars($receita_editar['tempo_preparo'] ?? '') ?>">

                <label for="dificuldade">Dificuldade:</label>
                <select id="dificuldade" name="dificuldade" required>
                    <option value="">Selecione a Dificuldade</option>
                    <option value="Fácil" <?= (isset($receita_editar) && $receita_editar['dificuldade'] == 'Fácil') ? 'selected' : '' ?>>Fácil</option>
                    <option value="Médio" <?= (isset($receita_editar) && $receita_editar['dificuldade'] == 'Médio') ? 'selected' : '' ?>>Médio</option>
                    <option value="Difícil" <?= (isset($receita_editar) && $receita_editar['dificuldade'] == 'Difícil') ? 'selected' : '' ?>>Difícil</option>
                </select>
            </fieldset>

            <fieldset>
                <legend>Ingredientes</legend>
                <label for="ingredientes_texto">Lista de Ingredientes:</label>
                <textarea id="ingredientes_texto" name="ingredientes_texto" placeholder="Liste os ingredientes, um por linha, ou separados por vírgula. Ex: 2 xícaras de farinha, 1 ovo grande, 1 colher de chá de fermento." rows="8" required><?= htmlspecialchars($receita_editar['ingredientes_lista_texto'] ?? '') ?></textarea>
            </fieldset>

            <fieldset>
                <legend>Instruções e Descrição</legend>
                <label for="modo_preparo">Modo de Preparo:</label>
                <textarea id="modo_preparo" name="modo_preparo" placeholder="Modo de Preparo" rows="6" required><?= htmlspecialchars($receita_editar['modo_preparo'] ?? '') ?></textarea>

                <label for="descricao">Descrição Geral da Receita (Opcional):</label>
                <textarea id="descricao" name="descricao" placeholder="Descrição Geral"><?= htmlspecialchars($receita_editar['descricao'] ?? '') ?></textarea>
            </fieldset>

            <fieldset>
                <legend>Foto da Receita</legend>
                <label for="foto_receita">Selecione uma nova foto para a receita (opcional):</label>
                <input type="file" id="foto_receita" name="foto_receita" accept="image/*">
                <?php if (isset($receita_editar) && !empty($receita_editar['foto_receita'])): ?>
                    <p>Foto atual:</p>
                    <img src="data:image/jpeg;base64,<?= base64_encode($receita_editar['foto_receita']) ?>" alt="Foto da Receita" class="foto-receita">
                <?php else: ?>
                    <p>Nenhuma foto atual.</p>
                <?php endif; ?>
            </fieldset>
            
            <button type="submit" name="atualizar">Atualizar Receita</button>
            <a href="../../receitasChef.php" class="button-cancel"><button type="button">Cancelar</button></a>
        </form>
    </div>
</div>

<script>
// Se você não precisa mais da funcionalidade de adicionar/remover campos de ingrediente dinamicamente,
// todo o bloco de script JavaScript pode ser removido ou limpo.
// Pois agora o campo de ingrediente é um textarea único.
</script>
</body>
</html>