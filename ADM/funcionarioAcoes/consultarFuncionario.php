<?php
session_start();
if (!isset($_SESSION['id_funcionario'])) {
    header("Location: ../LoginSenha/login.php");
    exit();
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../BancoDeDados/conexao.php';

// Verifica se o usuário está logado e se tem permissão de Administrador
if (!isset($_SESSION['id_login']) || $_SESSION['cargo'] !== 'administrador') {
    // $_SESSION['message'] = "Você não tem permissão para acessar esta área.";
    $_SESSION['message_type'] = "error";
    header("Location: ../../LoginSenha/login.php");
    exit;
}

$id_funcionario_consulta = $_GET['id'] ?? null;

if (is_null($id_funcionario_consulta)) {
    $_SESSION['message'] = "ID do funcionário não fornecido para consulta.";
    $_SESSION['message_type'] = "error";
    header("Location: ../funcionarioADM.php");
    exit;
}

$funcionario_detalhes = null;

try {
    // Busca todos os dados do funcionário, incluindo cargo e email
    $sql = "SELECT f.*, c.nome AS nome_cargo, c.descricao AS descricao_cargo, l.email, l.descricao AS descricao_login
            FROM funcionarios f
            JOIN cargos c ON f.id_cargo = c.id_cargo
            LEFT JOIN logins l ON f.id_funcionario = l.id_funcionario
            WHERE f.id_funcionario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_funcionario_consulta]);
    $funcionario_detalhes = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$funcionario_detalhes) {
        $_SESSION['message'] = "Funcionário não encontrado.";
        $_SESSION['message_type'] = "error";
        header("Location: ../funcionarioADM.php");
        exit;
    }

    // Opcional: Buscar histórico de restaurantes para este funcionário
    $sql_historico = "SELECT hr.*, r.nome AS nome_restaurante, r.telefone AS telefone_restaurante
                      FROM historico_restaurante hr
                      JOIN restaurantes r ON hr.id_restaurante = r.id_restaurante
                      WHERE hr.id_funcionario = ? ORDER BY hr.data_inicio DESC";
    $stmt_historico = $conn->prepare($sql_historico);
    $stmt_historico->execute([$id_funcionario_consulta]);
    $historico_restaurantes = $stmt_historico->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao consultar funcionário: " . $e->getMessage());
    $_SESSION['message'] = "Erro ao carregar detalhes do funcionário: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
    header("Location: ../funcionarioADM.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../../assets/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="../../styles/consultaADM.css">
    <title>Consultar Funcionário | ADM</title>

</head>

<body>
    <a href="../LoginSenha/logout.php" class="logout-button">
        <i class="fa-solid fa-right-from-bracket fa-lg gray-icon"></i>
    </a>
    <div class="container">
        <div class="menu">
            <h1 class="logo">Código de Sabores</h1>
            <nav>
                <a href="../cargosADM.php">Cargo</a>
                <a href="../restauranteADM.php">Restaurantes</a>
                <a href="../funcionarioADM.php">Funcionário</a>
                <a href="../referenciaADM.php">Referência</a>
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?= htmlspecialchars($_SESSION['nome_funcionario'] ?? 'Desconhecido') ?></span>
                </div>
            </nav>
        </div>

        <div class="funcionario-details">
            <h2>Detalhes do Funcionário: <?= htmlspecialchars($funcionario_detalhes['nome'] ?? 'N/A') ?></h2>

            <div class="photo-section">
                <?php if (!empty($funcionario_detalhes['foto_funcionario'])): ?>
                    <img src="data:image/jpeg;base64,<?= base64_encode($funcionario_detalhes['foto_funcionario']) ?>" alt="Foto do Funcionário" />
                <?php else: ?>
                    <img src="../../assets/default_profile.png" alt="Sem Foto" />
                <?php endif; ?>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <strong>ID:</strong>
                    <span><?= htmlspecialchars($funcionario_detalhes['id_funcionario'] ?? 'N/A') ?></span>
                </div>
                <div class="info-item">
                    <strong>CPF:</strong>
                    <span>
                        <?php
                        $cpf_valor = $funcionario_detalhes['cpf'] ?? '';
                        if (!empty($cpf_valor)) {
                            $cpf_limpo_e_limitado = substr(preg_replace('/\D/', '', $cpf_valor), 0, 11);
                            if (strlen($cpf_limpo_e_limitado) === 11) {
                                $cpf_formatado = preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "$1.$2.$3-$4", $cpf_limpo_e_limitado);
                                echo htmlspecialchars($cpf_formatado);
                            } else {
                                echo htmlspecialchars($cpf_limpo_e_limitado);
                            }
                        } else {
                            echo "N/A";
                        }
                        ?>
                    </span>
                </div>
                <div class="info-item">
                    <strong>Nome:</strong>
                    <span><?= htmlspecialchars($funcionario_detalhes['nome'] ?? 'N/A') ?></span>
                </div>
                <div class="info-item">
                    <strong>Email de Login:</strong>
                    <span><?= htmlspecialchars($funcionario_detalhes['email'] ?? 'Não cadastrado') ?></span>
                </div>
                <div class="info-item">
                    <strong>Data de Admissão:</strong>
                    <span><?= htmlspecialchars($funcionario_detalhes['data_admissao'] ?? 'N/A') ?></span>
                </div>
                <div class="info-item">
                    <strong>Salário:</strong>
                    <span>R$ <?= number_format($funcionario_detalhes['salario'] ?? 0, 2, ',', '.') ?></span>
                </div>
                <div class="info-item">
                    <strong>Cargo:</strong>
                    <span><?= htmlspecialchars($funcionario_detalhes['nome_cargo'] ?? 'N/A') ?></span>
                </div>
                <div class="info-item full-width-item">
                    <strong>Descrição do Cargo:</strong>
                    <span><?= nl2br(htmlspecialchars($funcionario_detalhes['descricao_cargo'] ?? 'N/A')) ?></span>
                </div>
                <div class="info-item full-width-item">
                    <strong>Descrição do Funcionário:</strong>
                    <span><?= nl2br(htmlspecialchars($funcionario_detalhes['descricao'] ?? 'N/A')) ?></span>
                </div>
                <?php if (!empty($funcionario_detalhes['descricao_login'])): ?>
                    <div class="info-item full-width-item">
                        <strong>Descrição do Login:</strong>
                        <span><?= nl2br(htmlspecialchars($funcionario_detalhes['descricao_login'])) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($historico_restaurantes)): ?>
                <h3 class="section-title">Histórico em Restaurantes</h3>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Restaurante</th>
                            <th>Telefone Rest.</th>
                            <th>Data Início</th>
                            <th>Data Fim</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico_restaurantes as $hist): ?>
                            <tr>
                                <td><?= htmlspecialchars($hist['nome_restaurante'] ?? 'N/A') ?> (ID: <?= htmlspecialchars($hist['id_restaurante'] ?? 'N/A') ?>)</td>
                                <td><?= htmlspecialchars($hist['telefone_restaurante'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($hist['data_inicio'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($hist['data_fim'] ?? 'Atual') ?></td>
                                <td><?= nl2br(htmlspecialchars($hist['descricao'] ?? 'N/A')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; margin-top: 20px;">Nenhum histórico em restaurantes cadastrado para este funcionário.</p>
            <?php endif; ?>

            <a href="../funcionarioADM.php" class="back-button">Voltar para a Lista de Funcionários</a>
        </div>
    </div>
</body>

</html>