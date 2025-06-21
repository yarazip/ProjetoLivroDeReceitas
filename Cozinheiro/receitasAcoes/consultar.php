<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../BancoDeDados/conexao.php'; // Caminho ajustado

$receita = null;
$ingredientes_receita = [];
$foto_receita = null;

if (isset($_GET['nome'])) {
    $nome_receita_param = $_GET['nome'];

    // Buscar receita principal
    $stmt = $conn->prepare("
        SELECT r.*, f.nome AS nome_funcionario, c.nome_categoria
        FROM receitas r
        JOIN funcionarios f ON r.id_funcionario = f.id_funcionario
        JOIN categorias c ON r.id_categoria = c.id_categoria
        WHERE r.nome_receita = ?
    ");
    $stmt->execute([$nome_receita_param]);
    $receita = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($receita) {
        // Buscar ingredientes e medidas da receita
        $stmtIngMed = $conn->prepare("
            SELECT ri.quantidade_ingrediente, ri.descricao AS descricao_ingrediente_extra,
                   i.nome AS nome_ingrediente, 
                   CONCAT(m.descricao, ' (', m.medida, ')') AS medida_completa
            FROM receita_ingrediente ri
            JOIN ingredientes i ON ri.id_ingrediente = i.id_ingrediente
            JOIN medidas m ON ri.id_medida = m.id_medida
            WHERE ri.nome_receita = ?
        ");
        $stmtIngMed->execute([$nome_receita_param]);
        $ingredientes_receita = $stmtIngMed->fetchAll(PDO::FETCH_ASSOC);

        // Buscar foto da receita
        $stmtFoto = $conn->prepare("SELECT tipo FROM foto_receita WHERE nome_receita = ? LIMIT 1");
        $stmtFoto->execute([$nome_receita_param]);
        $foto_receita = $stmtFoto->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['message'] = "Receita não encontrada para consulta.";
        $_SESSION['message_type'] = "error";
        header("Location: ../receitasChef.php");
        exit;
    }
} else {
    $_SESSION['message'] = "Parâmetro 'nome' da receita ausente para consulta.";
    $_SESSION['message_type'] = "error";
    header("Location: ../receitasChef.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <title>Detalhes da Receita</title>
    <link rel="stylesheet" href="../../styles/consultaREC.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="shortcut icon" href="../../assets/favicon.png" type="image/x-icon" />

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
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?= htmlspecialchars($_SESSION['nome_funcionario'] ?? 'Desconhecido') ?></span>
                </div>
            </nav>
        </div>

        <div class="main-content recipe-detail">
            <h2>Detalhes da Receita: <?= htmlspecialchars($receita['nome_receita']) ?></h2>

            <?php if ($foto_receita): ?>
                <div class="recipe-image-container">
                    <img src="data:image/jpeg;base64,<?= base64_encode($foto_receita['tipo']) ?>" alt="Foto da Receita" class="recipe-detail-image">
                </div>
            <?php else: ?>
                <p>Sem foto disponível para esta receita.</p>
            <?php endif; ?>

            <div class="recipe-info">
                <p><strong>Cozinheiro:</strong> <?= htmlspecialchars($receita['nome_funcionario']) ?></p>
                <p><strong>Data de Criação:</strong> <?= htmlspecialchars($receita['data_criacao']) ?></p>
                <p><strong>Categoria:</strong> <?= htmlspecialchars($receita['nome_categoria']) ?></p>
                <p><strong>Porções:</strong> <?= htmlspecialchars($receita['porcoes']) ?></p>
                <p><strong>Tempo de Preparo:</strong> <?= htmlspecialchars($receita['tempo_preparo']) ?></p>
                <p><strong>Dificuldade:</strong> <?= htmlspecialchars($receita['dificuldade']) ?></p>

                <h3>Ingredientes:</h3>
                <ul>
                    <?php if (!empty($ingredientes_receita)): ?>
                        <?php foreach ($ingredientes_receita as $ing): ?>
                            <li>
                                <?= htmlspecialchars($ing['quantidade_ingrediente']) ?>
                                <?= htmlspecialchars($ing['medida_completa']) ?> de
                                <strong><?= htmlspecialchars($ing['nome_ingrediente']) ?></strong>
                                <?php if (!empty($ing['descricao_ingrediente_extra'])): ?>
                                    (<?= htmlspecialchars($ing['descricao_ingrediente_extra']) ?>)
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>Nenhum ingrediente listado.</li>
                    <?php endif; ?>
                </ul>

                <h3>Modo de Preparo:</h3>
                <p class="preparo-text"><?= nl2br(htmlspecialchars($receita['modo_preparo'])) ?></p>

                <?php if (!empty($receita['descricao'])): ?>
                    <h3>Descrição:</h3>
                    <p><?= nl2br(htmlspecialchars($receita['descricao'])) ?></p>
                <?php endif; ?>
            </div>

            <a href="../receitasChef.php" class="button back-button">Voltar para a Lista de Receitas</a>
        </div>
    </div>
</body>

</html>