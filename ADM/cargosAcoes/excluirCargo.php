<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../BancoDeDados/conexao.php';

// Verifica se o usuário está logado como Administrador
if (!isset($_SESSION['id_login']) || $_SESSION['cargo'] !== 'Administrador') {
    $_SESSION['message'] = "Você não tem permissão para realizar esta ação.";
    $_SESSION['message_type'] = "error";
    header("Location: ../../LoginSenha/login.php");
    exit;
}

$id_cargo_para_excluir = $_GET['id'] ?? null;
$confirmacao = $_GET['confirmar'] ?? null;

// A exclusão só acontece se o ID do cargo e a confirmação 'sim' forem enviados
if (is_null($id_cargo_para_excluir) || $confirmacao !== 'sim') {
    $_SESSION['message'] = "Ação de exclusão de cargo inválida ou não confirmada.";
    $_SESSION['message_type'] = "error";
    header("Location: ../cargosADM.php"); // Redireciona de volta se não houver confirmação
    exit;
}

$conn->beginTransaction();

try {
    // --- Tratamento de Dependências: Antes de excluir um cargo, você deve considerar: ---
    // 1. Há FUNCIONÁRIOS com este cargo?
    //    Se sim, você pode:
    //    a) Impedir a exclusão (melhor para integridade, mas exige que o ADM mude o cargo dos funcionários antes).
    //    b) Definir o id_cargo dos funcionários para NULL (se a coluna for NULLable e o negócio permitir).
    //    c) Atribuir um 'cargo padrão' aos funcionários.
    //    d) Excluir os funcionários (MUITO CUIDADO com isso, geralmente não é o ideal).

    // Opção A: Impedir a exclusão se houver funcionários com este cargo.
    $stmt_check_funcionarios = $conn->prepare("SELECT COUNT(*) FROM funcionarios WHERE id_cargo = ?");
    $stmt_check_funcionarios->execute([$id_cargo_para_excluir]);
    $num_funcionarios_com_cargo = $stmt_check_funcionarios->fetchColumn();

    if ($num_funcionarios_com_cargo > 0) {
        $conn->rollBack();
        $_SESSION['message'] = "Não foi possível excluir o cargo. Ele está associado a " . $num_funcionarios_com_cargo . " funcionário(s). Mude o cargo desses funcionários primeiro.";
        $_SESSION['message_type'] = "error";
        header("Location: ../cargosADM.php");
        exit;
    }

    // Se não houver funcionários, procede com a exclusão do cargo
    $sql = "DELETE FROM cargos WHERE id_cargo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_cargo_para_excluir]);

    $conn->commit();
    $_SESSION['message'] = "Cargo excluído com sucesso!";
    $_SESSION['message_type'] = "success";
    header("Location: ../cargosADM.php"); // Redireciona para a página principal após a exclusão
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    $mensagem_erro = "Erro fatal ao excluir cargo: " . $e->getMessage();
    error_log($mensagem_erro);
    $_SESSION['message'] = "Erro ao excluir cargo: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
    header("Location: ../cargosADM.php");
    exit;
}
?>