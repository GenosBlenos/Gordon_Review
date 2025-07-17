<?php
session_start();
require __DIR__ . '/../app/funcoes.php';

// Padronização: garantir inclusão do menu e estrutura HTML
if (!isset($_SESSION['logado']) || !isAdmin()) {
    header("Location: login.php");
    exit();
}
require __DIR__ . '/../app/conexao.php';
include __DIR__ . '/../app/menu.php';

$error = '';
$success = '';
$nome = $cidade = $telefone = $email = $endereco = $bairro = $cep = $estado = $rg = $cic = $codbarras = '';

// Função para validar CPF
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

// Função para validar RG (básica)
function validar_rg($rg) {
    $rg = preg_replace('/[^0-9]/', '', $rg);
    return (strlen($rg) >= 7 && strlen($rg) <= 9);
}

// Processar cadastro de novo usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_usuario'])) {
    $nome = $_POST['nome'] ?? '';
    // Converter para "SOBRENOME, NOME"
    $nome = trim($nome);
    if (strpos($nome, ' ') !== false) {
        $partes = explode(' ', $nome);
        $sobrenome = array_pop($partes);
        $nome = $sobrenome . ', ' . implode(' ', $partes);
    }
    $cidade = $_POST['cidade'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    $bairro = $_POST['bairro'] ?? '';
    $cep = $_POST['cep'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $rg = $_POST['rg'] ?? '';
    $cic = $_POST['cic'] ?? '';
    $codbarras = $_POST['codbarras'] ?? '';

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
        // Verificar se o e-mail, CPF ou RG já existem
        $stmt_check = $conn->prepare("SELECT COUNT(*) AS total FROM pm_usua WHERE EMAIL = ? OR CIC = ? OR RG = ?");
        $stmt_check->bind_param("sss", $email, $cic, $rg);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $row = $result_check->fetch_assoc();
        
        if ($row['total'] > 0) {
            // Descobrir qual campo está duplicado
            $stmt_dup = $conn->prepare("SELECT EMAIL, CIC, RG FROM pm_usua WHERE EMAIL = ? OR CIC = ? OR RG = ? LIMIT 1");
            $stmt_dup->bind_param("sss", $email, $cic, $rg);
            $stmt_dup->execute();
            $dup = $stmt_dup->get_result()->fetch_assoc();
            if ($dup['EMAIL'] === $email) {
                $error = 'Este e-mail já está cadastrado.';
            } elseif ($dup['CIC'] === $cic) {
                $error = 'Este CPF já está cadastrado.';
            } elseif ($dup['RG'] === $rg) {
                $error = 'Este RG já está cadastrado.';
            } else {
                $error = 'Dados já cadastrados.';
            }
        } else {
            // Gerar próximo código disponível
            $result_codigo = $conn->query("SELECT MAX(CODIGO) AS max_codigo FROM pm_usua");
            $max_codigo = $result_codigo->fetch_assoc()['max_codigo'] ?? 0;
            $novo_codigo = $max_codigo + 1;

            // Hash da senha
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $data_cadastro = date('Y-m-d');
            $qtd = 1;
            // Inserir novo usuário com todos os campos obrigatórios
            $stmt = $conn->prepare("INSERT INTO pm_usua (CODIGO, NOME, ENDERECO, CIDADE, BAIRRO, CEP, ESTADO, RG, CIC, TELEFONE, CODBARRAS, QTD, DATACAD, EMAIL, SENHA) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssssssisss", $novo_codigo, $nome, $endereco, $cidade, $bairro, $cep, $estado, $rg, $cic, $telefone, $codbarras, $qtd, $data_cadastro, $email, $senha_hash);
            
            if ($stmt->execute()) {
                $success = 'Usuário cadastrado com sucesso! Código: ' . $novo_codigo;
                // Limpar os campos após sucesso
                $nome = $cidade = $telefone = $email = $endereco = $bairro = $cep = $estado = $rg = $cic = $codbarras = '';
            } else {
                $error = 'Erro ao cadastrar usuário: ' . $stmt->error;
            }
        }
    }
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Novo Usuário</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../styles/sense.css" rel="stylesheet">
    <style>
        /* ...existing style... */
    </style>
</head>
<body>
    <?php include __DIR__ . '/../app/menu.php'; ?>
    <div class="dp-banner">
        <div class="container text-center">
            <h1><i class="fas fa-user-plus me-3"></i>Cadastro de Usuário</h1>
            <p class="lead">Adicione novos usuários ao sistema e gerencie informações cadastrais</p>
            <div class="mt-4">
                <span class="badge bg-light text-dark me-2"><i class="fas fa-user me-1"></i> Novo Usuário</span>
                <span class="badge bg-light text-dark me-2"><i class="fas fa-id-card me-1"></i> Dados Pessoais</span>
            </div>
        </div>
    </div>
    <!-- Padronização de mensagens -->
    <?php if ($error): ?>
        <div class="alert alert-danger text-center">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success text-center">
            <?= htmlspecialchars($success) ?>
            <a href="usuario.php" class="btn btn-sm btn-success ms-3">Ver Usuários</a>
        </div>
    <?php endif; ?>
    <div class="container mt-4">
        <h2 class="mb-4">➕ Cadastrar Novo Usuário</h2>
        
        <div class="card">
            <div class="card-header card-header-custom">
                <h5 class="mb-0">Informações do Usuário</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="cadastroForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required-field">Nome Completo</label>
                                <input type="text" name="nome" class="form-control" 
                                       value="<?= htmlspecialchars($nome) ?>" 
                                       placeholder="Digite o nome completo" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required-field">Endereço</label>
                                <input type="text" name="endereco" class="form-control" 
                                       value="<?= htmlspecialchars($endereco) ?>" 
                                       placeholder="Digite o endereço" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Bairro</label>
                                <input type="text" name="bairro" class="form-control" 
                                       value="<?= htmlspecialchars($bairro) ?>" 
                                       placeholder="Digite o bairro">
                            </div>
                            <div class="mb-3">
                                <label class="form-label required-field">Cidade</label>
                                <input type="text" name="cidade" class="form-control" 
                                       value="<?= htmlspecialchars($cidade) ?>" 
                                       placeholder="Digite a cidade" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required-field">CEP</label>
                                <input type="text" name="cep" class="form-control" 
                                       value="<?= htmlspecialchars($cep) ?>" 
                                       placeholder="Digite o CEP" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required-field">Estado</label>
                                <input type="text" name="estado" class="form-control" 
                                       value="<?= htmlspecialchars($estado) ?>" 
                                       placeholder="Digite o estado (UF)" required maxlength="2">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required-field">RG</label>
                                <input type="text" name="rg" class="form-control" 
                                       value="<?= htmlspecialchars($rg) ?>" 
                                       placeholder="Digite o RG" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required-field">CPF</label>
                                <input type="text" name="cic" class="form-control" 
                                       value="<?= htmlspecialchars($cic) ?>" 
                                       placeholder="Digite o CPF" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required-field">Telefone</label>
                                <input type="text" name="telefone" class="form-control" 
                                       value="<?= htmlspecialchars($telefone) ?>" 
                                       placeholder="Digite o telefone com DDD" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Código de Barras</label>
                                <input type="text" name="codbarras" class="form-control" 
                                       value="<?= htmlspecialchars($codbarras) ?>" 
                                       placeholder="Digite o código de barras">
                            </div>
                            <div class="mb-3">
                                <label class="form-label required-field">E-mail</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($email) ?>" 
                                       placeholder="Digite o e-mail" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3 password-container">
                                <label class="form-label required-field">Senha</label>
                                <input type="password" name="senha" id="senha" class="form-control" 
                                       placeholder="Digite a senha" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('senha')">
                                    👁️
                                </button>
                                <div id="password-strength" class="password-strength strength-0"></div>
                                <small class="form-text text-muted">Mínimo 8 caracteres, com letras e números</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3 password-container">
                                <label class="form-label required-field">Confirmar Senha</label>
                                <input type="password" name="confirmar_senha" id="confirmar_senha" 
                                       class="form-control" placeholder="Confirme a senha" required>
                                <button style="padding-top: 6%;" type="button" class="password-toggle" onclick="togglePassword('confirmar_senha')">
                                    👁️
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="usuario.php" class="btn btn-secondary">
                            ↩️ Voltar para Usuários
                        </a>
                        <button type="submit" name="cadastrar_usuario" class="btn btn-primary">
                            ✅ Cadastrar Usuário
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Alternar visibilidade da senha
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
        }
        
        // Validar força da senha
        document.getElementById('senha').addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthBar = document.getElementById('password-strength');
            
            // Resetar barra
            strengthBar.className = 'password-strength';
            
            if (password.length === 0) {
                return;
            }
            
            // Calcular força
            let strength = 0;
            
            // Comprimento
            if (password.length > 7) strength += 1;
            
            // Letras maiúsculas e minúsculas
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 1;
            
            // Números
            if (/\d/.test(password)) strength += 1;
            
            // Caracteres especiais
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            // Atualizar barra de força
            strengthBar.classList.add(`strength-${strength}`);
        });
        
        // Validação do formulário
        document.getElementById('cadastroForm').addEventListener('submit', function(e) {
            const senha = document.getElementById('senha').value;
            const confirmarSenha = document.getElementById('confirmar_senha').value;
            
            if (senha.length < 8) {
                alert('A senha deve ter no mínimo 8 caracteres');
                e.preventDefault();
                return;
            }
            
            if (senha !== confirmarSenha) {
                alert('As senhas não coincidem');
                e.preventDefault();
            }
        });
    </script>
</body>
</html>