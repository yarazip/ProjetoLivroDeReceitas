<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../BancoDeDados/conexao.php';

// Verifica se o usuário está logado como cozinheiro (ou outro cargo autorizado para excluir)
if (!isset($_SESSION['id_login']) || $_SESSION['cargo'] !== 'Cozinheiro') {
    $_SESSION['message'] = "Você não tem permissão para realizar esta ação.";
    $_SESSION['message_type'] = "error";
    header("Location: ../../LoginSenha/login.php");
    exit;
}

$nome_receita_para_excluir = $_GET['nome'] ?? null;

// Se o nome da receita não foi fornecido na URL, redireciona de volta
if (is_null($nome_receita_para_excluir)) {
    $_SESSION['message'] = "Receita não especificada para exclusão.";
    $_SESSION['message_type'] = "error";
    header("Location: ../receitasChef.php");
    exit;
}

// Opcional: Buscar mais detalhes da receita para exibir na página de confirmação
// Por exemplo, para mostrar ao usuário mais informações antes de confirmar
try {
    $stmt = $conn->prepare("SELECT nome_receita, descricao FROM receitas WHERE nome_receita = ?");
    $stmt->execute([$nome_receita_para_excluir]);
    $receita_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receita_info) {
        $_SESSION['message'] = "Receita não encontrada para confirmação de exclusão.";
        $_SESSION['message_type'] = "error";
        header("Location: ../receitasChef.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar receita para confirmação: " . $e->getMessage());
    $_SESSION['message'] = "Erro ao carregar detalhes da receita.";
    $_SESSION['message_type'] = "error";
    header("Location: ../receitasChef.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Confirmar Exclusão de Receita</title>
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
                <a href="../receitasChef.php">Receitas</a>
                <a href="../ingredientesChef.php">Ingredientes</a>
                <a href="../medidasChef.php">Medidas</a>
                <a href="../categoriaChef.php">Categorias</a>
            </nav>
        </div>

        <div class="confirmation-box">
            <h2>Confirmar Exclusão</h2>
            <p>Você tem certeza que deseja excluir a receita:<br>
               <strong>"<?= htmlspecialchars($receita_info['nome_receita']) ?>"</strong>?</p>
            <p>Esta ação é irreversível.</p>
            <div class="buttons">
                <form action="excluir.php" method="GET">
                    <input type="hidden" name="excluir" value="<?= htmlspecialchars($nome_receita_para_excluir) ?>">
                    <input type="hidden" name="confirmar" value="sim">
                    <button type="submit" class="confirm-button">Sim, Excluir</button>
                </form>
                
                <button type="button" class="cancel-button" onclick="window.location.href='../receitasChef.php'">Cancelar</button>
            </div>
        </div>
    </div>
</body>
</html>