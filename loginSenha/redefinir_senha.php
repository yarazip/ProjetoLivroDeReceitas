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

        $msg = "Senha atualizada com sucesso!";
        $tipoMsg = "success";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <title>Redefinir Senha</title>
    <link rel="stylesheet" href="../styles/redefinir_senha.css">
    <script src="./scripts/script.js"></script>

    <script>
    function validarSenha() {
    const senha = document.querySelector('[name="nova_senha"]').value;
    const erros = [];

    if (senha.length < 8) erros.push("mínimo de 8 caracteres");
    if (!/[A-Z]/.test(senha)) erros.push("1 letra maiúscula");
    if (!/[a-z]/.test(senha)) erros.push("1 letra minúscula");
    if (!/[0-9]/.test(senha)) erros.push("1 número");
    if (!/[!@#$%^&*]/.test(senha)) erros.push("1 caractere especial");

    const divErro = document.getElementById('erro-senha');

    if (erros.length > 0) {
        divErro.textContent = "A senha precisa conter: " + erros.join(", ");
        divErro.style.display = "block";
        return false;
    } else {
        divErro.style.display = "none";
        return true;
    }
}
    </script>
</head>
<body>
    <h2>Redefinir Senha</h2>
    <?php if (!empty($msg)): ?>
    <div class="alert <?php echo $tipoMsg === 'success' ? 'alert-success' : 'alert-error'; ?>">
        <?php echo $msg; ?>
    </div>
    <?php endif; ?>
    <div class="password-field">
    <form method="POST" onsubmit="return validarSenha();">
        <div class="input-wrapper">
    <input type="password" name="nova_senha" placeholder="Nova senha" required>
    <i class="fa-solid fa-eye-slash" id="toggleSenha1"></i>
</div>

<div class="input-wrapper">
    <input type="password" name="confirmar" placeholder="Confirme a nova senha" required>
    <i class="fa-solid fa-eye-slash" id="toggleSenha2"></i>
</div>
        <button type="submit">Redefinir</button>
    </form>
    </div>
    <div id="erro-senha" class="alert alert-error" style="display:none;"></div>
</body>
</html>
