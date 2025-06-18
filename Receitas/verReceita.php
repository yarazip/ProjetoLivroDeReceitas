<?php
// Conexão
$conexao = new mysqli("localhost", "root", "yara123", "teste_trabalho_1", 3307);

if ($conexao->connect_error) {
    die("Erro na conexão: " . $conexao->connect_error);
}

// Sanitize input
$nome_receita = htmlspecialchars($_GET['nome'] ?? '');

if (empty($nome_receita)) {
    die("Nome da receita não especificado.");
}

// Get recipe details
$stmt = $conexao->prepare("SELECT r.*, f.nome as autor_nome 
                          FROM receitas r 
                          JOIN funcionarios f ON r.id_funcionario = f.id_funcionario 
                          WHERE r.nome_receita = ?");
if (!$stmt) {
    die("Erro na preparação da consulta: " . $conexao->error);
}

$stmt->bind_param("s", $nome_receita);
if (!$stmt->execute()) {
    die("Erro ao executar a consulta: " . $stmt->error);
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Receita não encontrada.");
}

$receita = $result->fetch_assoc();
$stmt->close();

// Check for photo - MODIFICADO PARA BUSCAR O BLOB DIRETAMENTE
$foto_data = null;
$foto_type = null;
$foto_stmt = $conexao->prepare("SELECT tipo FROM foto_receita WHERE nome_receita = ? LIMIT 1");
if ($foto_stmt) {
    $foto_stmt->bind_param("s", $nome_receita);
    if ($foto_stmt->execute()) {
        $foto_result = $foto_stmt->get_result();
        if ($foto_result->num_rows > 0) {
            $foto = $foto_result->fetch_assoc();
            $foto_data = $foto['tipo'];
            // Se você armazenou o tipo de imagem, descomente a linha abaixo
            // $foto_type = $foto['tipo_imagem']; // Você precisaria ter este campo na tabela
        }
    }
    $foto_stmt->close();
}

$conexao->close();

// Determina se tem foto
$tem_foto = !is_null($foto_data);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($receita['nome_receita']) ?> | Código de Sabores</title>
        <script src="script.js"></script>

    <link rel="stylesheet" href="../styles/exibirReceitas.css">
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body class="light-mode">
    <header>
        <h1 class="logo">Código de Sabores</h1>
        <nav class="nav-links">
            <a href="../mainview.html">Home</a>
            <a href="../Receitas/listarReceitas.php">Receitas</a>
        </nav>
        <div class="header-right">
            <button id="theme-toggle" class="theme-toggle"><i class="fas fa-moon"></i><i class="fas fa-sun"></i></button>
            <div class="header-buttons">
                <a href="../loginSenha/login.html" class="get-started-btn">Entrar</a>
            </div>
        </div>
    </header>

    <section class="recipe-hero">
        <?php if ($tem_foto): ?>
    <img src="data:image/jpeg;base64,<?= base64_encode($foto_data) ?>" 
         alt="Foto da Receita <?= htmlspecialchars($nome_receita) ?>" ">
<?php else: ?>
    <p>Foto não disponível.</p>
<?php endif; ?>


        <h1><?= htmlspecialchars($receita['nome_receita']) ?></h1>
        <p><?= nl2br(htmlspecialchars($receita['descricao'] ?? '')) ?></p>
    </section>

    <div class="recipe-container">
        <main class="recipe-content">
            <article>
                <h2><?= htmlspecialchars($receita['nome_receita']) ?></h2>
               <?php if ($tem_foto): ?>
    <img src="data:image/jpeg;base64,<?= base64_encode($foto_data) ?>" 
         alt="Foto da Receita <?= htmlspecialchars($nome_receita) ?>" 
         style="max-width: 400px;">
<?php else: ?>
    <p>Foto não disponível.</p>
<?php endif; ?>


                <h3>Ingredientes</h3>
                <ul class="ingredients-list">
                    <?php
                    // Verifica se existem ingredientes e se não está vazio
                    if (!empty($receita['ingredientes'])) {
                        // Divide os ingredientes por quebra de linha
                        $ingredientes = explode("\n", $receita['ingredientes']);
                        foreach ($ingredientes as $ingrediente) {
                            if (!empty(trim($ingrediente))) {
                                echo "<li>" . htmlspecialchars(trim($ingrediente)) . "</li>";
                            }
                        }
                    } else {
                        echo "<li>Nenhum ingrediente listado</li>";
                    }
                    ?>

                </ul>

                <h3>Modo de Preparo</h3>
                <ol class="instructions-list">
                    <?php
                    // Divide o modo de preparo por quebra de linha
                    $etapas = explode("\n", $receita['modo_preparo']);
                    foreach ($etapas as $etapa):
                        if (!empty(trim($etapa))): ?>
                            <li><?= htmlspecialchars(trim($etapa)) ?></li>
                    <?php endif;
                    endforeach;
                    ?>
                </ol>

                <?php if (!empty($receita['descricao'])): ?>
                    <h3>Descrição</h3>
                    <p><?= nl2br(htmlspecialchars($receita['descricao'])) ?></p>
                <?php endif; ?>
            </article>
        </main>

        <aside class="recipe-sidebar">
            <div class="recipe-info-card">
                <h3>Informações</h3>
                <div class="info-item"><i class="fas fa-clock"></i><span>Tempo de preparo: <?= htmlspecialchars($receita['tempo_preparo']) ?></span></div>
                <div class="info-item"><i class="fas fa-utensils"></i><span>Porções: <?= htmlspecialchars($receita['porcoes']) ?></span></div>
                <div class="info-item"><i class="fas fa-fire"></i><span>Dificuldade: <?= htmlspecialchars($receita['dificuldade']) ?></span></div>
                <div class="info-item"><i class="fas fa-user"></i><span>Cozinheiro: <?= htmlspecialchars($receita['autor_nome']) ?></span></div>
                <div class="info-item"><i class="fas fa-calendar"></i><span>Data de criação: <?= htmlspecialchars($receita['data_criacao']) ?></span></div>
            </div>

            <div class="recipe-info-card">
                <h3>Compartilhe</h3>
                <div class="redes">
                    <a href="#"><i class="fab fa-facebook fa-2x"></i></a>
                    <a href="#"><i class="fab fa-instagram fa-2x"></i></a>
                    <a href="#"><i class="fab fa-whatsapp fa-2x"></i></a>
                </div>
            </div>
        </aside>
    </div>

    <footer>
        <div class="footer-grid">
            <div class="footer-column">
                <h3>Empresa</h3>
                <ul>
                    <li><a href="../sobre/sobre.html">Sobre</a></li>
                    <li><a href="#">Carreiras</a></li>
                    <li><a href="#">Imprensa</a></li>
                    <li><a href="../contato/contato.html">Contato</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Suporte</h3>
                <ul>
                    <li><a href="#">Central de Ajuda</a></li>
                    <li><a href="#">Plataforma de Desenvolvedor</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Siga</h3>
                <ul>
                    <li><a href="#">Instagram</a></li>
                    <li><a href="#">Facebook</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 Código De Sabores, Inc. Todos os direitos reservados.</p>
            <div class="social-icons">
                <a href="#">Privacidade</a>
                <a href="#">Termos</a>
                <a href="#">Cookies</a>
            </div>
        </div>
    </footer>

    <script src="../js/PagReceita.js"></script>
</body>

</html>