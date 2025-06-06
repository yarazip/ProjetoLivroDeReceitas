<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../BancoDeDados/conexao.php';

// Verifica se o usuário está logado e se tem permissão (Degustador ou Administrador)
if (!isset($_SESSION['id_login']) || ($_SESSION['cargo'] !== 'Degustador' && $_SESSION['cargo'] !== 'Administrador')) {
    $_SESSION['message'] = "Você não tem permissão para realizar esta ação.";
    $_SESSION['message_type'] = "error";
    header("Location: ../../LoginSenha/login.php");
    exit;
}

$id_funcionario_excluir = $_GET['id_funcionario'] ?? null;
$nome_receita_excluir = $_GET['nome_receita'] ?? null;
$confirmacao = $_GET['confirmar'] ?? null;

// A exclusão só acontece se ambos os IDs e a confirmação 'sim' forem enviados
if (is_null($id_funcionario_excluir) || is_null($nome_receita_excluir) || $confirmacao !== 'sim') {
    $_SESSION['message'] = "Ação de exclusão de degustação inválida ou não confirmada.";
    $_SESSION['message_type'] = "error";
    header("Location: ../receitasDegustador.php");
    exit;
}

$conn->beginTransaction();
try {
    // A chave primária é composta, então a exclusão é direta.
    // Não há outras tabelas que dependam diretamente de degustacoes.
    $sql = "DELETE FROM degustacoes WHERE id_funcionario = ? AND nome_receita = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_funcionario_excluir, $nome_receita_excluir]);

    $conn->commit();
    $_SESSION['message'] = "Degustação excluída com sucesso!";
    $_SESSION['message_type'] = "success";
    header("Location: ../receitasDegustador.php");
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    $mensagem_erro = "Erro fatal ao excluir degustação: " . $e->getMessage();
    error_log($mensagem_erro);
    $_SESSION['message'] = "Erro ao excluir degustação: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
    header("Location: ../receitasDegustador.php");
    exit;
}
?>