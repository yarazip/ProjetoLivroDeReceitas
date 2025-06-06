<?php
session_start();
require_once '../BancoDeDados/conexao.php';

// Limpa a mensagem de erro antiga se o usuário apenas recarregar a página
if ($_SERVER["REQUEST_METHOD"] !== "POST" && isset($_SESSION['login_error'])) {
    unset($_SESSION['login_error']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $senha_digitada = $_POST['senha'];

    try {
        // Passo 1: Busca o usuário APENAS pelo email
        $sql = "SELECT l.id_login, l.email, l.senha AS senha_hash, f.nome AS nome_funcionario, c.nome AS nome_cargo
                FROM logins l
                JOIN funcionarios f ON l.id_funcionario = f.id_funcionario
                JOIN cargos c ON f.id_cargo = c.id_cargo
                WHERE l.email = :email
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Passo 2: Verifica se a senha digitada corresponde ao hash do banco
            if (password_verify($senha_digitada, $usuario['senha_hash'])) {
                // Senha correta! Login bem-sucedido.
                $_SESSION['id_login'] = $usuario['id_login'];
                $_SESSION['email'] = $usuario['email'];
                $_SESSION['cargo'] = $usuario['nome_cargo'];
                $_SESSION['nome_funcionario'] = $usuario['nome_funcionario'];
                
                // Pega o cargo, remove espaços e converte para minúsculas para o redirecionamento
                $cargo_formatado = trim(strtolower($usuario['nome_cargo']));

                switch ($cargo_formatado) {
                    case 'administrador':
                        header("Location: ../ADM/cargosADM.php");
                        break;
                    case 'cozinheira':
                    case 'cozinheiro':
                        header("Location: ../Cozinheiro/categoriaChef.php");
                        break;
                    case 'editor':
                        header("Location: ../Editor/livrosEditor.php");
                        break;
                    case 'degustadora':
                    case 'degustador':
                        header("Location: ../Degustador/receitasDegustador.php");
                        break;
                    default:
                        $_SESSION['login_error'] = "Seu cargo não possui uma página de acesso.";
                        header("Location: login.php");
                        break;
                }
                exit();
            }
        }
        
        // Se o email não foi encontrado ou a senha estava incorreta
        $_SESSION['login_error'] = "Email ou senha inválidos.";
        header("Location: login.php");
        exit();

    } catch (PDOException $e) {
        // Em produção, o ideal é registrar o erro e não exibir para o usuário
        // error_log("Erro no login: " . $e->getMessage());
        die("Ocorreu um erro crítico no sistema. Contate o administrador.");
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Sabores | Login</title>
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="../styles/login.css">
</head>
<body>
    <div class="logo">
        <h1>Código De Sabores</h1>
    </div>
    <div class="container">
        <h2>LOGIN</h2>

        <?php
            // Exibe a mensagem de erro, se ela existir na sessão
            if (isset($_SESSION['login_error'])) {
                echo '<p style="color: #D8000C; background-color: #FFD2D2; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 15px;">' 
                     . htmlspecialchars($_SESSION['login_error']) 
                     . '</p>';
                unset($_SESSION['login_error']); // Limpa a mensagem para não mostrar novamente
            }
        ?>

        <div class="login-section">
           <div class="login-container">
             <form method="post" action="login.php">
                 <input type="email" name="email" placeholder="Email" required>
                 <input type="password" name="senha" placeholder="Senha" required>
                 <button type="submit">Entrar</button>
             </form>
           </div>
        </div>
        
         <div class="links">
               <a href="../loginSenha/esqueciasenha.html">Esqueci a Senha</a>
         </div>  
         <img src="../assets/dishLogin.png" alt="Imagem Giratória" class="rotating-image">
    </div>
</body>
</html>