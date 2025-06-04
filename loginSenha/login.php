<?php
//medei essa parte de php todinha
session_start();
require_once '../BancoDeDados/conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    try {
        // Consulta para juntar login, funcionario e cargo
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

            $_SESSION['id_login'] = $usuario['id_login'];
            $_SESSION['email'] = $usuario['email'];
            $_SESSION['id_funcionario'] = $usuario['id_funcionario'];
            $_SESSION['cargo'] = $usuario['nome_cargo'];
            $_SESSION['nome_funcionario'] = $usuario['nome_funcionario'];

            // Redireciona conforme cargo
            switch (strtolower($usuario['nome_cargo'])) {
                case 'administrador':
                    header("Location: ../ADM/cargosADM.php");
                    break;
                case 'cozinheiro':
                    header("Location: ../Cozinheiro/categoriaChef.php");
                    break;
                case 'editor':
                    header("Location: ../Editor/livrosEditor.php");
                    break;
                case 'degustador':
                    header("Location: ../Degustador/receitasDegustador.php");
                    break;
                default:
                    // Se não reconhecido, manda pra página padrão
                    header("Location: ../LoginSenha/login.php");
                    break;
            }
            exit();
        } else {
            echo "<script>alert('Email ou senha inválidos!'); window.history.back();</script>";
            exit();
        }
    } catch (PDOException $e) {
        die("Erro ao consultar o banco: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Codigo de Sabores</title>
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="../styles/login.css">
    <script src="script.js"></script>
    <title>Codigo de Sabores|Login</title>
</head>
<body>
    <div class="logo">
        <h1>Código De 
            Sabores</h1>
    </div>
    <div class="container">
        <!-- Seção de Login -->
        <h2>LOGIN</h2>
        <div class="login-section">
           <div class="login-container">
              <form method="post" action="">
                 <input type="email" name="email" placeholder="Email" required>
                 <input type="password" name="senha" placeholder="Senha" required>
                 <button type="submit">Entrar</button>
              </form>
            </div>
        </div>
        
         <div class="links">
                <a href="../loginSenha/esqueciasenha.html">Esqueci a Senha</a>
                <!-- <a href="">Não possui cadastro? Clique aqui!</a> -->
                </div>  
                <img src="../assets/dishLogin.png" alt="Imagem Giratória" class="rotating-image">

        </div>
    </div>
</body>
</html>
