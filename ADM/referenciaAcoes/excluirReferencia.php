<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../BancoDeDados/conexao.php';

// Verifica se o usuário está logado como Administrador
if (!isset($_SESSION['id_login']) || $_SESSION['cargo'] !== 'administrador') {
    $_SESSION['message'] = "Você não tem permissão para realizar esta ação.";
    $_SESSION['message_type'] = "error";
    header("Location: ../../LoginSenha/login.php");
    exit;
}

$id_func_excluir = $_GET['id_func'] ?? null;
$id_rest_excluir = $_GET['id_rest'] ?? null;
$confirmacao = $_GET['confirmar'] ?? null;

// A exclusão só acontece se ambos os IDs e a confirmação 'sim' forem enviados
if (is_null($id_func_excluir) || is_null($id_rest_excluir) || $confirmacao !== 'sim') {
    $_SESSION['message'] = "Ação de exclusão de referência inválida ou não confirmada.";
    $_SESSION['message_type'] = "error";
    header("Location: ../referenciaADM.php");
    exit;
}

$conn->beginTransaction();
try {
    // A chave primária é composta, então a exclusão é direta.
    // Não há outras tabelas que dependam diretamente de historico_restaurante.
    $sql = "DELETE FROM historico_restaurante WHERE id_funcionario = ? AND id_restaurante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_func_excluir, $id_rest_excluir]);

    $conn->commit();
    $_SESSION['message'] = "Referência excluída com sucesso!";
    $_SESSION['message_type'] = "success";
    header("Location: ../referenciaADM.php");
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    $mensagem_erro = "Erro fatal ao excluir referência: " . $e->getMessage();
    error_log($mensagem_erro);
    $_SESSION['message'] = "Erro ao excluir referência: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
    header("Location: ../referenciaADM.php");
    exit;
}
?>