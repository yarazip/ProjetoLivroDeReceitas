<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Variáveis de controle
$message = '';
$error = '';
$showForm = false;
$token = $_GET['token'] ?? '';

// Função para log de eventos
function logMessage($message) {
    $logFile = __DIR__ . '/../logs/password_reset.log';
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

if ($token) {
    try {
        // 1. Verificar token válido e não expirado
        $stmt = $conn->prepare("SELECT email, expiracao FROM RecuperacaoSenha 
                               WHERE token = :token AND status = 'ativo'");
        $stmt->execute([':token' => $token]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tokenData) {
            throw new Exception("Token inválido ou já utilizado.");
        }

        // Verificar expiração
        $now = new DateTime();
        $expiration = new DateTime($tokenData['expiracao']);
        
        if ($now >= $expiration) {
            // Atualizar status para expirado
            $stmt = $conn->prepare("UPDATE RecuperacaoSenha SET status = 'expirado' 
                                   WHERE token = :token");
            $stmt->execute([':token' => $token]);
            throw new Exception("Este link de redefinição expirou. Solicite um novo.");
        }

        $email = $tokenData['email'];
        $showForm = true;

        // 2. Processar submissão do formulário
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $novaSenha = trim($_POST['nova_senha']);
            $confirmarSenha = trim($_POST['confirmar_senha']);
            
            // Validações básicas
            if (empty($novaSenha) || empty($confirmarSenha)) {
                throw new Exception("Todos os campos são obrigatórios.");
            }
            
            if ($novaSenha !== $confirmarSenha) {
                throw new Exception("As senhas não coincidem.");
            }
            
            if (strlen($novaSenha) < 8) {
                throw new Exception("A senha deve ter pelo menos 8 caracteres.");
            }
            
            // 3. Atualizar senha no banco de dados
            $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
            
            $conn->beginTransaction();
            
            try {
                // Atualizar senha do usuário
                $stmt = $conn->prepare("UPDATE Usuario SET senha = :senha WHERE email = :email");
                $stmt->execute([':senha' => $senhaHash, ':email' => $email]);
                
                // Invalidar token usado
                $stmt = $conn->prepare("UPDATE RecuperacaoSenha SET status = 'expirado' 
                                       WHERE token = :token");
                $stmt->execute([':token' => $token]);
                
                $conn->commit();
                
                // Log de sucesso
                logMessage("Senha redefinida com sucesso para: $email");
                
                $message = "Senha redefinida com sucesso! Você será redirecionado para o login...";
                $showForm = false;
                
                // Redirecionar após 3 segundos
                header("refresh:3;url=login.php");
                
            } catch (Exception $e) {
                $conn->rollBack();
                logMessage("Falha na transação: " . $e->getMessage());
                throw new Exception("Erro ao processar sua solicitação. Tente novamente.");
            }
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        logMessage("Erro no token $token: " . $error);
    }
} else {
    $error = "Link de redefinição inválido.";
    logMessage("Acesso sem token");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha | Código de Sabores</title>
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
            color: #e74c3c;
            margin: 10px 0;
            padding: 10px;
            background-color: #fdecea;
            border-radius: 4px;
            border-left: 4px solid #e74c3c;
        }
        
        .password-rules {
            font-size: 0.8em;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(20px);}
            to {opacity: 1; transform: translateY(0);}
        }
        
        @keyframes fadeOut {
            from {opacity: 1; transform: translateY(0);}
            to {opacity: 0; transform: translateY(20px);}
        }
    </style>
</head>
<body>
    <div class="header">
        <span class="logo">Código de Sabores</span>
    </div>
    <div class="container">
        <div class="form-box">
            <h2>Redefinir Senha</h2>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($showForm): ?>
                <form action="redefinirSenha.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                    <div class="input-group">
                        <input type="password" name="nova_senha" placeholder="Nova senha" required>
                        <div class="password-rules">Mínimo 8 caracteres</div>
                    </div>
                    <div class="input-group">
                        <input type="password" name="confirmar_senha" placeholder="Confirmar nova senha" required>
                    </div>
                    <button type="submit">Redefinir Senha</button>
                </form>
            <?php elseif (!$message): ?>
                <div class="info-message">
                    <p><a href="esqueciasenha.php">Solicitar novo link</a> ou <a href="login.php">Voltar ao login</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($message): ?>
    <div id="notification" class="notification">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <script>
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 3000);
    </script>
    <?php endif; ?>
</body>
</html>