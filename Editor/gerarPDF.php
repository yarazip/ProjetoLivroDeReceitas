gerar.pdf

<?php
require_once '../BancoDeDados/conexao.php';
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$id_livro = $_POST['id_livro'] ?? $_GET['id_livro'] ?? null;
if (!$id_livro) {
    die("Livro não especificado.");
}


// Buscar dados do livro
$sqlLivro = "SELECT * FROM livros WHERE id_livro = ?";
$stmtLivro = $conn->prepare($sqlLivro);
$stmtLivro->execute([$id_livro]);
$livro = $stmtLivro->fetch(PDO::FETCH_ASSOC);

if (!$livro) {
    die("Livro não encontrado.");
}

// Buscar receitas associadas ao livro
$sqlReceitas = "SELECT r.nome_receita, r.modo_preparo, r.tempo_preparo, r.porcoes, r.dificuldade, r.descricao 
                FROM receitas r 
                INNER JOIN livro_receita lr ON r.nome_receita = lr.nome_receita 
                WHERE lr.id_livro = ?";
$stmtReceitas = $conn->prepare($sqlReceitas);
$stmtReceitas->execute([$id_livro]);
$receitas = $stmtReceitas->fetchAll(PDO::FETCH_ASSOC);

// Opções do Dompdf
$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);

// HTML com estilo do protótipo
$html = '
<meta charset="UTF-8">
<style>
    body {
        font-family: "DejaVu Sans", serif;
        font-size: 12pt;
        text-align: justify;
        line-height: 1.5;
        margin: 40px;
    }
    h1 {
        text-align: center;
        font-size: 20pt;
        font-weight: bold;
        margin-bottom: 30px;
    }
    h2 {
        font-size: 16pt;
        font-weight: bold;
        margin-top: 25px;
        text-align: center;
    }
    h3 {
        font-size: 14pt;
        font-weight: bold;
        margin-top: 15px;
    }
    p {
        margin: 4px 0;
    }
    hr {
        margin: 20px 0;
    }
    .page-break {
        page-break-after: always;
    }
</style>
';

// Cabeçalho do livro
$html .= '<h1>' . htmlspecialchars($livro['titulo']) . '</h1>';
$html .= '<p><strong>ISBN:</strong> ' . htmlspecialchars($livro['isbn']) . '</p>';
$html .= '<p><strong>Descrição:</strong> ' . htmlspecialchars($livro['descricao']) . '</p>';

$html .= '<h2>Receitas</h2>';

// Receitas
foreach ($receitas as $r) {
    $html .= '<h3>' . htmlspecialchars($r['nome_receita']) . '</h3>';
    $html .= '<p><strong>Modo de Preparo:</strong> ' . nl2br(htmlspecialchars($r['modo_preparo'])) . '</p>';
    $html .= '<p><strong>Tempo de Preparo:</strong> ' . htmlspecialchars($r['tempo_preparo']) . ' minutos</p>';
    $html .= '<p><strong>Porções:</strong> ' . htmlspecialchars($r['porcoes']) . '</p>';
    $html .= '<p><strong>Dificuldade:</strong> ' . htmlspecialchars($r['dificuldade']) . '</p>';
    $html .= '<p><strong>Descrição:</strong> ' . htmlspecialchars($r['descricao']) . '</p>';
    $html .= '<div class="page-break"></div>';
}

// Gerar e exibir PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Livro_" . $livro['id_livro'] . ".pdf", ["Attachment" => false]);
exit;

