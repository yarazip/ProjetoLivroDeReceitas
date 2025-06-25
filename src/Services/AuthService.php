<?php

class AuthService
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

  public function validarLoginTextoPlano($email, $senha)
{
    $stmt = $this->pdo->prepare("SELECT senha FROM logins WHERE email = :email");
    $stmt->bindValue(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return false;
    }

    // Compara diretamente a senha (texto puro)
    return $senha === $user['senha'];
}

}
