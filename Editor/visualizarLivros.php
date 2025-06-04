<?php
require_once '../BancoDeDados/conexao.php';
require_once '../dompdf/autoload.inc.php';

// Buscar livros com receitas associadas
$sql = "
    SELECT l.id_livro, l.titulo, l.isbn, l.descricao, r.nome_receita
    FROM livros l
    LEFT JOIN livro_receita lr ON l.id_livro = lr.id_livro
    LEFT JOIN receitas r ON lr.nome_receita = r.nome_receita
    ORDER BY l.id_livro, r.nome_receita
";
$stmt = $conn->query($sql);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar receitas por livro
$livros = [];
foreach ($resultados as $linha) {
    $id = $linha['id_livro'];
    if (!isset($livros[$id])) {
        $livros[$id] = [
            'titulo' => $linha['titulo'],
            'isbn' => $linha['isbn'],
            'descricao' => $linha['descricao'],
            'receitas' => []
        ];
    }
    if ($linha['nome_receita']) {
        $livros[$id]['receitas'][] = $linha['nome_receita'];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Visualizar Livros</title>
    <link rel="stylesheet" href="../styles/func.css">
    <link rel="shortcut icon" href="../assets/favicon.png">
</head>
<body>
    <div class="container">
        <!-- Menu padrão do sistema -->
        <div class="menu">
            <h1 class="logo">Código de Sabores</h1>
            <nav>
                <a href="livrosEditor.php">Livros</a>
                <a href="visualizarLivros.php">Gerar PDF</a>
            </nav>
        </div>

        <!-- Conteúdo principal -->
        <h2>Livros com Receitas</h2>

        <table border="1" cellpadding="8" cellspacing="0">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>ISBN</th>
                    <th>Descrição</th>
                    <th>Receitas</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($livros as $id_livro => $livro): ?>
                    <tr>
                        <td><?= htmlspecialchars($livro['titulo']) ?></td>
                        <td><?= htmlspecialchars($livro['isbn']) ?></td>
                        <td><?= htmlspecialchars($livro['descricao']) ?></td>
                        <td>
                            <ul>
                                <?php foreach ($livro['receitas'] as $receita): ?>
                                    <li><?= htmlspecialchars($receita) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                        <td>
                            <form action="gerarPDF.php" method="post" target="_blank">
                                <input type="hidden" name="id_livro" value="<?= $id_livro ?>">
                                <button type="submit">Gerar PDF</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
