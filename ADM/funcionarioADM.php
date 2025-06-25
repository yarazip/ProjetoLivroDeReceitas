<?php
session_start();
if (!isset($_SESSION['id_funcionario'])) {
    header("Location: ../LoginSenha/login.php");
    exit();
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../BancoDeDados/conexao.php';

// Verifica se o usuário está logado e se tem permissão de Administrador
if (!isset($_SESSION['id_login']) || $_SESSION['cargo'] !== 'administrador') {
    // $_SESSION['message'] = "Você não tem permissão para acessar esta área.";
    $_SESSION['message_type'] = "error";
    header("Location: ../LoginSenha/login.php");
    exit;
}

// Lógica de PESQUISAR FUNCIONÁRIO
$termo = trim($_GET['pesquisa'] ?? '');

$sql = "SELECT f.*, c.nome AS nome_cargo, l.email
        FROM funcionarios f
        JOIN cargos c ON f.id_cargo = c.id_cargo
        LEFT JOIN logins l ON f.id_funcionario = l.id_funcionario
        WHERE f.nome LIKE ?"; // Pesquisa apenas pelo nome do funcionário
$stmt = $conn->prepare($sql);
$stmt->execute(["%$termo%"]);
$funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="../../styles/funcionario.css" />
    <title>Funcionários Administração</title>

</head>

<body>
    <a href="../LoginSenha/logout.php" class="logout-button">
        <i class="fa-solid fa-right-from-bracket fa-lg gray-icon"></i>
    </a>
    <div class="container">
        <div class="menu">
            <h1 class="logo">Código de Sabores</h1>
            <?php
            function isActive($page)
            {
                return basename($_SERVER['PHP_SELF']) == $page ? 'active' : '';
            }
            ?>

            <nav>
                <a href="cargosADM.php" class="<?= isActive('cargosADM.php') ?>">Cargo</a>
                <a href="restauranteADM.php" class="<?= isActive('restauranteADM.php') ?>">Restaurantes</a>
                <a href="funcionarioADM.php" class="<?= isActive('funcionarioADM.php') ?>">Funcionário</a>
                <a href="referenciaADM.php" class="<?= isActive('referenciaADM.php') ?>">Referência</a>
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?= htmlspecialchars($_SESSION['nome_funcionario'] ?? 'Desconhecido') ?></span>
                </div>
            </nav>
        </div>

        <?php
        // Exibir mensagens de feedback
        if (isset($_SESSION['message'])): ?>
            <div class="message-<?= $_SESSION['message_type'] ?? 'info' ?>">
                <?= htmlspecialchars($_SESSION['message']) ?>
            </div>
        <?php
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        endif;
        ?>

        <h2>Pesquisar Funcionários</h2>
        <div class="search-bar">
            <form method="GET">
                <label for="pesquisa">Pesquisar por Nome:</label>
                <input type="text" id="pesquisa" name="pesquisa" placeholder="Ex: João da Silva" value="<?= htmlspecialchars($termo) ?>">
                <button type="submit">Pesquisar</button>
                <?php if (!empty($termo)): ?>
                    <a href="funcionarioADM.php" class="clear-filters-button">Limpar Pesquisa</a>
                <?php endif; ?>
            </form>
        </div>

        <hr>

        <div class="add-button-container">
            <a href="adicionarFuncionario.php" class="add-button">Adicionar Novo Funcionário</a>
        </div>

        <h2>Lista de Funcionários</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>CPF</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Data de Admissão</th>
                    <th>Salário</th>
                    <th>Cargo</th>
                    <th>Descrição</th>
                    <th>Foto</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($funcionarios)): ?>
                    <tr>
                        <td colspan="10">Nenhum funcionário encontrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($funcionarios as $f): ?>
                        <tr>
                            <td><?= htmlspecialchars($f['id_funcionario'] ?? '') ?></td>
                            <td>
                                <?php
                                $cpf_valor = $f['cpf'] ?? ''; // Pega o valor do CPF, ou string vazia se NULL

                                if (!empty($cpf_valor)) { // Só tenta processar se não for vazio/nulo
                                    // Remove qualquer caractere não numérico e pega os primeiros 11 dígitos
                                    $cpf_limpo_e_limitado = substr(preg_replace('/\D/', '', $cpf_valor), 0, 11);

                                    // Se tiver exatamente 11 dígitos, formata; caso contrário, exibe como está.
                                    if (strlen($cpf_limpo_e_limitado) === 11) {
                                        $cpf_formatado = preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "$1.$2.$3-$4", $cpf_limpo_e_limitado);
                                        echo htmlspecialchars($cpf_formatado);
                                    } else {
                                        // Se não tiver 11 dígitos, exibe o CPF limpo (e limitado) sem formatação
                                        echo htmlspecialchars($cpf_limpo_e_limitado);
                                    }
                                } else {
                                    // Se o CPF for vazio ou nulo, exibe "N/A"
                                    echo "N/A";
                                }
                                ?>
                            </td>
                            <td><?= htmlspecialchars($f['nome'] ?? '') ?></td>
                            <td><?= htmlspecialchars($f['email'] ?? 'Sem email') ?></td>
                            <td><?= htmlspecialchars($f['data_admissao'] ?? '') ?></td>
                            <td>R$ <?= number_format($f['salario'] ?? 0, 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($f['nome_cargo'] ?? '') ?></td>
                            <td><?= htmlspecialchars($f['descricao'] ?? '') ?></td>
                            <td>
                                <?php if (!empty($f['foto_funcionario'])): ?>
                                    <img src="data:image/jpeg;base64,<?= base64_encode($f['foto_funcionario']) ?>" alt="Foto" class="foto-funcionario" />
                                <?php else: ?>
                                    Sem foto
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="funcionarioAcoes/consultarFuncionario.php?id=<?= htmlspecialchars($f['id_funcionario'] ?? '') ?>" class="view-button" title="Consultar">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="editarFuncionario.php?id=<?= htmlspecialchars($f['id_funcionario'] ?? '') ?>" class="edit-button" title="Editar">
                                        <i class="fas fa-pencil-alt"></i>
                                    </a>
                                    <a href="funcionarioAcoes/confirmarExclusaoFuncionario.php?id=<?= htmlspecialchars($f['id_funcionario'] ?? '') ?>" class="delete-button" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>