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

if (is_null($id_restaurante_para_excluir)) {
    $_SESSION['message'] = "Restaurante não especificado para exclusão.";
    $_SESSION['message_type'] = "error";
    header("Location: ../restauranteADM.php");
    exit;
}

// Buscar detalhes do restaurante para exibir na página de confirmação
try {
    $stmt = $conn->prepare("SELECT id_restaurante, nome, contato FROM restaurantes WHERE id_restaurante = ?");
    $stmt->execute([$id_restaurante_para_excluir]);
    $restaurante_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$restaurante_info) {
        $_SESSION['message'] = "Restaurante não encontrado para confirmação de exclusão.";
        $_SESSION['message_type'] = "error";
        header("Location: ../restauranteADM.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar restaurante para confirmação: " . $e->getMessage());
    $_SESSION['message'] = "Erro ao carregar detalhes do restaurante.";
    $_SESSION['message_type'] = "error";
    header("Location: ../restauranteADM.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Confirmar Exclusão de Restaurante</title>
    <link rel="stylesheet" href="../../styles/excluirADM.css">
  <link rel="icon" type="image/png" href="/ProjetoLivroDeReceitas/assets/favicon.png">
   
</head>
<body>
    <div class="container">
        <div class="menu">
            <h1 class="logo">Código de Sabores</h1>
            <nav>
                <a href="../cargosADM.php">Cargo</a>
                <a href="../restauranteADM.php">Restaurantes</a>
                <a href="../funcionarioADM.php">Funcionário</a>
                <a href="../referenciaADM.php">Referência</a>
            </nav>
        </div>

        <div class="confirmation-box">
            <h2>Confirmar Exclusão de Restaurante</h2>
            <p>Você tem certeza que deseja excluir o restaurante:<br>
               <strong>"<?= htmlspecialchars($restaurante_info['nome']) ?>" (ID: <?= htmlspecialchars($restaurante_info['id_restaurante']) ?>)</strong>?</p>
            <p>Esta ação é irreversível e removerá todos os históricos de funcionários associados a este restaurante.</p>
            <div class="buttons">
                <form action="excluirRestaurante.php" method="GET">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($id_restaurante_para_excluir) ?>">
                    <input type="hidden" name="confirmar" value="sim">
                    <button type="submit" class="confirm-button">Sim, Excluir</button>
                </form>

                <button type="button" class="cancel-button-res" onclick="window.location.href='../restauranteADM.php'">Cancelar</button>
            </div>
        </div>
    </div>
</body>
</html>