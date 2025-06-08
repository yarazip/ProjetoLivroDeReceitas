<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../BancoDeDados/conexao.php';

// Verifica se o usuário está logado e se tem permissão (Cozinheiro ou Administrador)
if (!isset($_SESSION['id_login']) || ($_SESSION['cargo'] !== 'cozinheiro' && $_SESSION['cargo'] !== 'administrador')) {
    $_SESSION['message'] = "Você não tem permissão para realizar esta ação.";
    $_SESSION['message_type'] = "error";
    header("Location: ../../LoginSenha/login.php");
    exit;
}

$id_categoria_para_excluir = $_GET['id'] ?? null;

if (is_null($id_categoria_para_excluir)) {
    $_SESSION['message'] = "Categoria não especificada para exclusão.";
    $_SESSION['message_type'] = "error";
    header("Location: ../categoriaChef.php");
    exit;
}

// Buscar detalhes da categoria para exibir na página de confirmação
try {
    $stmt = $conn->prepare("SELECT id_categoria, nome_categoria, descricao FROM categorias WHERE id_categoria = ?");
    $stmt->execute([$id_categoria_para_excluir]);
    $categoria_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$categoria_info) {
        $_SESSION['message'] = "Categoria não encontrada para confirmação de exclusão.";
        $_SESSION['message_type'] = "error";
        header("Location: ../categoriaChef.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar categoria para confirmação: " . $e->getMessage());
    $_SESSION['message'] = "Erro ao carregar detalhes da categoria.";
    $_SESSION['message_type'] = "error";
    header("Location: ../categoriaChef.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Confirmar Exclusão de Categoria</title>
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
            <h2>Confirmar Exclusão de Categoria</h2>
            <p>Você tem certeza que deseja excluir a categoria:<br>
               <strong>"<?= htmlspecialchars($categoria_info['nome_categoria']) ?>" (ID: <?= htmlspecialchars($categoria_info['id_categoria']) ?>)</strong>?</p>
            <p>Esta ação é irreversível e só será possível se a categoria não estiver sendo usada em nenhuma receita.</p>
            <div class="buttons">
                <form action="excluirCategoria.php" method="GET">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($id_categoria_para_excluir) ?>">
                    <input type="hidden" name="confirmar" value="sim">
                    <button type="submit" class="confirm-button">Sim, Excluir</button>
                </form>

                <button type="button" class="cancel-button" onclick="window.location.href='../categoriaChef.php'">Cancelar</button>
            </div>
        </div>
    </div>
</body>
</html>