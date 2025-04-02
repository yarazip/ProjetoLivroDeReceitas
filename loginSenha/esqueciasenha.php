<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    try {
        // Verificar se o email existe
        $stmt = $conn->prepare("SELECT email, nome FROM Usuario WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Gerar token
            $token = bin2hex(random_bytes(32));
            $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Registrar token no banco
            $stmt = $conn->prepare("INSERT INTO RecuperacaoSenha (email, token, expiracao, status) 
                                  VALUES (:email, :token, :expiracao, 'ativo')
                                  ON DUPLICATE KEY UPDATE 
                                  token = VALUES(token), 
                                  expiracao = VALUES(expiracao), 
                                  status = VALUES(status)");
            $stmt->execute([
                ':email' => $email,
                ':token' => $token,
                ':expiracao' => $expiracao
            ]);

            // Configurar PHPMailer
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ya678945@gmail.com';
            $mail->Password = 'yupy mdzf xoob sbsm';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            
            $mail->setFrom('ya678945@gmail.com', 'Código de Sabores');
            $mail->addAddress($email, $usuario['nome']);
            
            // Conteúdo do email
            $resetLink = "https://localhost/projetoreceitas/loginSenha/redefinirSenha.php?token=$token";
            
            $mail->isHTML(true);
            $mail->Subject = 'Redefinicao de Senha';
            $mail->Body = "
                <h1>Redefinicao de Senha</h1>
                <p>Olá {$usuario['nome']},</p>
                <p>Recebemos uma solicitacao para redefinir sua senha. Clique no link abaixo:</p>
                <p><a href='$resetLink'>Redefinir Senha</a></p>
                <p>O link expira em 1 hora.</p>
                <p>Caso nao tenha solicitado, ignore este email.</p>
            ";
            
            $mail->AltBody = "Redefina sua senha acessando: $resetLink";
            
            $mail->send();

            // ==============================================
            // DEBUG: Mostra o link na tela para teste (remova depois)
            echo "<div style='background:#e0f7fa; padding:15px; margin:20px; border:2px solid #0288d1; border-radius:5px;'>";
            echo "<h3 style='color:#0288d1;'>DEBUG (remova em produção)</h3>";
            echo "<p>Link enviado por email: <a href='$resetLink' style='color:#d32f2f;'>$resetLink</a></p>";
            echo "<p>Token: $token</p>";
            echo "</div>";
            // ==============================================

            $message = "Um link de redefinição foi enviado para seu e-mail!";
        } else {
            $error = "E-mail não encontrado em nosso sistema.";
        }
    } catch (Exception $e) {
        $error = "Erro ao processar solicitação: " . $e->getMessage();
    }
}
?>
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci a Senha</title>
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="../styles/esqueciasenha.css">
    <style>
        .notification {
            display: <?php echo $message ? 'block' : 'none'; ?>;
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #4CAF50;
            color: white;
            padding: 15px;
            border-radius: 5px;
            z-index: 1000;
            animation: fadeIn 0.5s, fadeOut 0.5s 2.5s;
        }
        
        .error-message {
            color: #ff3333;
            margin: 10px 0;
            text-align: center;
        }
        
        @keyframes fadeIn {
            from {opacity: 0;}
            to {opacity: 1;}
        }
        
        @keyframes fadeOut {
            from {opacity: 1;}
            to {opacity: 0;}
        }
    </style>
</head>
<body>
    <div class="header">
        <span class="logo">Código de Sabores</span>
    </div>
    <div class="container">
        <div class="form-box">
            <h2>Recuperar Senha</h2>
            <p>Digite seu e-mail para receber um link de redefinição de senha.</p>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form action="esqueciasenha.php" method="POST">
                <div class="input-group">
                    <input type="email" name="email" placeholder="Digite seu e-mail" required>
                </div>
                <button type="submit">Enviar Link</button>
            </form>            
            <a href="login.php" class="back-link">Voltar para Login</a>
        </div>
    </div>
    <img src="../assets/dish.png" alt="Imagem Giratória" class="rotating-image">
    
    <?php if ($message): ?>
    <div id="notification" class="notification"><?php echo htmlspecialchars($message); ?></div>
    <script>
        // Fade out the notification after 3 seconds
        setTimeout(function() {
            var notification = document.getElementById('notification');
            if (notification) {
                notification.style.display = 'none';
            }
        }, 3000);
    </script>
    <?php endif; ?>
</body>
</html>