<?php
// Ativar relatórios de erro completos
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require __DIR__ . '/../app/conexao.php';

if (isset($_POST['entrar'])) {
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);

    try {
        // 1. Tenta buscar como admin
        $stmt = $conn->prepare("SELECT * FROM pm_admin WHERE EMAIL = ?");
        if (!$stmt) {
            throw new Exception("Erro no prepare: " . $conn->error);
        }
        $stmt->bind_param("s", $email);
        if (!$stmt->execute()) {
            throw new Exception("Erro na execução: " . $stmt->error);
        }
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $usuario = $result->fetch_assoc();
            $role = 'admin';
            $_SESSION['usuario_id'] = $usuario['CODIGO'];
            $_SESSION['nome'] = $usuario['NOME']; // <-- Adicionado para admin
        } else {
            // 2. Se não achou, tenta como usuário padrão
            $stmt = $conn->prepare("SELECT * FROM pm_usua WHERE EMAIL = ?");
            if (!$stmt) {
                throw new Exception("Erro no prepare (usuarios): " . $conn->error);
            }
            $stmt->bind_param("s", $email);
            if (!$stmt->execute()) {
                throw new Exception("Erro na execução (usuarios): " . $stmt->error);
            }
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                throw new Exception("Usuário não encontrado");
            }
            $usuario = $result->fetch_assoc();
            $role = 'usuario';
            $_SESSION['usuario_id'] = $usuario['CODIGO'];
            $_SESSION['nome'] = $usuario['NOME']; // <-- Adicionado para usuário
        }

        // DEBUG: Verificar conteúdo
        error_log("Dados do usuário: " . print_r($usuario, true));
        error_log("Senha digitada: '$senha'");
        error_log("Hash no banco: '{$usuario['SENHA']}'");
        error_log("Tamanho do hash: " . strlen($usuario['SENHA']));

        // 1. Tentativa com password_verify
        if (password_verify($senha, $usuario['SENHA'])) {
            error_log("Autenticação via password_verify");
            $_SESSION['logado'] = true;
            $_SESSION['admin_nome'] = $usuario['NOME'];
            $_SESSION['role'] = $role;
            header("Location: home.php");
            exit();
        }
        // 2. Comparação direta para emergências
        elseif ($senha === $usuario['SENHA']) {
            error_log("Autenticação via comparação direta");
            $_SESSION['logado'] = true;
            $_SESSION['admin_nome'] = $usuario['NOME'];
            $_SESSION['role'] = $role;
            header("Location: home.php");
            exit();
        }
        // 3. Fallback para hashes antigos
        elseif (md5($senha) === $usuario['SENHA']) {
            error_log("Autenticação via MD5");
            $_SESSION['logado'] = true;
            $_SESSION['admin_nome'] = $usuario['NOME'];
            $_SESSION['role'] = $role;
            header("Location: home.php");
            exit();
        }
        else {
            throw new Exception("Senha incorreta");
        }
    } catch (Exception $e) {
        $erro = $e->getMessage();
        error_log("ERRO: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../styles/sense.css" rel="stylesheet">
    <style>
        .login-container {
            max-width: 500px;
            margin: 40px auto;
        }
        .card-header {
            background-color: #072a3a;
            padding: 20px;
            text-align: center;
            border-top-left-radius: 8px !important;
            border-top-right-radius: 8px !important;
        }
        .header-image {
            max-height: 60px;
            width: auto;
        }
    </style>
</head>

<body class="bg-light">
    <div class="login-container">
        <div class="card shadow">
            <!-- Cabeçalho com imagem dentro do container -->
            <div class="card-header">
                <img src="../src/gordon.jpg" alt="Gordon Logo" class="header-image">
            </div>
            
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Acesso do Sistema</h2>

                <?php if (isset($erro)): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($erro) ?>
                        <?php if (strpos($erro, 'Senha') !== false): ?>
                            <div class="mt-2">
                                <small>Senha padrão: admin123</small>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">E-mail:</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Senha:</label>
                        <input type="password" name="senha" class="form-control">
                    </div>
                    <button type="submit" name="entrar" class="btn btn-primary w-100">Entrar</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>