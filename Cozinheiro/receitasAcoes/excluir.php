<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Caminho ajustado para a conexão com o banco de dados
require_once '../../BancoDeDados/conexao.php'; // Voltar duas pastas para encontrar BancoDeDados

// Verifica se o usuário está logado como cozinheiro (ou outro cargo autorizado para excluir)
if (!isset($_SESSION['id_login']) || $_SESSION['cargo'] !== 'Cozinheiro') {
    $_SESSION['message'] = "Você não tem permissão para excluir receitas.";
    $_SESSION['message_type'] = "error";
    header("Location: ../../LoginSenha/login.php"); // Redireciona para login se não autorizado
    exit;
}

$nome_receita_para_excluir = $_GET['excluir'] ?? null;
$confirmacao = $_GET['confirmar'] ?? null; // Adiciona a variável para capturar a confirmação

if (is_null($nome_receita_para_excluir)) {
    $_SESSION['message'] = "Nome da receita para exclusão não fornecido.";
    $_SESSION['message_type'] = "error";
    header("Location: ../receitasChef.php");
    exit;
}

// Lógica de EXCLUSÃO real, SÓ SE HOUVER CONFIRMAÇÃO
if ($confirmacao === 'sim') {
    $conn->beginTransaction();

    try {
        // Lista de tabelas relacionadas e suas colunas que referenciam 'nome_receita'
        // Mantenha apenas as tabelas que realmente possuem chaves estrangeiras com 'nome_receita'
        $tabelas_relacionadas = [
            'degustacoes' => 'nome_receita',
            'publicacoes' => 'nome_receita',
            'livro_receita' => 'nome_receita',
            'receita_ingrediente' => 'nome_receita', // Mantenha se você ainda usa essa tabela para ingredientes
            'foto_receita' => 'nome_receita'
        ];

        // Iterar e excluir registros relacionados em cada tabela
        foreach ($tabelas_relacionadas as $tabela => $coluna) {
            try {
                // Prepara a consulta para verificar se a tabela e a coluna existem
                // Evita erros se o seu banco não tiver todas as tabelas listadas
                $stmt_check_column = $conn->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
                                                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
                $stmt_check_column->execute([$tabela, $coluna]);

                if ($stmt_check_column->rowCount() > 0) {
                    // Se a tabela e a coluna existirem, procede com a exclusão
                    $stmt_delete_related = $conn->prepare("DELETE FROM $tabela WHERE $coluna = ?");
                    $stmt_delete_related->execute([$nome_receita_para_excluir]);
                } else {
                    // Loga se a coluna não for encontrada, mas não impede o processo
                    error_log("Aviso: Coluna '$coluna' não encontrada na tabela '$tabela' durante exclusão da receita '$nome_receita_para_excluir'.");
                }
            } catch (PDOException $e) {
                // Loga qualquer erro de SQL ao tentar acessar/excluir da tabela relacionada
                error_log("Erro ao tentar excluir de '$tabela': " . $e->getMessage());
                // Não faz um rollback aqui, pois queremos que outras exclusões de dependências tentem ocorrer
            }
        }

        // Finalmente, excluir a receita da tabela principal 'receitas'
        $stmt_delete_main = $conn->prepare("DELETE FROM receitas WHERE nome_receita = ?");
        $stmt_delete_main->execute([$nome_receita_para_excluir]);

        $conn->commit();
        $_SESSION['message'] = "Receita '" . htmlspecialchars($nome_receita_para_excluir) . "' excluída com sucesso!";
        $_SESSION['message_type'] = "success";
        header("Location: ../receitasChef.php"); // Redireciona para a página principal
        exit;

    } catch (PDOException $e) {
        $conn->rollBack();
        $mensagem_erro = "Erro fatal ao excluir receita: " . $e->getMessage();
        error_log($mensagem_erro);
        $_SESSION['message'] = "Erro ao excluir receita: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: ../receitasChef.php"); // Redireciona com erro
        exit;
    }
} else {
    // Exibe o alerta de confirmação no navegador
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Confirmar Exclusão</title>
        <link rel="stylesheet" href="../../styles/func.css"> <link rel="shortcut icon" href="../../assets/favicon.png" type="image/x-icon">
    </head>
    <body>
        <script>
            // Função para exibir o alerta de confirmação
            function confirmarExclusao() {
                var confirmar = confirm("Tem certeza que deseja excluir a receita '<?= htmlspecialchars($nome_receita_para_excluir) ?>'? Esta ação é irreversível.");
                if (confirmar) {
                    // Se o usuário confirmar, redireciona para o mesmo script com 'confirmar=sim'
                    window.location.href = "excluir.php?excluir=<?= urlencode($nome_receita_para_excluir) ?>&confirmar=sim";
                } else {
                    // Se o usuário cancelar, volta para a página principal
                    window.location.href = "../../receitasChef.php";
                }
            }
            confirmarExclusao(); // Chama a função assim que a página carrega
        </script>
    </body>
    </html>
    <?php
}
?>