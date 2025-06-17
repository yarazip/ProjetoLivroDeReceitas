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

$id_restaurante_para_excluir = $_GET['id'] ?? null;
$confirmacao = $_GET['confirmar'] ?? null;

// A exclusão só acontece se o ID do restaurante e a confirmação 'sim' forem enviados
if (is_null($id_restaurante_para_excluir) || $confirmacao !== 'sim') {
    $_SESSION['message'] = "Ação de exclusão de restaurante inválida ou não confirmada.";
    $_SESSION['message_type'] = "error";
    header("Location: ../restauranteADM.php");
    exit;
}

$conn->beginTransaction();
try {
    // Tratamento de Dependências: Excluir de tabelas que dependem de 'id_restaurante' primeiro.
    // A tabela principal que depende de 'restaurantes' é 'historico_restaurante'.
    // Se tiver outras tabelas que referenciam 'id_restaurante', adicione aqui.

    // 1. Excluir registros em 'historico_restaurante'
    $stmt = $conn->prepare("DELETE FROM historico_restaurante WHERE id_restaurante = ?");
    $stmt->execute([$id_restaurante_para_excluir]);

    // 2. Finalmente, excluir o restaurante da tabela principal
    $sql = "DELETE FROM restaurantes WHERE id_restaurante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_restaurante_para_excluir]);

    $conn->commit();
    $_SESSION['message'] = "Restaurante excluído com sucesso!";
    $_SESSION['message_type'] = "success";
    header("Location: ../restauranteADM.php");
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    $mensagem_erro = "Erro fatal ao excluir restaurante: " . $e->getMessage();
    error_log($mensagem_erro);
    $_SESSION['message'] = "Erro ao excluir restaurante: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
    header("Location: ../restauranteADM.php");
    exit;
}
?>