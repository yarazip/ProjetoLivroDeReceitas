<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../BancoDeDados/conexao.php';

// Check if user is logged in and is an Editor
if (!isset($_SESSION['id_login']) || $_SESSION['cargo'] !== 'Editor') {
    $_SESSION['message'] = "Você não tem permissão para adicionar receitas.";
    $_SESSION['message_type'] = "error";
    header("Location: ../LoginSenha/login.php");
    exit;
}

$id_editor = $_SESSION['id_funcionario'] ?? null;

if (!$id_editor) {
    $_SESSION['message'] = "Erro: Editor não identificado.";
    $_SESSION['message_type'] = "error";
    header("Location: ../LoginSenha/login.php");
    exit;
}

// Fetch livros linked to this editor for the select dropdown
$sqlLivros = "SELECT id_livro, titulo FROM livros WHERE id_editor = ?";
$stmtLivros = $conn->prepare($sqlLivros);
$stmtLivros->execute([$id_editor]);
$livros = $stmtLivros->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories, ingredients, and measures for the form selects
$categorias = $conn->query("SELECT id_categoria, nome_categoria FROM categorias")->fetchAll(PDO::FETCH_ASSOC);
$ingredientes_disponiveis = $conn->query("SELECT id_ingrediente, nome FROM ingredientes ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$medidas_disponiveis = $conn->query("SELECT id_medida, descricao, medida FROM medidas ORDER BY descricao ASC")->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['adicionar'])) {
    $nome = $_POST['nome_receita'];
    $data = $_POST['data_criacao'];
    $id_categoria = $_POST['id_categoria'];
    $modo_preparo = $_POST['modo_preparo'];
    $porcoes = $_POST['porcoes'];
    $tempo_preparo = $_POST['tempo_preparo'];
    $dificuldade = $_POST['dificuldade'];
    $descricao_geral = $_POST['descricao_geral'];
    $id_livro = $_POST['id_livro'] ?? null;

    if (!$id_livro) {
        $_SESSION['message'] = "Por favor, selecione um Livro para vincular a receita.";
        $_SESSION['message_type'] = "error";
        header("Location: adicionar_receita.php");
        exit;
    }

    if (empty($_POST['ingredientes']) || empty($_POST['quantidades']) || empty($_POST['medidas'])) {
        $_SESSION['message'] = "Por favor, adicione pelo menos um ingrediente com quantidade e medida.";
        $_SESSION['message_type'] = "error";
        header("Location: adicionar_receita.php");
        exit;
    }

    $conn->beginTransaction();

    try {
        // Insert recipe with link to livro and editor's funcionario id
        $sql = "INSERT INTO receitas (nome_receita, data_criacao, id_categoria, modo_preparo, porcoes, tempo_preparo, dificuldade, descricao, id_livro, id_funcionario)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nome, $data, $id_categoria, $modo_preparo, $porcoes, $tempo_preparo, $dificuldade, $descricao_geral, $id_livro, $id_editor]);

        // Insert ingredients
        $ingredientes_post = $_POST['ingredientes'];
        $quantidades_post = $_POST['quantidades'];
        $medidas_post = $_POST['medidas'];
        $descricoes_ing_post = $_POST['descricao_ingrediente'] ?? [];

        $sqlIng = "INSERT INTO receita_ingrediente (nome_receita, id_ingrediente, id_medida, quantidade_ingrediente, descricao) VALUES (?, ?, ?, ?, ?)";
        $stmtIng = $conn->prepare($sqlIng);

        foreach ($ingredientes_post as $index => $id_ingrediente) {
            $id_medida = $medidas_post[$index];
            $quantidade = $quantidades_post[$index];
            $desc_ing = $descricoes_ing_post[$index] ?? null;

            if (empty($id_ingrediente) || empty($id_medida) || !is_numeric($quantidade) || $quantidade <= 0) {
                throw new Exception("Dados de ingrediente inválidos na linha " . ($index + 1));
            }
            $stmtIng->execute([$nome, $id_ingrediente, $id_medida, $quantidade, $desc_ing]);
        }

        // Insert photo if uploaded
        if (isset($_FILES['foto_receita']) && $_FILES['foto_receita']['error'] === UPLOAD_ERR_OK && !empty($_FILES['foto_receita']['tmp_name'])) {
            $foto = file_get_contents($_FILES['foto_receita']['tmp_name']);
            $sqlFoto = "INSERT INTO foto_receita (tipo, data_upload, id_funcionario, nome_receita) VALUES (?, NOW(), ?, ?)";
            $stmtFoto = $conn->prepare($sqlFoto);
            $stmtFoto->execute([$foto, $id_editor, $nome]);
        }

        $conn->commit();
        $_SESSION['message'] = "Receita '" . htmlspecialchars($nome) . "' adicionada com sucesso!";
        $_SESSION['message_type'] = "success";
        header("Location: listar_receitas_editor.php");
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Erro ao adicionar receita: " . $e->getMessage());
        $_SESSION['message'] = "Erro ao adicionar receita: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: adicionar_receita.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <title>Adicionar Nova Receita ao Livro | Editor</title>
    <link rel="stylesheet" href="../styles/func.css" />
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon" />
    <style>
        /* Reuse styles from adicionarReceitas.php or customize as needed */
        .insert-bar form { display: flex; flex-direction: column; gap: 15px; }
        .insert-bar fieldset { border: 1px solid #ccc; padding: 20px; border-radius: 8px; background-color: #f9f9f9; }
        .insert-bar legend { font-size: 1.2em; font-weight: bold; padding: 0 10px; color: #333; }
        .insert-bar label { display: block; margin-bottom: 5px; font-weight: bold; }
        .insert-bar input[type="text"],
        .insert-bar input[type="date"],
        .insert-bar input[type="number"],
        .insert-bar select,
        .insert-bar textarea { width: calc(100% - 22px); padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        .insert-bar button { padding: 12px 25px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1em; margin-top: 10px; }
        .insert-bar button:hover { opacity: 0.9; }
        .message-success, .message-error { padding: 10px; margin-bottom: 20px; border-radius: 5px; font-weight: bold; }
        .message-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .ingredientes-container {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            background-color: #fff;
        }
        .ingrediente-item {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #eee;
            align-items: flex-end;
        }
        .ingrediente-item:last-child {
            border-bottom: none;
        }
        .ingrediente-item label {
            flex-basis: 100%;
            margin-bottom: 5px;
        }
        .ingrediente-item select,
        .ingrediente-item input[type="number"],
        .ingrediente-item input[type="text"] {
            flex-grow: 1;
            margin-bottom: 0;
            min-width: 120px;
        }
        .ingrediente-item input[type="number"] { width: 80px; min-width: 80px; }
        .ingrediente-item button {
            flex-shrink: 0;
            padding: 8px 12px;
            font-size: 0.9em;
            margin-top: 0;
        }
        .adicionar-ingrediente { background-color: #007bff; color: white; }
        .remover-ingrediente { background-color: #dc3545; color: white; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>Adicionar Nova Receita ao Livro</h1>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="message-<?= $_SESSION['message_type'] ?? 'info' ?>">
            <?= htmlspecialchars($_SESSION['message']) ?>
        </div>
        <?php
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        ?>
    <?php endif; ?>

    <div class="insert-bar">
        <form method="POST" enctype="multipart/form-data">
            <p><strong>Editor logado:</strong> <?= htmlspecialchars($_SESSION['nome_funcionario'] ?? 'Desconhecido') ?></p>

            <fieldset>
                <legend>Livro</legend>
                <label for="id_livro">Selecione o Livro:</label>
                <select id="id_livro" name="id_livro" size="5" style="width: 100%; min-width: 300px; max-width: 600px;" required>
                    <option value="">Selecione o Livro</option>
                    <?php foreach ($livros as $livro): ?>
                        <option value="<?= htmlspecialchars($livro['id_livro']) ?>">
                            <?= htmlspecialchars($livro['titulo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </fieldset>

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

            <fieldset class="ingredientes-container">
                <legend>Ingredientes</legend>
                <div class="ingrediente-item">
                    <label for="ingrediente_0" class="sr-only">Ingrediente:</label>
                    <select name="ingredientes[]" id="ingrediente_0" required>
                        <option value="">Selecione o ingrediente</option>
                        <?php foreach ($ingredientes_disponiveis as $ingrediente): ?>
                            <option value="<?= htmlspecialchars($ingrediente['id_ingrediente']) ?>">
                                <?= htmlspecialchars($ingrediente['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="quantidade_0" class="sr-only">Quantidade:</label>
                    <input type="number" name="quantidades[]" id="quantidade_0" step="0.1" min="0" placeholder="Qtd" required>

                    <label for="medida_0" class="sr-only">Medida:</label>
                    <select name="medidas[]" id="medida_0" required>
                        <option value="">Selecione a medida</option>
                        <?php foreach ($medidas_disponiveis as $medida): ?>
                            <option value="<?= htmlspecialchars($medida['id_medida']) ?>">
                                <?= htmlspecialchars($medida['descricao'] . ' (' . $medida['medida'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="descricao_ingrediente_0" class="sr-only">Descrição Adicional:</label>
                    <input type="text" name="descricao_ingrediente[]" id="descricao_ingrediente_0" placeholder="Descrição (ex: picado)">

                    <button type="button" class="adicionar-ingrediente">+</button>
                </div>
            </fieldset>

            <fieldset>
                <legend>Instruções e Descrição</legend>
                <label for="modo_preparo">Modo de Preparo:</label>
                <textarea id="modo_preparo" name="modo_preparo" placeholder="Descreva passo a passo como preparar a receita." rows="6" required></textarea>

                <label for="descricao_geral">Descrição Geral da Receita (Opcional):</label>
                <textarea id="descricao_geral" name="descricao_geral" placeholder="Uma breve descrição sobre a receita."></textarea>
            </fieldset>

            <fieldset>
                <legend>Foto da Receita</legend>
                <label for="foto_receita">Selecione uma foto para a receita:</label>
                <input type="file" id="foto_receita" name="foto_receita" accept="image/*">
            </fieldset>

            <button type="submit" name="adicionar">Salvar Receita</button>
            <a href="listar_receitas_editor.php"><button type="button">Cancelar</button></a>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let ingredienteCounter = 1;

    function addIngredienteItem() {
        const container = document.querySelector('.ingredientes-container');
        const firstItem = container.querySelector('.ingrediente-item');
        const newItem = firstItem.cloneNode(true);

        newItem.querySelectorAll('[id]').forEach(el => {
            el.id = el.id.replace(/_(\d+)$/, '_' + ingredienteCounter);
        });
        newItem.querySelectorAll('[name]').forEach(el => {
            el.name = el.name.replace(/\[\]$/, '[' + ingredienteCounter + ']');
            if (el.tagName === 'SELECT' || el.tagName === 'INPUT') {
                el.value = '';
            }
        });

        const addButton = newItem.querySelector('.adicionar-ingrediente');
        if (addButton) {
            addButton.classList.replace('adicionar-ingrediente', 'remover-ingrediente');
            addButton.textContent = '-';
        }

        container.appendChild(newItem);
        ingredienteCounter++;
    }

    document.querySelector('.ingredientes-container').addEventListener('click', function(event) {
        if (event.target.classList.contains('adicionar-ingrediente')) {
            addIngredienteItem();
        } else if (event.target.classList.contains('remover-ingrediente')) {
            if (document.querySelectorAll('.ingrediente-item').length > 1) {
                event.target.closest('.ingrediente-item').remove();
            } else {
                alert("É necessário ter pelo menos um ingrediente na receita.");
            }
        }
    });
});
</script>
</body>
</html>
