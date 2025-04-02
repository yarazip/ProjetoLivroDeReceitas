<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../conexao.php';

// URL base corrigida para a estrutura do projeto
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/projetoreceitas/';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);

    try {
        $stmt = $conn->prepare("SELECT * FROM Usuario WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_tipo'] = $usuario['tipo'];
            
            // Redirecionamentos corrigidos para a nova estrutura
            switch ($usuario['tipo']) {
                case 'administrador':
                    header('Location: ' . $base_url . 'ADM/restauranteADM.php');
                    break;
                case 'cozinheiro':
                    header('Location: ' . $base_url . 'Chef/receitasChef.php');
                    break;
                case 'degustador':
                    header('Location: ' . $base_url . 'Degustador/receitasDegustador.php');
                    break;
                case 'editor':
                    header('Location: ' . $base_url . 'Editor/livrosEditor.php');
                    break;
                default:
                    header('Location: ' . $base_url . 'index.php');
            }
            exit();
        } else {
            $error = "Email ou senha incorretos";
        }
    } catch (Exception $e) {
        $error = "Erro ao processar login: " . $e->getMessage();
    }
}
?>
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Sabores | Login</title>
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="../styles/login.css">
    <style>
        .error-message {
            color: #ff3333;
            margin: 10px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="logo">
        <h1>Código De Sabores</h1>
    </div>
    <div class="container">
        <h2>LOGIN</h2>
        <div class="login-section">
            <div class="login-container">
                <form method="POST" action="login.php">
                    <?php if ($error): ?>
                        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="senha" placeholder="Senha" required>
                    <button type="submit">Entrar</button>
                </form>                
            </div>
        </div>                
        
        <div class="links">
            <a href="esqueciasenha.php">Esqueci a Senha</a>
        </div>  

        <div class="video-section">
            <video autoplay loop muted>
                <source src="../assets/video.mp4" type="video/mp4">
                Seu navegador não suporta vídeos.
            </video>
        </div>
    </div>
</body>
</html>