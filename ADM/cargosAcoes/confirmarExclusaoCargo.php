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

$id_cargo_para_excluir = $_GET['id'] ?? null;

// Se o ID do cargo não foi fornecido na URL, redireciona de volta
if (is_null($id_cargo_para_excluir)) {
    $_SESSION['message'] = "Cargo não especificado para exclusão.";
    $_SESSION['message_type'] = "error";
    header("Location: ../cargosADM.php");
    exit;
}

// Buscar detalhes do cargo para exibir na página de confirmação
try {
    $stmt = $conn->prepare("SELECT id_cargo, nome, descricao FROM cargos WHERE id_cargo = ?");
    $stmt->execute([$id_cargo_para_excluir]);
    $cargo_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cargo_info) {
        $_SESSION['message'] = "Cargo não encontrado para confirmação de exclusão.";
        $_SESSION['message_type'] = "error";
        header("Location: ../cargosADM.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar cargo para confirmação: " . $e->getMessage());
    $_SESSION['message'] = "Erro ao carregar detalhes do cargo.";
    $_SESSION['message_type'] = "error";
    header("Location: ../cargosADM.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Confirmar Exclusão de Cargo</title>
    <link rel="stylesheet" href="../../styles/excluirADM.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="shortcut icon" href="../../assets/favicon.png" type="image/x-icon">

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
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?= htmlspecialchars($_SESSION['nome_funcionario'] ?? 'Desconhecido') ?></span>
                </div>
            </nav>
        </div>

        <div class="confirmation-box">
            <h2>Confirmar Exclusão de Cargo</h2>
            <p>Você tem certeza que deseja excluir o cargo:<br>
                <strong>"<?= htmlspecialchars($cargo_info['nome']) ?>" (ID: <?= htmlspecialchars($cargo_info['id_cargo']) ?>)</strong>?
            </p>
            <p>Esta ação é irreversível e pode causar inconsistências se houver funcionários associados a este cargo.</p>
            <div class="buttons">
                <form action="excluirCargo.php" method="GET">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($id_cargo_para_excluir) ?>">
                    <input type="hidden" name="confirmar" value="sim">
                    <button type="submit" class="confirm-button">Sim, Excluir</button>
                </form>

                <a href="../cargosADM.php" class="cancel-button"><button type="button">Cancelar</button></a>
            </div>
        </div>
    </div>
</body>

</html>