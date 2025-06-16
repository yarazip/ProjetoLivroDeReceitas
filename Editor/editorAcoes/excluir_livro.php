<?php
require_once '../BancoDeDados/conexao.php';
require_once '../dompdf/autoload.inc.php';

// Verifica se o método é POST, por segurança
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: visualizarLivros.php");
    exit;
}

// MODIFICADO: Pega o ID via POST em vez de GET
$id_livro = filter_input(INPUT_POST, 'id_livro', FILTER_VALIDATE_INT);

if (!$id_livro) {
    set_flash_message("ID inválido para exclusão.", 'error');
    header("Location: visualizarLivros.php");
    exit;
}

try {
    $conn->beginTransaction();

    // Deleta os registros na tabela de associação primeiro
    $stmt1 = $conn->prepare("DELETE FROM livro_receita WHERE id_livro = ?");
    $stmt1->execute([$id_livro]);

    // Deleta o livro da tabela principal
    $stmt2 = $conn->prepare("DELETE FROM livros WHERE id_livro = ?");
    $stmt2->execute([$id_livro]);

    // Confirma se alguma linha foi realmente deletada na tabela de livros
    if ($stmt2->rowCount() > 0) {
        $conn->commit();
        set_flash_message("Livro excluído com sucesso!");
    } else {
        $conn->rollBack();
        set_flash_message("Livro não encontrado ou já excluído.", 'error');
    }
    
} catch (Exception $e) {
    $conn->rollBack();
    set_flash_message("Erro ao excluir o livro: " . $e->getMessage(), 'error');
}

// Redireciona de volta para a lista
header("Location: visualizarLivros.php");
exit;