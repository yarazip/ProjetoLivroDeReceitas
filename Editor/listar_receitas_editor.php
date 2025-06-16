<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../BancoDeDados/conexao.php';

// Check if user is logged in and is an Editor
if (!isset($_SESSION['id_login']) || $_SESSION['cargo'] !== 'Editor') {
    $_SESSION['message'] = "Você não tem permissão para acessar esta página.";
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

// SQL query to get recipes linked to the logged-in editor
// Assuming there is a table 'livros' with 'id_editor' linking books to editors
// and 'receitas' linked to 'livros' via 'id_livro'
// Also joining cozinheiros, degustadores, avaliacoes tables

$sql = "
SELECT 
    r.nome_receita,
    c.nome AS nome_cozinheiro,
    d.nome AS nome_degustador,
    a.nota AS avaliacao_nota,
    a.comentario AS avaliacao_comentario
FROM receitas r
JOIN livros l ON r.id_livro = l.id_livro
JOIN funcionarios c ON r.id_funcionario = c.id_funcionario
LEFT JOIN avaliacoes a ON a.id_receita = r.id_receita
LEFT JOIN degustadores d ON a.id_degustador = d.id_degustador
WHERE l.id_editor = ?
ORDER BY r.nome_receita ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute([$id_editor]);
$receitas = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <title>Listar Receitas - Editor</title>
    <link rel="stylesheet" href="../styles/func.css" />
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon" />
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:nth-child(even){background-color: #f2f2f2;}
    </style>
</head>
<body>
<div class="container">
    <h1>Receitas do Editor</h1>
    <?php if (count($receitas) === 0): ?>
        <p>Nenhuma receita encontrada para este editor.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Nome da Receita</th>
                    <th>Cozinheiro</th>
                    <th>Degustador</th>
                    <th>Avaliação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($receitas as $receita): ?>
                    <tr>
                        <td><?= htmlspecialchars($receita['nome_receita']) ?></td>
                        <td><?= htmlspecialchars($receita['nome_cozinheiro']) ?></td>
                        <td><?= htmlspecialchars($receita['nome_degustador'] ?? 'N/A') ?></td>
                        <td>
                            <?php
                                if ($receita['avaliacao_nota'] !== null) {
                                    echo "Nota: " . htmlspecialchars($receita['avaliacao_nota']);
                                    if (!empty($receita['avaliacao_comentario'])) {
                                        echo "<br>Comentário: " . htmlspecialchars($receita['avaliacao_comentario']);
                                    }
                                } else {
                                    echo "Sem avaliação";
                                }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
