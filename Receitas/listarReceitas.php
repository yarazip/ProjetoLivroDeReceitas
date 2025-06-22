<?php
$conn = new mysqli("localhost", "root", "yara123", "teste_trabalho_1", 3307);

if ($conn->connect_error) {
    die("Erro ao conectar ao banco de dados: " . $conn->connect_error);
}

// Consulta modificada para verificar se existe foto associada
$sql = "
    SELECT r.nome_receita, f.nome AS autor, c.nome_categoria,
           (SELECT COUNT(*) FROM foto_receita fr WHERE fr.nome_receita = r.nome_receita) AS tem_foto
    FROM receitas r
    JOIN funcionarios f ON r.id_funcionario = f.id_funcionario
    JOIN categorias c ON r.id_categoria = c.id_categoria
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="script.js"></script>
    <link rel="stylesheet" href="../styles/exibirReceitas.css">
    <link rel="stylesheet" href="../styles/listarReceitas.css">
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Receitas | Código de Sabores</title>
    <script src="../js/Receitas.js"></script>
    <style>
        .recipe-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        .no-image {
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #777;
        }
    </style>
</head>
<body class="light-mode">
    <header>
        <h1 class="logo">Código de Sabores</h1>
        <nav class="nav-links">
            <!-- <a href="mainview.html">Sobre Nós</a> -->
            <!-- <a href="mainview.html">Fale Conosco</a> -->
        </nav>
        <div class="header-right">
            <button id="theme-toggle" class="theme-toggle" aria-label="Alternar tema">
                <i class="fas fa-moon"></i>
                <i class="fas fa-sun"></i>
            </button>
            <div class="header-buttons">
                <!-- <a href="../loginSenha/login.html" class="get-started-btn">Entrar</a> -->
            </div>
        </div>
    </header>

    <section class="recipe-hero">
        <h1>Descubra Novas Receitas</h1>
        <img src="../assets/dish.png" alt="">
        <p>Encontre inspiração para suas próximas refeições</p>
    </section>

    <div class="recipes-container">
        <div class="recipes-filters">
            <a href="../Receitas/listarReceitas.php" class="filter-btn active">Todas</a>
        </div>
    </div>
    
    <div class="featured-recipes">
        <h2 class="section-title">Todas as Receitas</h2>
        <div class="recipes-grid">
            <?php
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $nomeUrl = urlencode($row['nome_receita']);
                    $temFoto = $row['tem_foto'] > 0;
                    
                    echo '
                    <div class="recipe-card">
                        <a href="verReceita.php?nome='.$nomeUrl.'">
                            '.($temFoto ? 
                                '<img src="exibirImagem.php?nome='.$nomeUrl.'" alt="'.$row['nome_receita'].'" class="recipe-img">' : 
                                '<div class="recipe-img no-image"><i class="fas fa-camera fa-2x"></i><span>Sem imagem</span></div>').'
                        </a>
                        <div class="recipe-info">
                            <h3 class="recipe-title"><a href="verReceita.php?nome='.$nomeUrl.'">'.$row['nome_receita'].'</a></h3>
                            <div class="recipe-meta">
                                <span><i class="fas fa-user"></i> '.$row['autor'].'</span>
                            </div>
                            <div class="recipe-categories">
                                <span class="category-tag">'.$row['nome_categoria'].'</span>
                            </div>
                        </div>
                    </div>';
                }
            } else {
                echo '<p class="no-recipes">Nenhuma receita cadastrada.</p>';
            }
            $conn->close();
            ?>
        </div>
    </div>

    <footer>
        <div class="footer-grid">
            <div class="footer-column">
                <h3>Código de Sabores</h3>
                <ul>
                    <li><a href="#">Sobre Nós</a></li>
                    <li><a href="#">Nossa Equipe</a></li>
                    <li><a href="#">Carreiras</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Recursos</h3>
                <ul>
                    <li><a href="#">Receitas</a></li>
                    <li><a href="#">Categorias</a></li>
                    <li><a href="#">Tutoriais</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Legal</h3>
                <ul>
                    <li><a href="#">Termos de Uso</a></li>
                    <li><a href="#">Política de Privacidade</a></li>
                    <li><a href="#">Cookies</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Contato</h3>
                <ul>
                    <li><a href="#">Fale Conosco</a></li>
                    <li><a href="#">Suporte</a></li>
                    <li><a href="#">Redes Sociais</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; '.date('Y').' Código de Sabores. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>