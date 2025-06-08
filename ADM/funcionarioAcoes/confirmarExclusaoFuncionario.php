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

if (is_null($id_funcionario_para_excluir)) {
    $_SESSION['message'] = "Funcionário não especificado para exclusão.";
    $_SESSION['message_type'] = "error";
    header("Location: ../funcionarioADM.php");
    exit;
}

// Buscar detalhes do funcionário para exibir na página de confirmação
try {
    $stmt_func = $conn->prepare("SELECT nome, CPF, id_funcionario FROM funcionarios WHERE id_funcionario = ?");
    $stmt_func->execute([$id_funcionario_para_excluir]);
    $funcionario_info = $stmt_func->fetch(PDO::FETCH_ASSOC);

    if (!$funcionario_info) {
        $_SESSION['message'] = "Funcionário não encontrado para confirmação de exclusão.";
        $_SESSION['message_type'] = "error";
        header("Location: ../funcionarioADM.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar funcionário para confirmação: " . $e->getMessage());
    $_SESSION['message'] = "Erro ao carregar detalhes do funcionário.";
    $_SESSION['message_type'] = "error";
    header("Location: ../funcionarioADM.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Confirmar Exclusão de Funcionário</title>
    <link rel="stylesheet" href="../../styles/func.css">
    <link rel="shortcut icon" href="../../assets/favicon.png" type="image/x-icon">
    <style>
        .confirmation-box {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            margin: 50px auto;
        }
        .confirmation-box h2 {
            color: #dc3545;
            margin-bottom: 20px;
        }
        .confirmation-box p {
            font-size: 1.1em;
            margin-bottom: 30px;
        }
        .confirmation-box .buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        .confirmation-box .buttons button {
            padding: 12px 25px;
            font-size: 1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .confirmation-box .buttons .confirm-button {
            background-color: #dc3545; /* Vermelho */
            color: white;
        }
        .confirmation-box .buttons .confirm-button:hover {
            background-color: #c82333;
        }
        .confirmation-box .buttons .cancel-button {
            background-color: #6c757d; /* Cinza */
            color: white;
        }
        .confirmation-box .buttons .cancel-button:hover {
            background-color: #5a6268;
        }
    </style>
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
            <h2>Confirmar Exclusão de Funcionário</h2>
            <p>Você tem certeza que deseja excluir o funcionário:<br>
               <strong>"<?= htmlspecialchars($funcionario_info['nome']) ?>" (CPF: <?= htmlspecialchars($funcionario_info['CPF']) ?>)</strong>?</p>
            <p>Esta ação é irreversível e removerá todos os dados relacionados a este funcionário (login, histórico de restaurante, degustações, etc.).</p>
            <div class="buttons">
                <form action="excluirFuncionario.php" method="GET">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($id_funcionario_para_excluir) ?>">
                    <input type="hidden" name="confirmar" value="sim">
                    <button type="submit" class="confirm-button">Sim, Excluir</button>
                </form>

                <button type="button" class="cancel-button" onclick="window.location.href='../funcionarioADM.php'">Cancelar</button>
            </div>
        </div>
    </div>
</body>
</html>