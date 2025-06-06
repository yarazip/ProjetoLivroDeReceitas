<?php
session_start();
require_once '../BancoDeDados/conexao.php';

// Limpa a mensagem de erro antiga se o usuário apenas recarregar a página
if ($_SERVER["REQUEST_METHOD"] !== "POST" && isset($_SESSION['login_error'])) {
    unset($_SESSION['login_error']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    try {
        // Consulta para juntar login, funcionario e cargo
        // IMPORTANTE: Esta consulta espera senhas em texto puro. Não é seguro para produção.
        $sql = "SELECT l.id_login, l.email, l.id_funcionario, f.nome AS nome_funcionario, c.nome AS nome_cargo
                FROM logins l
                JOIN funcionarios f ON l.id_funcionario = f.id_funcionario
                JOIN cargos c ON f.id_cargo = c.id_cargo
                WHERE l.email = :email AND l.senha = :senha
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha', $senha);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            // Limpa qualquer erro de login anterior da sessão
            unset($_SESSION['login_error']);

            // Armazena os dados do usuário na sessão
            $_SESSION['id_login'] = $usuario['id_login'];
            $_SESSION['email'] = $usuario['email'];
            $_SESSION['id_funcionario'] = $usuario['id_funcionario'];
            $_SESSION['cargo'] = $usuario['nome_cargo'];
            $_SESSION['nome_funcionario'] = $usuario['nome_funcionario'];

            // Redireciona o usuário com base no seu cargo
            switch (strtolower($usuario['nome_cargo'])) {
                case 'administrador':
                    header("Location: ../ADM/cargosADM.php");
                    break;
                case 'cozinheira': // Aceita a forma feminina
                case 'cozinheiro': // Aceita a forma masculina
                    header("Location: ../Cozinheiro/categoriaChef.php");
                    break;
                case 'editor':
                    header("Location: ../Editor/livrosEditor.php");
                    break;
                case 'degustador':
                    header("Location: ../Degustador/receitasDegustador.php");
                    break;
                default:
                    // Se o cargo não for reconhecido, volta para o login
                    $_SESSION['login_error'] = "Cargo não reconhecido. Contate o administrador.";
                    header("Location: login.php");
                    break;
            }
            exit(); // Garante que o script pare após o redirecionamento

        } else {
            // Se o email ou senha estiverem errados, define uma mensagem de erro e volta ao login
            $_SESSION['login_error'] = "Email ou senha inválidos!";
            header("Location: login.php");
            exit();
        }
    } catch (PDOException $e) {
        // Em um ambiente de produção, é melhor registrar o erro e mostrar uma página de erro genérica.
        // error_log("Erro de banco de dados: " . $e->getMessage());
        die("Erro ao conectar com o banco de dados. Por favor, tente novamente mais tarde.");
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
            // Exibe a mensagem de erro, se existir
            if (isset($_SESSION['login_error'])) {
                // A estilização pode ser feita em uma classe no seu arquivo CSS
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