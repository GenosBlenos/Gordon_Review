<?php
session_start();
require __DIR__ . '/../app/funcoes.php';
require __DIR__ . '/../app/conexao.php';

if (!isset($_SESSION['logado']) || !isAdmin()) {
    header("Location: login.php");
    exit();
}
$usuario_id = isset($_GET['usuario']) ? (int)$_GET['usuario'] : 0;
$usuario = null;
$pagamentos = null;
if ($usuario_id > 0) {
    $stmt = $conn->prepare("SELECT NOME FROM pm_usua WHERE CODIGO = ?");
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $usuario = $res->fetch_assoc();
    }
    $stmt->close();
    $stmt2 = $conn->prepare("SELECT * FROM pagamentos WHERE usuario_id = ? ORDER BY data_pagamento DESC");
    $stmt2->bind_param('i', $usuario_id);
    $stmt2->execute();
    $pagamentos = $stmt2->get_result();
} else {
    // Se não houver filtro de usuário, mostra todos os pagamentos
    $pagamentos = $conn->query("SELECT p.*, u.NOME as usuario_nome FROM pagamentos p LEFT JOIN pm_usua u ON p.usuario_id = u.CODIGO ORDER BY p.data_pagamento DESC");
}
function gerarVerificador($pagamento) {
    return substr(sha1($pagamento['id'].$pagamento['usuario_id'].$pagamento['valor'].$pagamento['data_pagamento']),0,10);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Extrato de Pagamentos de Multa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../styles/sense.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../app/menu.php'; ?>
    <div class="dp-banner">
        <div class="container text-center">
            <h1><i class="fas fa-file-invoice-dollar me-3"></i>Extrato de Pagamentos de Multa</h1>
            <p class="lead">Consulte os pagamentos de multas realizados e acesse comprovantes</p>
            <div class="mt-4">
                <span class="badge bg-light text-dark me-2"><i class="fas fa-search me-1"></i> Pesquisa</span>
                <span class="badge bg-light text-dark me-2"><i class="fas fa-file-alt me-1"></i> Comprovantes</span>
            </div>
        </div>
    </div>
    <div class="container deep">
        <div class="extrato-box bg-white shadow-sm mt-4 mb-4 p-4 rounded-4">
            <h3 class="mb-4 text-center">Extrato de Pagamentos de Multa</h3>
            <p class="mb-3 alert alert-info text-center" style="font-size: 1rem;">
                <strong>O código verificador</strong> garante a autenticidade do comprovante de pagamento.<br>
                Guarde-o ou informe para conferência do comprovante.<br>
                <span style="font-size:0.95em; color:#888;">Clique em "Ver" para acessar o comprovante detalhado.</span>
            </p>
            <?php if ($usuario): ?>
                <p class="text-center"><strong>Usuário:</strong> <?= htmlspecialchars($usuario['NOME']) ?> (Cód: <?= $usuario_id ?>)</p>
            <?php endif; ?>
            <div class="table-responsive">
            <table class="tabela mb-4">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Valor Pago</th>
                        <th>Data do Pagamento</th>
                        <th>Código Verificador</th>
                        <th>Comprovante</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($pagamentos && $pagamentos->num_rows > 0): ?>
                    <?php while ($p = $pagamentos->fetch_assoc()): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td>R$ <?= number_format($p['valor'],2,',','.') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($p['data_pagamento'])) ?></td>
                            <td><span class="badge bg-primary" style="font-family:monospace; font-size:1em;"><?= gerarVerificador($p) ?></span></td>
                            <td><a href="comprovante_multa.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center">Nenhum pagamento encontrado.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</body>
</html>
