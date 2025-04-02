<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../conexao.php';

// Variáveis de controle
$error = '';
$success = '';
$edicaoAtiva = false;
$avaliacaoEditando = null;

// Processar operações
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Modo edição
    if (isset($_GET['editar'])) {
        $id = intval($_GET['editar']);
        $stmt = $conn->prepare("SELECT * FROM Avaliacao WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $avaliacaoEditando = $stmt->fetch(PDO::FETCH_ASSOC);
        $edicaoAtiva = ($avaliacaoEditando !== false);
    }
    
    // Exclusão de avaliação
    if (isset($_GET['excluir'])) {
        $id = intval($_GET['excluir']);
        try {
            $stmt = $conn->prepare("DELETE FROM Avaliacao WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $success = "Avaliação excluída com sucesso!";
        } catch (Exception $e) {
            $error = "Erro ao excluir avaliação: " . $e->getMessage();
        }
    }
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $degustador_email = trim($_POST['degustador_email']);
    $receita_id = intval($_POST['receita_id']);
    $nota = floatval($_POST['nota']);
    $comentario = trim($_POST['comentario']);
    
    try {
        if ($edicaoAtiva && isset($_POST['editar_id'])) {
            // Modo edição
            $id = intval($_POST['editar_id']);
            $stmt = $conn->prepare("UPDATE Avaliacao SET 
                                  receita_id = :receita_id,
                                  degustador_email = :degustador_email,
                                  nota = :nota,
                                  comentario = :comentario
                                  WHERE id = :id");
            $stmt->execute([
                ':receita_id' => $receita_id,
                ':degustador_email' => $degustador_email,
                ':nota' => $nota,
                ':comentario' => $comentario,
                ':id' => $id
            ]);
            $success = "Avaliação atualizada com sucesso!";
        } else {
            // Modo adição
            $stmt = $conn->prepare("INSERT INTO Avaliacao 
                                  (receita_id, degustador_email, nota, comentario) 
                                  VALUES 
                                  (:receita_id, :degustador_email, :nota, :comentario)");
            $stmt->execute([
                ':receita_id' => $receita_id,
                ':degustador_email' => $degustador_email,
                ':nota' => $nota,
                ':comentario' => $comentario
            ]);
            $success = "Avaliação registrada com sucesso!";
        }
        header("Location: receitasDegustador.php?success=1");
        exit();
    } catch (Exception $e) {
        $error = "Erro ao salvar avaliação: " . $e->getMessage();
    }
}

// Obter receitas para dropdown
$receitas = $conn->query("SELECT r.id, r.nome, u.nome AS cozinheiro_nome 
                         FROM Receita r
                         JOIN Cozinheiro c ON r.cozinheiro_email = c.email
                         JOIN Usuario u ON c.email = u.email
                         ORDER BY r.nome")
                ->fetchAll(PDO::FETCH_ASSOC);

// Obter avaliações
$avaliacoes = $conn->query("SELECT a.*, r.nome AS receita_nome, u.nome AS cozinheiro_nome 
                           FROM Avaliacao a
                           JOIN Receita r ON a.receita_id = r.id
                           JOIN Cozinheiro c ON r.cozinheiro_email = c.email
                           JOIN Usuario u ON c.email = u.email
                           ORDER BY a.id DESC")
                  ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/func.css">
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
    <title>Avaliações de Receitas</title>
    <style>
        .insert-bar {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }
        .insert-bar input, 
        .insert-bar select, 
        .insert-bar textarea {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .insert-bar button {
            padding: 10px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="menu">
            <h1 class="logo">Código de Sabores</h1>
            <nav>
                <a href="receitasDegustador.php">Receitas</a>
            </nav>
        </div>

        <?php if ($error): ?>
            <div style="color: red; padding: 10px;"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($success): ?>
            <div style="color: green; padding: 10px;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" class="insert-bar">
            <?php if ($edicaoAtiva): ?>
                <input type="hidden" name="editar_id" value="<?= $avaliacaoEditando['id'] ?>">
            <?php endif; ?>
            
            <input type="email" name="degustador_email" 
                   placeholder="Email do degustador" required
                   value="<?= $edicaoAtiva ? htmlspecialchars($avaliacaoEditando['degustador_email']) : '' ?>">
            
            <select name="receita_id" required>
                <option value="">Selecione a receita</option>
                <?php foreach ($receitas as $receita): ?>
                    <option value="<?= $receita['id'] ?>"
                        <?= ($edicaoAtiva && $receita['id'] == $avaliacaoEditando['receita_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($receita['nome']) ?> (<?= htmlspecialchars($receita['cozinheiro_nome']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input type="number" name="nota" min="0" max="10" step="0.1"
                   placeholder="Nota (0-10)" required
                   value="<?= $edicaoAtiva ? htmlspecialchars($avaliacaoEditando['nota']) : '' ?>">
            
            <textarea name="comentario" placeholder="Comentário"><?= 
                $edicaoAtiva ? htmlspecialchars($avaliacaoEditando['comentario']) : '' 
            ?></textarea>
            
            <button type="submit"><?= $edicaoAtiva ? 'Atualizar' : 'Adicionar' ?></button>
            
            <?php if ($edicaoAtiva): ?>
                <a href="receitasDegustador.php" style="padding: 10px; background: #ccc; text-align: center;">Cancelar</a>
            <?php endif; ?>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Receita</th>
                    <th>Chef</th>
                    <th>Degustador</th>
                    <th>Nota</th>
                    <th>Comentário</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($avaliacoes as $avaliacao): ?>
                <tr>
                    <td><?= htmlspecialchars($avaliacao['receita_nome']) ?></td>
                    <td><?= htmlspecialchars($avaliacao['cozinheiro_nome']) ?></td>
                    <td><?= htmlspecialchars($avaliacao['degustador_email']) ?></td>
                    <td><?= number_format($avaliacao['nota'], 1) ?></td>
                    <td><?= htmlspecialchars($avaliacao['comentario']) ?></td>
                    <td>
                        <a href="receitasDegustador.php?editar=<?= $avaliacao['id'] ?>">Editar</a>
                        <a href="receitasDegustador.php?excluir=<?= $avaliacao['id'] ?>" 
                           onclick="return confirm('Tem certeza?')">Excluir</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>