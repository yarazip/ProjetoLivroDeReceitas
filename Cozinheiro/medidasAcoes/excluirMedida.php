<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../BancoDeDados/conexao.php';

// Verifica se o usuário está logado e se tem permissão (Cozinheiro ou Administrador)
if (!isset($_SESSION['id_login']) || ($_SESSION['cargo'] !== 'Cozinheiro' && $_SESSION['cargo'] !== 'Administrador')) {
    $_SESSION['message'] = "Você não tem permissão para realizar esta ação.";
    $_SESSION['message_type'] = "error";
    header("Location: ../../LoginSenha/login.php");
    exit;
}

$id_medida_para_excluir = $_GET['id'] ?? null;
$confirmacao = $_GET['confirmar'] ?? null;

// A exclusão só acontece se o ID da medida e a confirmação 'sim' forem enviados
if (is_null($id_medida_para_excluir) || $confirmacao !== 'sim') {
    $_SESSION['message'] = "Ação de exclusão de medida inválida ou não confirmada.";
    $_SESSION['message_type'] = "error";
    header("Location: ../medidasChef.php");
    exit;
}

$conn->beginTransaction();
try {
    // Verificar dependências na tabela 'receita_ingrediente'
    $stmt_check_deps = $conn->prepare("SELECT COUNT(*) FROM receita_ingrediente WHERE id_medida = ?");
    $stmt_check_deps->execute([$id_medida_para_excluir]);
    $num_dependencias = $stmt_check_deps->fetchColumn();

    if ($num_dependencias > 0) {
        $conn->rollBack();
        $_SESSION['message'] = "Não foi possível excluir a medida. Ela está sendo usada em " . $num_dependencias . " ingrediente(s) de receita.";
        $_SESSION['message_type'] = "error";
    } else {
        // Se não houver dependências, procede com a exclusão
        $sql = "DELETE FROM medidas WHERE id_medida = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id_medida_para_excluir]);
        $conn->commit();
        $_SESSION['message'] = "Medida excluída com sucesso!";
        $_SESSION['message_type'] = "success";
    }
} catch (PDOException $e) {
    $conn->rollBack();
    $_SESSION['message'] = "Erro ao excluir medida: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
    error_log("Erro ao excluir medida: " . $e->getMessage());
}
header("Location: ../medidasChef.php"); // Redireciona para a página principal após a exclusão
exit;
?>