<?php
require_once '../BancoDeDados/conexao.php';
require '../vendor/autoload.php'; // se usou composer
// ou: require '../path/to/PHPMailer.php'; etc. se for manual
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = $_POST['email'];
    
    // Verifica se o e-mail existe no sistema
    $sql = "SELECT * FROM logins WHERE email = :email";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":email", $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Gera token
        $token = bin2hex(random_bytes(32));
        $expiracao = date("Y-m-d H:i:s", strtotime("+1 hour"));
        
        // Salva o token no banco
        $insert = "INSERT INTO recuperacao_senha (email, token, expiracao) VALUES (:email, :token, :exp)";
        $stmtInsert = $conn->prepare($insert);
        $stmtInsert->bindParam(":email", $email);
        $stmtInsert->bindParam(":token", $token);
        $stmtInsert->bindParam(":exp", $expiracao);
        $stmtInsert->execute();

        // Envia o e-mail (simples usando mail() – pode usar PHPMailer também)
        $link = "http://localhost:8000/LoginSenha/redefinir_senha.php?token=$token";

        $assunto = "Redefinição de Senha - Código de Sabores";
        $mensagem = "Olá! Clique no link abaixo para redefinir sua senha:\n\n$link\n\nEsse link expira em 1 hora.";
        $cabecalhos = "From: codigodesabores@gmail.com";

        // $mail = new PHPMailer(true);

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; 
    $mail->SMTPAuth = true;
    $mail->Username = 'codigosabores@gmail.com'; 
    $mail->Password = 'vwvz qcnr rojs hxbb'; 
    $mail->SMTPSecure = 'ssl'; 
    $mail->Port = 465; 
    $mail->CharSet = 'UTF-8';

    $mail->setFrom('codigosabores@gmail.com', 'Código de Sabores'); // email e nome exibido no remetente
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = $assunto;
    $mail->Body = nl2br($mensagem);

    $mail->send();
} catch (Exception $e) {
    echo "Erro ao enviar e-mail: {$mail->ErrorInfo}";
}


$msg = "Um link foi enviado para seu e-mail!";
$tipoMsg = "success";
    } else {
$msg = "E-mail não encontrado!";
$tipoMsg = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci a Senha</title>
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="../styles/esqueciasenha.css">
    <title>Recuperar Senha</title>
</head>
<body>
    <div class="header">
        <span class="logo">Código de Sabores</span>
    </div>
    <div class="container">
        <div class="form-box">
            <?php if (!empty($msg)): ?>
    <div class="alert <?php echo $tipoMsg === 'success' ? 'alert-success' : 'alert-error'; ?>">
        <?php echo $msg; ?>
    </div>
<?php endif; ?>

            <h2>Recuperar Senha</h2>
            <p>Digite seu e-mail para receber um link de redefinição de senha.</p>
            <form action="" method="POST">
                <div class="input-group">
                    <input type="email" name="email" placeholder="Digite seu e-mail" required>
                </div>
                <button type="submit">Enviar Link</button>
            </form>            
            <a href="login.php" class="back-link">Voltar para Login</a>
        </div>
    </div>
    <img src="../assets/dish.png" alt="Imagem Giratória" class="rotating-image">
    <div id="notification" class="notification">Código enviado para o seu e-mail!</div>
    <script src="./scripts/script.js"></script>
</body>
</html>