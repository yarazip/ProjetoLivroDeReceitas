<?php
require_once '../BancoDeDados/conexao.php';

if (!isset($_GET['token'])) {
    die("Token inválido!");
}

$token = $_GET['token'];

// Verifica se o token existe e está válido
$sql = "SELECT * FROM recuperacao_senha WHERE token = :token AND expiracao >= NOW()";
$stmt = $conn->prepare($sql);
$stmt->bindParam(":token", $token);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    die("Link expirado ou inválido.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nova_senha = $_POST['nova_senha'];
    $confirmar = $_POST['confirmar'];

    if ($nova_senha !== $confirmar) {
        echo "<script>alert('Senhas não coincidem!');</script>";
    } else {
        // Revalida o token para obter o e-mail
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);
        $email = $dados['email'];

        // Não faça hash, só salva a senha simples
        $update = "UPDATE logins SET senha = :senha WHERE email = :email";
        $stmtUpdate = $conn->prepare($update);
        $stmtUpdate->bindParam(":senha", $nova_senha);  // Salva senha simples
        $stmtUpdate->bindParam(":email", $email);
        $stmtUpdate->execute();

        // Invalida o token
        $delete = "DELETE FROM recuperacao_senha WHERE token = :token";
        $stmtDelete = $conn->prepare($delete);
        $stmtDelete->bindParam(":token", $token);
        $stmtDelete->execute();

        echo "<script>alert('Senha atualizada com sucesso!'); window.location.href='login.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Redefinir Senha</title>
    <link rel="stylesheet" href="../styles/redefinir.css">
    <script>
    function validarSenha() {
        const senha = document.querySelector('[name="nova_senha"]').value;
        const erros = [];

        if (senha.length < 8) erros.push("mínimo de 8 caracteres");
        if (!/[A-Z]/.test(senha)) erros.push("1 letra maiúscula");
        if (!/[a-z]/.test(senha)) erros.push("1 letra minúscula");
        if (!/[0-9]/.test(senha)) erros.push("1 número");
        if (!/[!@#$%^&*]/.test(senha)) erros.push("1 caractere especial");

        if (erros.length > 0) {
            alert("A senha precisa conter: " + erros.join(", "));
            return false;
        }
        return true;
    }
    </script>
</head>
<body>
    <h2>Redefinir Senha</h2>
    <form method="POST" onsubmit="return validarSenha();">
        <input type="password" name="nova_senha" placeholder="Nova senha" required>
        <input type="password" name="confirmar" placeholder="Confirme a nova senha" required>
        <button type="submit">Redefinir</button>
    </form>
</body>
</html>
