<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../BancoDeDados/conexao.php';

// Verifica se o usuário está logado como cozinheiro (ou outro cargo autorizado para excluir)
if (!isset($_SESSION['id_login']) || $_SESSION['cargo'] !== 'cozinheiro') {
    $_SESSION['message'] = "Você não tem permissão para excluir receitas.";
    $_SESSION['message_type'] = "error";
    header("Location: ../../LoginSenha/login.php");
    exit;
}

$nome_receita_para_excluir = $_GET['excluir'] ?? null;
$confirmacao = $_GET['confirmar'] ?? null;

// A exclusão só acontece se o nome da receita e a confirmação 'sim' forem enviados
if (is_null($nome_receita_para_excluir) || $confirmacao !== 'sim') {
    $_SESSION['message'] = "Ação de exclusão inválida ou não confirmada.";
    $_SESSION['message_type'] = "error";
    header("Location: ../receitasChef.php"); // Redireciona de volta se não houver confirmação
    exit;
}

$conn->beginTransaction();

try {
    // Lista de tabelas relacionadas e suas colunas que referenciam 'nome_receita'
    $tabelas_relacionadas = [
        'degustacoes' => 'nome_receita',
        'publicacoes' => 'nome_receita',
        'livro_receita' => 'nome_receita',
        'receita_ingrediente' => 'nome_receita',
        'foto_receita' => 'nome_receita'
    ];

    foreach ($tabelas_relacionadas as $tabela => $coluna) {
        try {
            $stmt_check_column = $conn->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                                                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
            $stmt_check_column->execute([$tabela, $coluna]);

            if ($stmt_check_column->rowCount() > 0) {
                $stmt_delete_related = $conn->prepare("DELETE FROM $tabela WHERE $coluna = ?");
                $stmt_delete_related->execute([$nome_receita_para_excluir]);
            } else {
                error_log("Aviso: Coluna '$coluna' não encontrada na tabela '$tabela' durante exclusão da receita '$nome_receita_para_excluir'.");
            }
        } catch (PDOException $e) {
            error_log("Erro ao tentar excluir de '$tabela': " . $e->getMessage());
        }
    }

    // Finalmente, excluir a receita da tabela principal 'receitas'
    $stmt_delete_main = $conn->prepare("DELETE FROM receitas WHERE nome_receita = ?");
    $stmt_delete_main->execute([$nome_receita_para_excluir]);

    $conn->commit();
    $_SESSION['message'] = "Receita '" . htmlspecialchars($nome_receita_para_excluir) . "' excluída com sucesso!";
    $_SESSION['message_type'] = "success";
    header("Location: ../receitasChef.php"); // Redireciona para a página principal após a exclusão
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    $mensagem_erro = "Erro fatal ao excluir receita: " . $e->getMessage();
    error_log($mensagem_erro);
    $_SESSION['message'] = "Erro ao excluir receita: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
    header("Location: ../receitasChef.php");
    exit;
}
?>