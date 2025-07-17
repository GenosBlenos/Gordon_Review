<?php
session_start();
require __DIR__ . '/../app/funcoes.php';
require __DIR__ . '/../app/conexao.php';

if (!isset($_SESSION['logado']) || !isAdmin()) {
    header("Location: login.php");
    exit();
}

$pagamento_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pagamento = null;
$usuario = null;
$erro = '';
if ($pagamento_id > 0) {
    $stmt = $conn->prepare("SELECT p.*, u.NOME, u.CODIGO FROM pagamentos p JOIN pm_usua u ON p.usuario_id = u.CODIGO WHERE p.id = ?");
    $stmt->bind_param('i', $pagamento_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $pagamento = $res->fetch_assoc();
    } else {
        $erro = 'Pagamento não encontrado.';
    }
    $stmt->close();
} else {
    $erro = 'ID de pagamento inválido.';
}
function gerarVerificador($pagamento) {
    return substr(sha1($pagamento['id'].$pagamento['usuario_id'].$pagamento['valor'].$pagamento['data_pagamento']),0,10);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprovante de Pagamento de Multa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../styles/sense.css" rel="stylesheet">
    <style>
        .nf-box {
            max-width: 420px;
            margin: 40px auto;
            background: #fff;
            border: 2px dashed #0d6efd;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.08);
            padding: 2.5rem 2rem;
            font-family: 'Courier New', Courier, monospace;
        }
        .nf-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .nf-title {
            font-size: 1.3rem;
            font-weight: bold;
            color: #0d6efd;
            letter-spacing: 1px;
        }
        .nf-logo {
            width: 48px;
            margin-bottom: 8px;
        }
        .nf-table {
            width: 100%;
            margin-bottom: 1.2rem;
        }
        .nf-table th, .nf-table td {
            padding: 6px 0;
            font-size: 1rem;
            border-bottom: 1px solid #eee;
        }
        .nf-table th {
            text-align: left;
            color: #555;
            font-weight: 600;
        }
        .nf-table td {
            text-align: right;
            color: #222;
        }
        .nf-verificador {
            font-family: monospace;
            font-size: 1.1em;
            color: #0d6efd;
            background: #f1f8ff;
            padding: 4px 10px;
            border-radius: 6px;
            display: inline-block;
        }
        .nf-footer {
            text-align: center;
            font-size: 0.95em;
            color: #888;
            margin-top: 1.5rem;
        }
        @media (max-width: 480px) {
            .nf-box { padding: 1.2rem 0.5rem; }
            .nf-title { font-size: 1.1rem; }
        }
    </style>
</head>
<body>
    <div class="nf-box">
        <div class="nf-header">
            <img src="../src/gordon.jpg" alt="Logo Biblioteca" class="nf-logo">
            <div class="nf-title">Comprovante de Pagamento de Multa</div>
            <div style="font-size:0.95em; color:#555;">Biblioteca GORDON</div>
        </div>
        <?php if ($erro): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($erro) ?></div>
        <?php elseif ($pagamento): ?>
            <table class="nf-table">
                <tr><th>Usuário</th><td><?= htmlspecialchars($pagamento['NOME']) ?> (<?= $pagamento['usuario_id'] ?>)</td></tr>
                <tr><th>Valor Pago</th><td>R$ <?= number_format($pagamento['valor'],2,',','.') ?></td></tr>
                <tr><th>Data do Pagamento</th><td><?= date('d/m/Y H:i', strtotime($pagamento['data_pagamento'])) ?></td></tr>
                <tr><th>ID do Pagamento</th><td><?= $pagamento['id'] ?></td></tr>
                <tr><th>Código Verificador</th><td><span class="nf-verificador"><?= gerarVerificador($pagamento) ?></span></td></tr>
            </table>
            <div class="nf-footer">
                <strong>Guarde este comprovante.</strong><br>
                A autenticidade pode ser verificada pelo código verificador.<br>
                <span style="font-size:0.92em;">Este documento não é válido como nota fiscal eletrônica oficial.</span>
            </div>
        <?php endif; ?>
        <div class="mt-4 d-flex flex-column flex-md-row gap-2 justify-content-center">
            <a href="extrato_multas.php?usuario=<?= $pagamento ? $pagamento['usuario_id'] : '' ?>" class="btn btn-outline-primary w-100 w-md-auto">Ver Extrato/Histórico</a>
            <a href="usuario.php" class="btn btn-secondary w-100 w-md-auto">Voltar</a>
        </div>
    </div>
</body>
</html>
