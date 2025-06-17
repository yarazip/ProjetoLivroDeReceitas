<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../BancoDeDados/conexao.php';

// Verifica se o usuário está logado como Administrador
if (!isset($_SESSION['id_login']) || $_SESSION['cargo'] !== 'administrador') {
    // $_SESSION['message'] = "Você não tem permissão para realizar esta ação.";
    $_SESSION['message_type'] = "error";
    header("Location: ../../LoginSenha/login.php");
    exit;
}

$id_funcionario_para_excluir = $_GET['id'] ?? null;
$confirmacao = $_GET['confirmar'] ?? null;

// A exclusão só acontece se o ID do funcionário e a confirmação 'sim' forem enviados
if (is_null($id_funcionario_para_excluir) || $confirmacao !== 'sim') {
    $_SESSION['message'] = "Ação de exclusão de funcionário inválida ou não confirmada.";
    $_SESSION['message_type'] = "error";
    header("Location: ../funcionarioADM.php");
    exit;
}

$conn->beginTransaction();
try {
    // Ordem de exclusão é CRÍTICA devido às chaves estrangeiras.
    // Excluir de tabelas que dependem de 'id_funcionario' primeiro.

    // 1. Excluir registros em 'degustacoes' que dependem de id_funcionario
    $stmt = $conn->prepare("DELETE FROM degustacoes WHERE id_funcionario = ?");
    $stmt->execute([$id_funcionario_para_excluir]);

    // 2. Excluir registros em 'historico_restaurante'
    $stmt = $conn->prepare("DELETE FROM historico_restaurante WHERE id_funcionario = ?");
    $stmt->execute([$id_funcionario_para_excluir]);

    // 3. Excluir registros em 'publicacoes'
    $stmt = $conn->prepare("DELETE FROM publicacoes WHERE id_funcionario = ?");
    $stmt->execute([$id_funcionario_para_excluir]);

    // 4. Excluir registros em 'livros' (se id_funcionario for FK em livros, o que parece ser no seu DB)
    // CUIDADO: Se um funcionário for editor de livros, isso apagará os livros dele.
    $stmt = $conn->prepare("DELETE FROM livros WHERE id_funcionario = ?");
    $stmt->execute([$id_funcionario_para_excluir]);

    // 5. Excluir registros em 'foto_funcionario' (se houver, e não for parte do blob direto na tabela func)
    $stmt = $conn->prepare("DELETE FROM foto_funcionario WHERE id_funcionario = ?");
    $stmt->execute([$id_funcionario_para_excluir]);
    
    // 6. Excluir registros em 'foto_receita' (se id_funcionario for FK lá)
    $stmt = $conn->prepare("DELETE FROM foto_receita WHERE id_funcionario = ?");
    $stmt->execute([$id_funcionario_para_excluir]);

    // 7. Excluir registros em 'receitas' criadas por este funcionário (se id_funcionario for FK lá)
    // CUIDADO: Isso pode apagar muitas receitas. Alternativa: reatribuir receitas a outro funcionário ou permitir NULL na FK.
    // Se a tabela 'receita_ingrediente' ou 'degustacoes' tem FK para 'nome_receita', você precisaria apagar receitas_ingredientes, degustacoes primeiro.
    // A melhor prática é configurar ON DELETE CASCADE nas chaves estrangeiras no banco de dados para evitar essa cascata manual no PHP.
    // Mas, se não tem CASCADE, essa é a forma manual.

    // Ações em cadeia para receitas criadas pelo funcionário
    $stmt_get_receitas = $conn->prepare("SELECT nome_receita FROM receitas WHERE id_funcionario = ?");
    $stmt_get_receitas->execute([$id_funcionario_para_excluir]);
    $receitas_do_funcionario = $stmt_get_receitas->fetchAll(PDO::FETCH_COLUMN);

    foreach ($receitas_do_funcionario as $nome_receita_afetada) {
        // Excluir de receita_ingrediente
        $stmt_ri = $conn->prepare("DELETE FROM receita_ingrediente WHERE nome_receita = ?");
        $stmt_ri->execute([$nome_receita_afetada]);

        // Excluir de degustacoes (relacionado à receita)
        $stmt_deg = $conn->prepare("DELETE FROM degustacoes WHERE nome_receita = ?");
        $stmt_deg->execute([$nome_receita_afetada]);

        // Excluir de publicacoes (relacionado à receita)
        $stmt_pub = $conn->prepare("DELETE FROM publicacoes WHERE nome_receita = ?");
        $stmt_pub->execute([$nome_receita_afetada]);

        // Excluir de livro_receita (relacionado à receita)
        $stmt_lr = $conn->prepare("DELETE FROM livro_receita WHERE nome_receita = ?");
        $stmt_lr->execute([$nome_receita_afetada]);

        // Excluir de foto_receita
        $stmt_fr = $conn->prepare("DELETE FROM foto_receita WHERE nome_receita = ?");
        $stmt_fr->execute([$nome_receita_afetada]);
    }
    // Finalmente, excluir as receitas criadas pelo funcionário
    $stmt = $conn->prepare("DELETE FROM receitas WHERE id_funcionario = ?");
    $stmt->execute([$id_funcionario_para_excluir]);


    // 8. Excluir o registro de login do funcionário
    $stmt = $conn->prepare("DELETE FROM logins WHERE id_funcionario = ?");
    $stmt->execute([$id_funcionario_para_excluir]);

    // 9. Finalmente, excluir o funcionário da tabela principal
    $stmt = $conn->prepare("DELETE FROM funcionarios WHERE id_funcionario = ?");
    $stmt->execute([$id_funcionario_para_excluir]);

    $conn->commit();
    $_SESSION['message'] = "Funcionário excluído com sucesso, e seus dados relacionados.";
    $_SESSION['message_type'] = "success";
    header("Location: ../funcionarioADM.php");
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    $mensagem_erro = "Erro fatal ao excluir funcionário: " . $e->getMessage();
    error_log($mensagem_erro);
    $_SESSION['message'] = "Erro ao excluir funcionário: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
    header("Location: ../funcionarioADM.php");
    exit;
}
?>