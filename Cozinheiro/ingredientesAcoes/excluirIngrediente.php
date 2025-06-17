<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../BancoDeDados/conexao.php';

// Verifica se o usuário está logado e se tem permissão (Cozinheiro ou Administrador)
if (!isset($_SESSION['id_login']) || ($_SESSION['cargo'] !== 'cozinheiro' && $_SESSION['cargo'] !== 'administrador')) {
    $_SESSION['message'] = "Você não tem permissão para realizar esta ação.";
    $_SESSION['message_type'] = "error";
    header("Location: ../../LoginSenha/login.php");
    exit;
}

$id_ingrediente_para_excluir = $_GET['id'] ?? null;
$confirmacao = $_GET['confirmar'] ?? null;

// A exclusão só acontece se o ID do ingrediente e a confirmação 'sim' forem enviados
if (is_null($id_ingrediente_para_excluir) || $confirmacao !== 'sim') {
    $_SESSION['message'] = "Ação de exclusão de ingrediente inválida ou não confirmada.";
    $_SESSION['message_type'] = "error";
    header("Location: ../ingredientesChef.php");
    exit;
}

$conn->beginTransaction();
try {
    // Verificar dependências na tabela 'receita_ingrediente'
    $stmt_check_deps = $conn->prepare("SELECT COUNT(*) FROM receita_ingrediente WHERE id_ingrediente = ?");
    $stmt_check_deps->execute([$id_ingrediente_para_excluir]);
    $num_dependencias = $stmt_check_deps->fetchColumn();

    if ($num_dependencias > 0) {
        $conn->rollBack();
        $_SESSION['message'] = "Não foi possível excluir o ingrediente. Ele está sendo usado em " . $num_dependencias . " receita(s).";
        $_SESSION['message_type'] = "error";
    } else {
        // Se não houver dependências, procede com a exclusão
        $sql = "DELETE FROM ingredientes WHERE id_ingrediente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id_ingrediente_para_excluir]);
        $conn->commit();
        $_SESSION['message'] = "Ingrediente excluído com sucesso!";
        $_SESSION['message_type'] = "success";
    }
} catch (PDOException $e) {
    $conn->rollBack();
    $_SESSION['message'] = "Erro ao excluir ingrediente: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
    error_log("Erro ao excluir ingrediente: " . $e->getMessage());
}
header("Location: ../ingredientesChef.php"); // Redireciona para a página principal após a exclusão
exit;
?>