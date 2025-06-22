<?php
session_start();
if (!isset($_SESSION['id_funcionario'])) {
    header("Location: ../LoginSenha/login.php");
    exit();
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../BancoDeDados/conexao.php'; // Caminho ajustado

// Verifica se o usuário está logado e se tem permissão
if (!isset($_SESSION['id_login']) || ($_SESSION['cargo'] !== 'cozinheiro' && $_SESSION['cargo'] !== 'administrador')) {
    $_SESSION['message'] = "Você não tem permissão para editar receitas.";
    $_SESSION['message_type'] = "error";
    header("Location: ../../LoginSenha/login.php");
    exit;
}

// Buscar categorias, ingredientes e medidas para os selects
$categorias = $conn->query("SELECT id_categoria, nome_categoria FROM categorias ORDER BY nome_categoria ASC")->fetchAll(PDO::FETCH_ASSOC);
$ingredientes_disponiveis = $conn->query("SELECT id_ingrediente, nome FROM ingredientes ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$medidas_disponiveis = $conn->query("SELECT id_medida, descricao, medida FROM medidas ORDER BY descricao ASC")->fetchAll(PDO::FETCH_ASSOC);

// Lógica para processar a ATUALIZAÇÃO (se o formulário foi submetido)
if (isset($_POST['atualizar'])) {
    $nome_original = $_POST['nome_original'];
    $nome = $_POST['nome_receita'];
    $data = $_POST['data_criacao'];
    $id_funcionario = $_POST['id_funcionario'];
    $id_categoria = $_POST['id_categoria'];
    $modo_preparo = $_POST['modo_preparo'];
    $porcoes = $_POST['porcoes'];
    $tempo_preparo = $_POST['tempo_preparo'];
    $dificuldade = $_POST['dificuldade'];
    $descricao_geral = $_POST['descricao_geral']; // Descrição geral da receita
    // Removido: $ingredientes_texto = $_POST['ingredientes_texto'];

    $conn->beginTransaction();

    try {
        // Atualizar dados da receita principal
        $sql = "UPDATE receitas SET
        nome_receita = ?, data_criacao = ?, id_funcionario = ?, id_categoria = ?,
        modo_preparo = ?, porcoes = ?, tempo_preparo = ?, dificuldade = ?, descricao = ?
        WHERE nome_receita = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $nome,
            $data,
            $id_funcionario,
            $id_categoria,
            $modo_preparo,
            $porcoes,
            $tempo_preparo,
            $dificuldade,
            $descricao_geral,
            $nome_original
        ]);

        // Excluir ingredientes antigos e reinserir os atualizados
        $stmt_delete_ing = $conn->prepare("DELETE FROM receita_ingrediente WHERE nome_receita = ?");
        $stmt_delete_ing->execute([$nome_original]);

        if (!empty($_POST['ingredientes']) && !empty($_POST['quantidades']) && !empty($_POST['medidas'])) {
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
                    // Lidar com erro de ingrediente inválido
                    // Para edição, podemos permitir que campos vazios não gerem erro fatal, mas que sejam ignorados ou validados melhor
                    continue; // Pula para o próximo ingrediente se este for inválido
                }
                $stmtIng->execute([$nome, $id_ingrediente, $id_medida, $quantidade, $desc_ing]);
            }
        }


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
        header("Location: ../receitasChef.php");
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Erro ao atualizar receita: " . $e->getMessage());
        $_SESSION['message'] = "Erro ao atualizar receita: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: ../receitasChef.php");
        exit;
    }
}

