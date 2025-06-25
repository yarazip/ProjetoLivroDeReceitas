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

// Verifica se o usuário está logado e se tem permissão (Cozinheiro ou Administrador)
if (!isset($_SESSION['id_login']) || ($_SESSION['cargo'] !== 'cozinheiro' && $_SESSION['cargo'] !== 'administrador')) {
    $_SESSION['message'] = "Você não tem permissão para adicionar receitas.";
    $_SESSION['message_type'] = "error";
    header("Location: ../LoginSenha/login.php");
    exit;
}

// Buscar categorias, ingredientes e medidas para os selects do formulário
$categorias = $conn->query("SELECT id_categoria, nome_categoria FROM categorias")->fetchAll(PDO::FETCH_ASSOC);
$ingredientes_disponiveis = $conn->query("SELECT id_ingrediente, nome FROM ingredientes ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$medidas_disponiveis = $conn->query("SELECT id_medida, descricao, medida FROM medidas ORDER BY descricao ASC")->fetchAll(PDO::FETCH_ASSOC);


// Lógica de INSERIR nova receita
if (isset($_POST['adicionar'])) {
    $nome = $_POST['nome_receita'];
    $data = $_POST['data_criacao'];
    $id_categoria = $_POST['id_categoria'];
    $modo_preparo = $_POST['modo_preparo'];
    $porcoes = $_POST['porcoes'];
    $tempo_preparo = $_POST['tempo_preparo'];
    $dificuldade = $_POST['dificuldade'];
    $descricao_geral = $_POST['descricao_geral']; // Descrição geral da receita
    // Removido: $ingredientes_texto = $_POST['ingredientes_texto']; // Este campo agora é para depuração ou se você quiser mantê-lo auxiliar

    $id_funcionario = $_SESSION['id_funcionario'] ?? null;

    if (!$id_funcionario) {
        $_SESSION['message'] = "Erro: Funcionário não logado para adicionar receita.";
        $_SESSION['message_type'] = "error";
        header("Location: adicionarReceita.php");
        exit;
    }

    // Validação mínima dos ingredientes (se ao menos um set foi enviado)
    if (empty($_POST['ingredientes']) || empty($_POST['quantidades']) || empty($_POST['medidas'])) {
        $_SESSION['message'] = "Por favor, adicione pelo menos um ingrediente com quantidade e medida.";
        $_SESSION['message_type'] = "error";
        header("Location: adicionarReceita.php");
        exit;
    }

    $conn->beginTransaction();

    try {
        // Inserir receita principal
        $sql = "INSERT INTO receitas (nome_receita, data_criacao, id_categoria, modo_preparo, porcoes, tempo_preparo, dificuldade, descricao, id_funcionario)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nome, $data, $id_categoria, $modo_preparo, $porcoes, $tempo_preparo, $dificuldade, $descricao_geral, $id_funcionario]);


        // Inserir ingredientes na tabela receita_ingrediente (reativado)
        $ingredientes_post = $_POST['ingredientes'];
        $quantidades_post = $_POST['quantidades'];
        $medidas_post = $_POST['medidas'];
        $descricoes_ing_post = $_POST['descricao_ingrediente'] ?? []; // Descrição opcional por ingrediente

        $sqlIng = "INSERT INTO receita_ingrediente (nome_receita, id_ingrediente, id_medida, quantidade_ingrediente, descricao) VALUES (?, ?, ?, ?, ?)";
        $stmtIng = $conn->prepare($sqlIng);

        foreach ($ingredientes_post as $index => $id_ingrediente) {
            $id_medida = $medidas_post[$index];
            $quantidade = $quantidades_post[$index];
            $desc_ing = $descricoes_ing_post[$index] ?? null; // Pega a descrição específica se existir

            // Validação básica para garantir que todos os campos do ingrediente foram preenchidos
            if (empty($id_ingrediente) || empty($id_medida) || !is_numeric($quantidade) || $quantidade <= 0) {
                throw new Exception("Dados de ingrediente inválidos na linha " . ($index + 1));
            }
            $stmtIng->execute([$nome, $id_ingrediente, $id_medida, $quantidade, $desc_ing]);
        }

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
        header("Location: receitasChef.php"); // Redireciona para a página principal das receitas
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Erro ao adicionar receita: " . $e->getMessage());
        $_SESSION['message'] = "Erro ao adicionar receita: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: adicionarReceita.php"); // Redireciona de volta para o formulário de adição
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <title>Adicionar Nova Receita | Cozinheiro</title>
    <link rel="stylesheet" href="../styles/adicionarReceitas.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon" />

</head>

<body>
        <a href="../LoginSenha/logout.php" class="logout-button">
        <i class="fa-solid fa-right-from-bracket fa-lg gray-icon"></i>
    </a>
    <div class="container">
        <div class="menu">
            <h1 class="logo">Código de Sabores</h1>
            <nav>
                <a href="receitasChef.php">Receitas</a>
                <a href="ingredientesChef.php">Ingredientes</a>
                <a href="medidasChef.php">Medidas</a>
                <a href="categoriaChef.php">Categorias</a>
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

                    <label for="descricao_geral">Descrição Geral da Receita:</label>
                    <textarea id="descricao_geral" name="descricao_geral" placeholder="Uma breve descrição sobre a receita."></textarea>
                </fieldset>

                <fieldset>
                    <legend>Foto da Receita</legend>
                    <label for="foto_receita">Selecione uma foto para a receita:</label>
                    <input type="file" id="foto_receita" name="foto_receita" accept="image/*">
                </fieldset>

                <button type="submit" name="adicionar">Salvar Receita</button>
                <a href="receitasChef.php"><button type="button">Cancelar</button></a>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let ingredienteCounter = 1; // Começa em 1 porque o primeiro já está no HTML

            // Função para clonar e adicionar um novo item de ingrediente
            function addIngredienteItem() {
                const container = document.querySelector('.ingredientes-container');
                const firstItem = container.querySelector('.ingrediente-item');
                const newItem = firstItem.cloneNode(true); // Clonar o primeiro item com todos os seus elementos

                // Atualizar IDs e nomes para serem únicos
                newItem.querySelectorAll('[id]').forEach(el => {
                    el.id = el.id.replace(/_(\d+)$/, '_' + ingredienteCounter);
                });
                newItem.querySelectorAll('[name]').forEach(el => {
                    el.name = el.name.replace(/\[\]$/, '[' + ingredienteCounter + ']');
                    // Limpa o valor para que o novo campo não venha preenchido
                    if (el.tagName === 'SELECT' || el.tagName === 'INPUT') {
                        el.value = '';
                    }
                });

                // Mudar o botão "+" para um botão de remover "-"
                const addButton = newItem.querySelector('.adicionar-ingrediente');
                if (addButton) {
                    addButton.classList.replace('adicionar-ingrediente', 'remover-ingrediente');
                    addButton.textContent = '-';
                }

                container.appendChild(newItem);
                ingredienteCounter++;
            }

            // Adiciona listener para o botão "Adicionar Ingrediente"
            // Usamos delegação de evento para botões dinamicamente adicionados
            document.querySelector('.ingredientes-container').addEventListener('click', function(event) {
                if (event.target.classList.contains('adicionar-ingrediente')) {
                    addIngredienteItem();
                } else if (event.target.classList.contains('remover-ingrediente')) {
                    // Garante que pelo menos um ingrediente permaneça
                    if (document.querySelectorAll('.ingrediente-item').length > 1) {
                        event.target.closest('.ingrediente-item').remove();
                    } else {
                        alert("É necessário ter pelo menos um ingrediente na receita.");
                    }
                }
            });
        });
        document.addEventListener('DOMContentLoaded', function () {
    // Inicializa o Select2 para todos os selects de ingredientes
    function aplicarSelect2() {
        document.querySelectorAll('select[name^="ingredientes"]').forEach(function(select) {
            $(select).select2({
                width: 'resolve',
                placeholder: "Busque um ingrediente",
                language: "pt-BR"
            });
        });
    }

    aplicarSelect2(); // aplica nos existentes

    // Reaplica ao adicionar novo ingrediente
    document.querySelector('.ingredientes-container').addEventListener('click', function(event) {
        if (event.target.classList.contains('adicionar-ingrediente')) {
            setTimeout(aplicarSelect2, 50); // espera DOM adicionar novo item
        }
    });
});
    </script>
</body>

</html>