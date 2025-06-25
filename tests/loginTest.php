<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Services/AuthService.php';

class LoginTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        $host = 'localhost';
        $dbname = 'teste_trabalho_1';
        $username = 'root';
        $password = 'yara123';
        $port = '3307';

        $this->pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function testLoginValido()
    {
        $email = 'EDITOR@email.com';
        $senha = 'senha123';

        $authService = new AuthService($this->pdo);

        $resultado = $authService->validarLoginTextoPlano($email, $senha);

        $this->assertTrue($resultado);
    }

    public function testLoginInvalido()
    {
        $email = 'EDITOR@email.com';
        $senha = 'senha_errada';

        $authService = new AuthService($this->pdo);

        $resultado = $authService->validarLoginTextoPlano($email, $senha);

        $this->assertFalse($resultado);
    }
}
