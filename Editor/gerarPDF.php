<?php
require_once '../BancoDeDados/conexao.php';
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_POST['id_livro'])) {
    die("Livro não especificado.");
}

$id_livro = (int)$_POST['id_livro'];

// Buscar dados do livro com data_publicacao e info do editor
$sqlLivro = "SELECT l.*, f.nome AS nome_editor, f.email AS email_editor
             FROM livros l
             LEFT JOIN funcionarios f ON l.id_editor = f.id_funcionario
             WHERE l.id_livro = ?";
$stmtLivro = $conn->prepare($sqlLivro);
$stmtLivro->execute([$id_livro]);
$livro = $stmtLivro->fetch(PDO::FETCH_ASSOC);

if (!$livro) {
    die("Livro não encontrado.");
}

// Buscar receitas associadas ao livro com cozinheiro, degustador e avaliação
$sqlReceitas = "
SELECT 
    r.nome_receita, r.modo_preparo, r.tempo_preparo, r.porcoes, r.dificuldade, r.descricao,
    c.nome AS nome_cozinheiro,
    d.nome AS nome_degustador,
    a.nota AS avaliacao_nota,
    a.comentario AS avaliacao_comentario
FROM receitas r
INNER JOIN livro_receita lr ON r.nome_receita = lr.nome_receita
LEFT JOIN funcionarios c ON r.id_funcionario = c.id_funcionario
LEFT JOIN avaliacoes a ON a.id_receita = r.id_receita
LEFT JOIN degustadores d ON a.id_degustador = d.id_degustador
WHERE lr.id_livro = ?
";
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
        margin-bottom: 10px;
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
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
        vertical-align: top;
    }
    th {
        background-color: #4CAF50;
        color: white;
    }
    .page-break {
        page-break-after: always;
    }
</style>
';

// Cabeçalho do livro com data de publicação e editor
$html .= '<h1>' . htmlspecialchars($livro['titulo']) . '</h1>';
$html .= '<p><strong>ISBN:</strong> ' . htmlspecialchars($livro['isbn']) . '</p>';
$html .= '<p><strong>Data de Publicação:</strong> ' . htmlspecialchars($livro['data_publicacao']) . '</p>';
$html .= '<p><strong>Editor Responsável:</strong> ' . htmlspecialchars($livro['nome_editor']) . ' (' . htmlspecialchars($livro['email_editor']) . ')</p>';

$html .= '<h2>Receitas</h2>';

// Receitas em tabela
$html .= '<table>';
$html .= '<thead><tr>
            <th>Nome da Receita</th>
            <th>Cozinheiro</th>
            <th>Degustador</th>
            <th>Avaliação</th>
          </tr></thead><tbody>';

foreach ($receitas as $r) {
    $avaliacao = 'Sem avaliação';
    if ($r['avaliacao_nota'] !== null) {
        $avaliacao = 'Nota: ' . htmlspecialchars($r['avaliacao_nota']);
        if (!empty($r['avaliacao_comentario'])) {
            $avaliacao .= '<br>Comentário: ' . htmlspecialchars($r['avaliacao_comentario']);
        }
    }
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars($r['nome_receita']) . '</td>';
    $html .= '<td>' . htmlspecialchars($r['nome_cozinheiro'] ?? 'N/A') . '</td>';
    $html .= '<td>' . htmlspecialchars($r['nome_degustador'] ?? 'N/A') . '</td>';
    $html .= '<td>' . $avaliacao . '</td>';
    $html .= '</tr>';
}

$html .= '</tbody></table>';

// Gerar e exibir PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Livro_" . $livro['id_livro'] . ".pdf", ["Attachment" => false]);
exit;
