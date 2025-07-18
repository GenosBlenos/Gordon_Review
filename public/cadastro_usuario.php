<?php
session_start();
require __DIR__ . '/../app/funcoes.php';
require __DIR__ . '/../app/conexao.php';

if (!isset($_SESSION['logado']) || !isAdmin()) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// Inicializa variáveis do formulário
$nome = $cidade = $telefone = $email = $endereco = $bairro = $cep = $estado = $rg = $cic = '';

// Funções de validação
function validar_cpf($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) return false;
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) return false;
    }
    return true;
}
function validar_rg($rg) {
    $rg = preg_replace('/[^0-9]/', '', $rg);
    return (strlen($rg) >= 7 && strlen($rg) <= 9);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_usuario'])) {
    $nome = trim($_POST['nome'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $endereco = trim($_POST['endereco'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');
    $cep = trim($_POST['cep'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $rg = trim($_POST['rg'] ?? '');
    $cic = trim($_POST['cic'] ?? '');

    // Validações
    if (empty($nome) || empty($senha) || empty($confirmar_senha) || empty($endereco) || empty($cep) || empty($estado) || empty($rg) || empty($cic) || empty($email)) {
        $error = 'Preencha todos os campos obrigatórios.';
    } elseif ($senha !== $confirmar_senha) {
        $error = 'As senhas não coincidem.';
    } elseif (!validar_cpf($cic)) {
        $error = 'CPF inválido.';
    } elseif (!validar_rg($rg)) {
        $error = 'RG inválido.';
    } else {
        // Verificar duplicidade
        $stmt_check = $conn->prepare("SELECT COUNT(*) AS total FROM pm_usua WHERE EMAIL = ? OR CIC = ? OR RG = ?");
        $stmt_check->bind_param("sss", $email, $cic, $rg);
        $stmt_check->execute();
        $row = $stmt_check->get_result()->fetch_assoc();
        if ($row['total'] > 0) {
            $error = 'E-mail, CPF ou RG já cadastrado.';
        } else {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $data_cadastro = date('Y-m-d');
            $qtd = 1;
            $stmt = $conn->prepare("INSERT INTO pm_usua (NOME, ENDERECO, CIDADE, BAIRRO, CEP, ESTADO, RG, CIC, TELEFONE, QTD, DATACAD, EMAIL, SENHA) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssissss", $nome, $endereco, $cidade, $bairro, $cep, $estado, $rg, $cic, $telefone, $qtd, $data_cadastro, $email, $senha_hash);
            if ($stmt->execute()) {
                $success = 'Usuário cadastrado com sucesso!';
                $nome = $cidade = $telefone = $email = $endereco = $bairro = $cep = $estado = $rg = $cic = '';
            } else {
                $error = 'Erro ao cadastrar usuário: ' . $stmt->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Novo Usuário</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../styles/sense.css" rel="stylesheet">
    <style>
        body { background: #f4f6fa; font-family: 'Segoe UI', Arial, sans-serif; }
        .card { border-radius: 18px; box-shadow: 0 4px 18px rgba(0,0,0,0.08); }
        .dp-banner { background: linear-gradient(90deg, #283e51 0%, #485563 100%); padding: 32px 0 24px 0; margin-bottom: 32px; }
        .dp-banner h1 { color: #fff; }
        .dp-banner .lead { color: #f5f5f5; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../app/menu.php'; ?>
    <div class="dp-banner">
        <div class="container text-center">
            <h1><i class="fas fa-user-plus me-3"></i>Cadastro de Usuário</h1>
            <p class="lead">Adicione novos usuários ao sistema e gerencie informações cadastrais</p>
        </div>
    </div>
    <div class="container mt-4">
        <?php if ($error): ?>
            <div class="alert alert-danger text-center"> <?= htmlspecialchars($error) ?> </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success text-center"> <?= htmlspecialchars($success) ?> <a href="usuario.php" class="btn btn-sm btn-success ms-3">Ver Usuários</a></div>
        <?php endif; ?>
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Informações do Usuário</h5></div>
            <div class="card-body">
                <form method="POST" id="cadastroForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required-field">Nome Completo</label>
                                <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($nome) ?>" placeholder="Digite o nome completo" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required-field">Endereço</label>
                                <input type="text" name="endereco" class="form-control" value="<?= htmlspecialchars($endereco) ?>" placeholder="Digite o endereço" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Bairro</label>
                                <input type="text" name="bairro" class="form-control" value="<?= htmlspecialchars($bairro) ?>" placeholder="Digite o bairro">
                            </div>
                            <div class="mb-3">
                                <label class="form-label required-field">Cidade</label>
                                <input type="text" name="cidade" class="form-control" value="<?= htmlspecialchars($cidade) ?>" placeholder="Digite a cidade" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required-field">CEP</label>
                                <input type="text" name="cep" class="form-control" value="<?= htmlspecialchars($cep) ?>" placeholder="Digite o CEP" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required-field">Estado</label>
                                <input type="text" name="estado" class="form-control" value="<?= htmlspecialchars($estado) ?>" placeholder="Digite o estado (UF)" required maxlength="2">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required-field">RG</label>
                                <input type="text" name="rg" class="form-control" value="<?= htmlspecialchars($rg) ?>" placeholder="Digite o RG" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required-field">CPF</label>
                                <input type="text" name="cic" class="form-control" value="<?= htmlspecialchars($cic) ?>" placeholder="Digite o CPF" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required-field">Telefone</label>
                                <input type="text" name="telefone" class="form-control" value="<?= htmlspecialchars($telefone) ?>" placeholder="Digite o telefone com DDD" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required-field">E-mail</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" placeholder="Digite o e-mail" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3 password-container">
                                <label class="form-label required-field">Senha</label>
                                <input type="password" name="senha" id="senha" class="form-control" placeholder="Digite a senha" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 password-container">
                                <label class="form-label required-field">Confirmar Senha</label>
                                <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" placeholder="Confirme a senha" required>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <a href="usuario.php" class="btn btn-secondary">↩️ Voltar para Usuários</a>
                        <button type="submit" name="cadastrar_usuario" class="btn btn-primary">✅ Cadastrar Usuário</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Limpa cada campo individualmente ao carregar a página
        window.addEventListener('DOMContentLoaded', function() {
            document.querySelector('input[name="nome"]').value = '';
            document.querySelector('input[name="endereco"]').value = '';
            document.querySelector('input[name="bairro"]').value = '';
            document.querySelector('input[name="cidade"]').value = '';
            document.querySelector('input[name="cep"]').value = '';
            document.querySelector('input[name="estado"]').value = '';
            document.querySelector('input[name="rg"]').value = '';
            document.querySelector('input[name="cic"]').value = '';
            document.querySelector('input[name="telefone"]').value = '';
            document.querySelector('input[name="email"]').value = '';
            document.querySelector('input[name="senha"]').value = '';
            document.querySelector('input[name="confirmar_senha"]').value = '';
        });
    </script>
</body>
</html>