// Lógica para BUSCAR RECEITA PARA EDIÇÃO (se o formulário não foi submetido, mas a página foi acessada via GET)
$receita_editar = null;
if (isset($_GET['nome'])) {
    $nome_receita_param = $_GET['nome'];

    // Buscar receita principal
    $stmt = $conn->prepare("SELECT nome_receita, data_criacao, id_funcionario, id_categoria, modo_preparo, porcoes, tempo_preparo, dificuldade, descricao
                            FROM receitas WHERE nome_receita = ?");
    $stmt->execute([$nome_receita_param]);
    $receita_editar = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($receita_editar) {
        // Buscar ingredientes específicos desta receita (reativado)
        $stmtIng = $conn->prepare("SELECT ri.id_ingrediente, ri.id_medida, ri.quantidade_ingrediente, ri.descricao AS descricao_ingrediente_item
                                    FROM receita_ingrediente ri
                                    WHERE ri.nome_receita = ?");
        $stmtIng->execute([$nome_receita_param]);
        $receita_editar['ingredientes_detalhes'] = $stmtIng->fetchAll(PDO::FETCH_ASSOC);

        // Buscar foto da receita
        $stmtFoto = $conn->prepare("SELECT tipo FROM foto_receita WHERE nome_receita = ? LIMIT 1");
        $stmtFoto->execute([$nome_receita_param]);
        $foto = $stmtFoto->fetch(PDO::FETCH_ASSOC);
        if ($foto) {
            $receita_editar['foto_receita'] = $foto['tipo'];
        }
    } else {
        $_SESSION['message'] = "Receita não encontrada para edição.";
        $_SESSION['message_type'] = "error";
        header("Location: ../receitasChef.php");
        exit;
    }
} else {
    $_SESSION['message'] = "Parâmetro 'nome' da receita ausente para edição.";
    $_SESSION['message_type'] = "error";
    header("Location: ../receitasChef.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <title>Editar Receita</title>
    <link rel="stylesheet" href="../../styles/edicaoREC.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="shortcut icon" href="../../assets/favicon.png" type="image/x-icon" />

</head>

<body>
        <a href="../LoginSenha/logout.php" class="logout-button">
        <i class="fa-solid fa-right-from-bracket fa-lg gray-icon"></i>
    </a>
    <div class="container">
        <div class="menu">
            <h1 class="logo">Código de Sabores</h1>
            <nav>
                <a href="../receitasChef.php">Receitas</a>
                <a href="../ingredientesChef.php">Ingredientes</a>
                <a href="../medidasChef.php">Medidas</a>
                <a href="../categoriaChef.php">Categorias</a>
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?= htmlspecialchars($_SESSION['nome_funcionario'] ?? 'Desconhecido') ?></span>
                </div>
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

                <fieldset class="ingredientes-container">
                    <legend>Ingredientes</legend>
                    <?php if (!empty($receita_editar['ingredientes_detalhes'])): ?>
                        <?php foreach ($receita_editar['ingredientes_detalhes'] as $index => $ing): ?>
                            <div class="ingrediente-item">
                                <label for="ingrediente_<?= $index ?>" class="sr-only">Ingrediente:</label>
                                <select name="ingredientes[]" id="ingrediente_<?= $index ?>" required>
                                    <option value="">Selecione o ingrediente</option>
                                    <?php foreach ($ingredientes_disponiveis as $ingrediente_disp): ?>
                                        <option value="<?= htmlspecialchars($ingrediente_disp['id_ingrediente']) ?>"
                                            <?= ($ing['id_ingrediente'] == $ingrediente_disp['id_ingrediente']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($ingrediente_disp['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <label for="quantidade_<?= $index ?>" class="sr-only">Quantidade:</label>
                                <input type="number" name="quantidades[]" id="quantidade_<?= $index ?>" step="0.1" min="0" placeholder="Qtd"
                                    value="<?= htmlspecialchars($ing['quantidade_ingrediente']) ?>" required>

                                <label for="medida_<?= $index ?>" class="sr-only">Medida:</label>
                                <select name="medidas[]" id="medida_<?= $index ?>" required>
                                    <option value="">Selecione a medida</option>
                                    <?php foreach ($medidas_disponiveis as $medida_disp): ?>
                                        <option value="<?= htmlspecialchars($medida_disp['id_medida']) ?>"
                                            <?= ($ing['id_medida'] == $medida_disp['id_medida']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($medida_disp['descricao'] . ' (' . $medida_disp['medida'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <label for="descricao_ingrediente_<?= $index ?>" class="sr-only">Descrição Adicional:</label>
                                <input type="text" name="descricao_ingrediente[]" id="descricao_ingrediente_<?= $index ?>" placeholder="Descrição (ex: picado)"
                                    value="<?= htmlspecialchars($ing['descricao_ingrediente_item'] ?? '') ?>">

                                <?php if ($index === 0 && count($receita_editar['ingredientes_detalhes']) == 1): // Se for o único item inicial 
                                ?>
                                    <button type="button" class="adicionar-ingrediente">+</button>
                                <?php else: ?>
                                    <button type="button" class="remover-ingrediente">-</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: // Se não houver ingredientes ou se a receita for nova 
                    ?>
                        <div class="ingrediente-item">
                            <label for="ingrediente_0" class="sr-only">Ingrediente:</label>
                            <select name="ingredientes[]" id="ingrediente_0" required>
                                <option value="">Selecione o ingrediente</option>
                                <?php foreach ($ingredientes_disponiveis as $ingrediente_disp): ?>
                                    <option value="<?= htmlspecialchars($ingrediente_disp['id_ingrediente']) ?>">
                                        <?= htmlspecialchars($ingrediente_disp['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <label for="quantidade_0" class="sr-only">Quantidade:</label>
                            <input type="number" name="quantidades[]" id="quantidade_0" step="0.1" min="0" placeholder="Qtd" required>

                            <label for="medida_0" class="sr-only">Medida:</label>
                            <select name="medidas[]" id="medida_0" required>
                                <option value="">Selecione a medida</option>
                                <?php foreach ($medidas_disponiveis as $medida_disp): ?>
                                    <option value="<?= htmlspecialchars($medida_disp['id_medida']) ?>">
                                        <?= htmlspecialchars($medida_disp['descricao'] . ' (' . $medida_disp['medida'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <label for="descricao_ingrediente_0" class="sr-only">Descrição Adicional:</label>
                            <input type="text" name="descricao_ingrediente[]" id="descricao_ingrediente_0" placeholder="Descrição (ex: picado)">

                            <button type="button" class="adicionar-ingrediente">+</button>
                        </div>
                    <?php endif; ?>
                </fieldset>

                <fieldset>
                    <legend>Instruções e Descrição</legend>
                    <label for="modo_preparo">Modo de Preparo:</label>
                    <textarea id="modo_preparo" name="modo_preparo" placeholder="Modo de Preparo" rows="6" required><?= htmlspecialchars($receita_editar['modo_preparo'] ?? '') ?></textarea>

                    <label for="descricao_geral">Descrição Geral da Receita:</label>
                    <textarea id="descricao_geral" name="descricao_geral" placeholder="Descrição Geral"><?= htmlspecialchars($receita_editar['descricao'] ?? '') ?></textarea>
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
                <a href="../receitasChef.php" class="button-cancel"><button type="button">Cancelar</button></a>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let ingredienteCounter = <?= !empty($receita_editar['ingredientes_detalhes']) ? count($receita_editar['ingredientes_detalhes']) : 0 ?>;

            // Função para clonar e adicionar um novo item de ingrediente
            function addIngredienteItem() {
                const container = document.querySelector('.ingredientes-container');
                // Pega o último item para clonar e manter os selects preenchidos do original (vazios para o novo)
                const lastItem = container.querySelector('.ingrediente-item:last-child');
                const newItem = lastItem.cloneNode(true);

                // Limpar os valores dos inputs e selects do novo item
                newItem.querySelectorAll('input, select').forEach(el => {
                    if (el.tagName === 'SELECT' || el.type !== 'hidden') { // Não limpa hidden inputs
                        el.value = '';
                    }
                });

                // Atualizar IDs e nomes para serem únicos
                newItem.querySelectorAll('[id]').forEach(el => {
                    el.id = el.id.replace(/_(\d+)$/, '_' + ingredienteCounter);
                    const label = newItem.querySelector(`label[for="${el.id}"]`); // Atualiza o 'for' do label
                    if (label) label.setAttribute('for', el.id);
                });
                newItem.querySelectorAll('[name]').forEach(el => {
                    el.name = el.name.replace(/\[\d+\]$/, '[' + ingredienteCounter + ']'); // Atualiza o índice do array
                });

                // Mudar o botão "+" para um botão de remover "-"
                let addButton = newItem.querySelector('.adicionar-ingrediente');
                if (addButton) {
                    addButton.classList.replace('adicionar-ingrediente', 'remover-ingrediente');
                    addButton.textContent = '-';
                } else { // Se clonou um item que já era remover, garante que tenha um botão remover
                    let existingRemoverButton = newItem.querySelector('.remover-ingrediente');
                    if (!existingRemoverButton) {
                        // Cria um novo botão remover se não houver um
                        const newRemoverButton = document.createElement('button');
                        newRemoverButton.type = 'button';
                        newRemoverButton.classList.add('remover-ingrediente');
                        newRemoverButton.textContent = '-';
                        newItem.appendChild(newRemoverButton);
                    }
                }

                container.appendChild(newItem);
                ingredienteCounter++;
            }

            // Adiciona listener para o botão "Adicionar Ingrediente" e "Remover Ingrediente"
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
    </script>
</body>

</html>