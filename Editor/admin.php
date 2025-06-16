<?php
session_start();
if (!isset($_SESSION['id_login']) || ($_SESSION['cargo'] !== 'Editor' && $_SESSION['cargo'] !== 'Administrador')) {
    $_SESSION['message'] = "Você não tem permissão para acessar esta página.";
    $_SESSION['message_type'] = "error";
    header("Location: ../LoginSenha/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <title>Área Administrativa - Editor</title>
    <link rel="stylesheet" href="../styles/func.css" />
</head>
<body>
<div class="container">
    <h1>Área Administrativa do Editor</h1>

    <a href="adicionar_receita.php">
        <button type="button">Adicionar Nova Receita ao Livro</button>
    </a>

    <br><br>

    <a href="listar_receitas_editor.php">Listar Receitas</a>
</div>
</body>
</html>
