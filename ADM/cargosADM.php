<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../conexao.php';

// Função para gerar senha temporária
function gerarSenhaTemporaria() {
    return bin2hex(random_bytes(4)); // Gera uma senha aleatória de 8 caracteres
}

// Operação de Exclusão
if (isset($_GET['excluir'])) {
    try {
        $email = $_GET['excluir'];
        
        // Primeiro remove das tabelas específicas
        $tipos = ['Administrador', 'Cozinheiro', 'Degustador', 'Editor'];
        foreach ($tipos as $tipo) {
            $stmt = $conn->prepare("DELETE FROM $tipo WHERE email = ?");
            $stmt->execute([$email]);
        }
        
        // Depois remove da tabela Usuario
        $stmt = $conn->prepare("DELETE FROM Usuario WHERE email = ?");
        $stmt->execute([$email]);
        
        $_SESSION['mensagem'] = [
            'tipo' => 'sucesso',
            'texto' => 'Usuário excluído com sucesso!'
        ];
        
        header('Location: cargosADM.php');
        exit;
    } catch(PDOException $e) {
        $_SESSION['mensagem'] = [
            'tipo' => 'erro',
            'texto' => 'Erro ao excluir usuário: ' . $e->getMessage()
        ];
    }
}

// Operação de Edição (carregar dados)
$usuarioEdicao = null;
if (isset($_GET['editar'])) {
    try {
        $email = $_GET['editar'];
        $stmt = $conn->prepare("SELECT * FROM Usuario WHERE email = ?");
        $stmt->execute([$email]);
        $usuarioEdicao = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $_SESSION['mensagem'] = [
            'tipo' => 'erro',
            'texto' => 'Erro ao carregar usuário: ' . $e->getMessage()
        ];
    }
}

// Operação de Cadastro/Atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = $_POST['email'];
        $nome = $_POST['nome'];
        $tipo = $_POST['tipo'];
        $datanasc = $_POST['datanasc'] ?? '2000-01-01';
        
        if (isset($_POST['id_edicao'])) {
            // MODE EDIÇÃO
            $stmt = $conn->prepare("UPDATE Usuario SET nome = ?, datanasc = ?, tipo = ? WHERE email = ?");
            $stmt->execute([$nome, $datanasc, $tipo, $email]);
            
            // Remove de todas as tabelas de tipo
            $tipos = ['Administrador', 'Cozinheiro', 'Degustador', 'Editor'];
            foreach ($tipos as $t) {
                $conn->prepare("DELETE FROM $t WHERE email = ?")->execute([$email]);
            }
            
            // Adiciona na tabela do novo tipo
            if (in_array($tipo, $tipos)) {
                $conn->prepare("INSERT INTO $tipo (email) VALUES (?)")->execute([$email]);
            }
            
            $_SESSION['mensagem'] = [
                'tipo' => 'sucesso',
                'texto' => 'Usuário atualizado com sucesso!'
            ];
        } else {
            // MODO CADASTRO
            $senhaTemporaria = gerarSenhaTemporaria();
            $senhaHash = password_hash($senhaTemporaria, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO Usuario (email, nome, senha, datanasc, tipo) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$email, $nome, $senhaHash, $datanasc, $tipo]);
            
            if (in_array($tipo, ['Administrador', 'Degustador', 'Editor', 'Cozinheiro'])) {
                $conn->prepare("INSERT INTO $tipo (email) VALUES (?)")->execute([$email]);
            }
            
            $_SESSION['mensagem'] = [
                'tipo' => 'sucesso',
                'texto' => "Usuário $nome adicionado com sucesso! Senha temporária: $senhaTemporaria"
            ];
        }
        
        header('Location: cargosADM.php');
        exit;
    } catch(PDOException $e) {
        $_SESSION['mensagem'] = [
            'tipo' => 'erro',
            'texto' => 'Erro: ' . $e->getMessage()
        ];
    }
}

// Busca usuários
$search = $_GET['search'] ?? '';
$query = "SELECT u.* FROM Usuario u WHERE u.nome LIKE ? OR u.email LIKE ?";
$params = ["%$search%", "%$search%"];
$stmt = $conn->prepare($query);
$stmt->execute($params);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração de Usuários</title>
    <style>
        .acao { white-space: nowrap; }
        .form-edicao { background-color: #f0f8ff; padding: 15px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Mensagens -->
        <?php if (!empty($_SESSION['mensagem'])): ?>
            <div class="mensagem <?= $_SESSION['mensagem']['tipo'] ?>">
                <?= $_SESSION['mensagem']['texto'] ?>
            </div>
            <?php unset($_SESSION['mensagem']); ?>
        <?php endif; ?>

        <!-- Formulário de Edição/Cadastro -->
        <div class="<?= $usuarioEdicao ? 'form-edicao' : '' ?>">
            <h2><?= $usuarioEdicao ? 'Editar Usuário' : 'Adicionar Novo Usuário' ?></h2>
            <form method="POST">
                <?php if ($usuarioEdicao): ?>
                    <input type="hidden" name="id_edicao" value="1">
                <?php endif; ?>
                
                <div>
                    <label>E-mail:</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($usuarioEdicao['email'] ?? '') ?>" <?= $usuarioEdicao ? 'readonly' : 'required' ?>>
                </div>
                
                <div>
                    <label>Nome:</label>
                    <input type="text" name="nome" value="<?= htmlspecialchars($usuarioEdicao['nome'] ?? '') ?>" required>
                </div>
                
                <div>
                    <label>Data Nascimento:</label>
                    <input type="date" name="datanasc" value="<?= htmlspecialchars($usuarioEdicao['datanasc'] ?? '') ?>">
                </div>
                
                <div>
                    <label>Tipo:</label>
                    <select name="tipo" required>
                        <option value="">Selecione...</option>
                        <option value="Administrador" <?= ($usuarioEdicao['tipo'] ?? '') == 'Administrador' ? 'selected' : '' ?>>Administrador</option>
                        <option value="Cozinheiro" <?= ($usuarioEdicao['tipo'] ?? '') == 'Cozinheiro' ? 'selected' : '' ?>>Cozinheiro</option>
                        <option value="Degustador" <?= ($usuarioEdicao['tipo'] ?? '') == 'Degustador' ? 'selected' : '' ?>>Degustador</option>
                        <option value="Editor" <?= ($usuarioEdicao['tipo'] ?? '') == 'Editor' ? 'selected' : '' ?>>Editor</option>
                    </select>
                </div>
                
                <button type="submit"><?= $usuarioEdicao ? 'Atualizar' : 'Cadastrar' ?></button>
                <?php if ($usuarioEdicao): ?>
                    <a href="cargosADM.php">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Listagem de Usuários -->
        <h2>Lista de Usuários</h2>
        <form method="GET" class="search-bar">
            <input type="text" name="search" placeholder="Pesquisar..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Buscar</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>E-mail</th>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Data Nasc.</th>
                    <th class="acao">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><?= htmlspecialchars($usuario['email']) ?></td>
                    <td><?= htmlspecialchars($usuario['nome']) ?></td>
                    <td><?= htmlspecialchars($usuario['tipo']) ?></td>
                    <td><?= htmlspecialchars($usuario['datanasc']) ?></td>
                    <td class="acao">
                        <a href="cargosADM.php?editar=<?= urlencode($usuario['email']) ?>">Editar</a> |
                        <a href="cargosADM.php?excluir=<?= urlencode($usuario['email']) ?>" onclick="return confirm('Tem certeza que deseja excluir este usuário?')">Excluir</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>