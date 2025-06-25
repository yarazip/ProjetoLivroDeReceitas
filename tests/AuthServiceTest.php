<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Services/AuthService.php';
require_once __DIR__ . '/../BancoDeDados/conexao.php';

class AuthServiceTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function testLoginValido()
    {
        $authService = new AuthService($this->pdo);
        $resultado = $authService->validarLogin('usuario_valido@example.com', 'senha_correta');
        $this->assertTrue($resultado);
    }

    public function testLoginInvalido()
    {
        $authService = new AuthService($this->pdo);
        $resultado = $authService->validarLogin('usuario_invalido@example.com', 'senha_errada');
        $this->assertFalse($resultado);
    }
}
