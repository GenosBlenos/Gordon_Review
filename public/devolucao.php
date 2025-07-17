<?php
ob_start();
session_start();
require __DIR__ . '/../app/funcoes.php';

if (!isset($_SESSION['logado']) || !isAdmin()) {
    header("Location: login.php");
    exit();
}
include __DIR__ . '/../app/conexao.php';
include __DIR__ . '/../app/menu.php';
?>
<div class="dp-banner">
    <div class="container text-center">
        <h1><i class="fas fa-undo-alt me-3"></i>Gestão de Devoluções</h1>
        <p class="lead">Registre e acompanhe devoluções de livros, multas e atrasos</p>
        <div class="mt-4">
            <span class="badge bg-light text-dark me-2"><i class="fas fa-calendar-check me-1"></i> Devoluções Pendentes</span>
            <span class="badge bg-light text-dark me-2"><i class="fas fa-money-bill-wave me-1"></i> Multas</span>
        </div>
    </div>
</div>

<?php
$termo_pesquisa = isset($_GET['pesquisa']) ? trim($_GET['pesquisa']) : '';

if (isset($_POST['devolver'])) {
    $emprestimo_id = (int) $_POST['emprestimo_id'];
    $data_devolucao = date('Y-m-d');

    $conn->begin_transaction();

    try {
        // Buscar dados do empréstimo usando o ID
        $stmt = $conn->prepare("SELECT EMPCODLIV, EMPADEV, EMPUSUA FROM emprest WHERE ID = ?");
        $stmt->bind_param("i", $emprestimo_id);
        $stmt->execute();
        $emprestimo = $stmt->get_result()->fetch_assoc();

        if (!$emprestimo) {
            throw new Exception("Empréstimo não encontrado");
        }

        $livro_id = $emprestimo['EMPCODLIV'];
        $data_prevista = $emprestimo['EMPADEV'];

        // Calcular dias de atraso
        $dias_atraso = 0;
        if ($data_devolucao > $data_prevista) {
            $d1 = new DateTime($data_prevista);
            $d2 = new DateTime($data_devolucao);
            $dias_atraso = $d2->diff($d1)->days;
        }
        // Calcular multa
        $multa = $dias_atraso * 3.00;

        // Liberar livro
        $stmt = $conn->prepare("UPDATE pm_acerv SET EMPRESTIMO = 'N' WHERE CODIGO = ?");
        $stmt->bind_param("i", $livro_id);
        $stmt->execute();

        // Atualizar empréstimo
        $stmt = $conn->prepare("
            UPDATE emprest 
            SET EMPPAGO = TRUE, 
                EMPDEVOLU = ?,
                EMPATRASO = ?,
                EMPMULTA = ?
            WHERE ID = ?
        ");
        $stmt->bind_param("sddi", $data_devolucao, $dias_atraso, $multa, $emprestimo_id);
        $stmt->execute();

        $conn->commit();

        // Mensagem de sucesso
        $msg = "Livro devolvido com sucesso!";
        if ($dias_atraso > 0) {
            $msg .= " Multa de R$" . number_format($multa, 2, ',', '.') . " ($dias_atraso dias de atraso)";
        }
        $_SESSION['success'] = $msg;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Erro: " . $e->getMessage();
    }
    header("Location: devolucao.php?pesquisa=" . urlencode($termo_pesquisa));
    exit();
}

// Paginação
$itens_por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$offset = max(0, ($pagina - 1) * $itens_por_pagina);

// Query para contagem
$count_query = "SELECT COUNT(*) as total 
                FROM emprest e 
                JOIN pm_acerv p ON e.EMPCODLIV = p.CODIGO 
                JOIN pm_usua u ON e.EMPUSUA = u.CODIGO 
                WHERE e.EMPPAGO = FALSE";

// Query principal
$sql = "SELECT e.ID AS emprestimo_id,
               e.EMPCODLIV,
               p.TITULO, 
               u.NOME, 
               e.EMPDATA, 
               e.EMPADEV,
               e.EMPDEVOLU,
               CASE 
                   WHEN CURDATE() > e.EMPADEV 
                   THEN DATEDIFF(CURDATE(), e.EMPADEV) 
                   ELSE 0 
               END AS dias_atraso,
               CASE 
                   WHEN CURDATE() > e.EMPADEV 
                   THEN DATEDIFF(CURDATE(), e.EMPADEV) * 3.00 
                   ELSE 0.00 
               END AS multa_devida
        FROM emprest e 
        JOIN pm_acerv p ON e.EMPCODLIV = p.CODIGO 
        JOIN pm_usua u ON e.EMPUSUA = u.CODIGO 
        WHERE e.EMPPAGO = FALSE AND p.EMPRESTIMO = 'S'";

// Adicionar condições de pesquisa se existirem
if (!empty($termo_pesquisa)) {
    $termo_like = "%{$termo_pesquisa}%";
    $id_pesquisa = is_numeric($termo_pesquisa) ? (int) $termo_pesquisa : 0;

    $count_query .= " AND (p.TITULO LIKE ? OR u.NOME LIKE ? OR e.ID = ? OR p.CODIGO = ?)";
    $sql .= " AND (p.TITULO LIKE ? OR u.NOME LIKE ? OR e.ID = ? OR p.CODIGO = ?)";
}

$sql .= " ORDER BY e.EMPDATA DESC";
$sql .= " LIMIT ? OFFSET ?";

// Preparar e executar query de contagem
$stmt_count = $conn->prepare($count_query);
if (!empty($termo_pesquisa)) {
    $stmt_count->bind_param('ssii', $termo_like, $termo_like, $id_pesquisa, $id_pesquisa);
}
$stmt_count->execute();
$count_result = $stmt_count->get_result();
$total_registros = $count_result->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $itens_por_pagina);

// Preparar e executar query principal
$stmt = $conn->prepare($sql);
if (!empty($termo_pesquisa)) {
    $stmt->bind_param('ssiiii', $termo_like, $termo_like, $id_pesquisa, $id_pesquisa, $itens_por_pagina, $offset);
} else {
    $stmt->bind_param('ii', $itens_por_pagina, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devolução</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../styles/sense.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6fa;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .stat-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.08);
            padding: 32px 0 18px 0;
            text-align: center;
            transition: box-shadow 0.3s, transform 0.3s;
            margin-bottom: 16px;
        }
        .stat-card:hover {
            box-shadow: 0 8px 32px rgba(0,0,0,0.16);
            transform: translateY(-4px) scale(1.03);
        }
        .stat-number {
            font-size: 2.8rem;
            font-weight: 700;
            color: #3498db;
            margin-bottom: 8px;
        }
        .stat-label {
            font-size: 1.1rem;
            color: #555;
            letter-spacing: 0.5px;
        }
        .dp-banner {
            background: linear-gradient(90deg, #283e51 0%, #485563 100%);
            border-radius: 0;
            box-shadow: 0 4px 18px rgba(52,152,219,0.08);
            padding: 32px 0 24px 0;
            margin-bottom: 32px;
        }
        .dp-banner h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #fff;
            text-shadow: 1px 1px 8px rgba(0,0,0,0.18);
        }
        .dp-banner .lead {
            color: #f5f5f5;
            font-size: 1.2rem;
            margin-bottom: 12px;
            text-shadow: 1px 1px 6px rgba(0,0,0,0.12);
        }
        .dp-banner .badge {
            background: #fff;
            color: #3498db;
            font-weight: 600;
            border-radius: 18px;
            box-shadow: 0 2px 8px rgba(52,152,219,0.10);
        }
        .btn-success, .btn-atualizar {
            background: linear-gradient(90deg, #283e51 0%, #485563 100%);
            border: none;
            font-weight: 600;
            color: #fff;
            box-shadow: 0 2px 8px rgba(40,62,81,0.12);
            transition: background 0.3s;
        }
        .btn-success:hover, .btn-atualizar:hover {
            background: linear-gradient(90deg, #485563 0%, #283e51 100%);
            color: #fff;
        }
        .multa-info {
            font-size: 0.9rem;
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container deep">
        <div class="container">
            <!-- Formulário de Pesquisa -->
            <form method="GET" class="mb-4">
                <div class="input-group">
                    <input type="text" name="pesquisa" class="form-control"
                        placeholder="Pesquisar por livro, usuário ou ID..."
                        value="<?= htmlspecialchars($termo_pesquisa) ?>">
                    <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                    <?php if (!empty($termo_pesquisa)): ?>
                        <a href="devolucao.php" class="btn btn-outline-secondary">Limpar</a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if (!empty($termo_pesquisa)): ?>
                <div class="alert alert-info mb-3">
                    Resultados para: <strong><?= htmlspecialchars($termo_pesquisa) ?></strong>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="tabela">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Livro</th>
                            <th>Usuário</th>
                            <th>Data Empréstimo</th>
                            <th>Data Prevista</th>
                            <th>Data Devolução</th>
                            <th>Situação</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php
                                $data_emprestimo = date('d/m/Y', strtotime($row['EMPDATA']));
                                $data_prevista = date('d/m/Y', strtotime($row['EMPADEV']));
                                $data_devolucao = !empty($row['EMPDEVOLU'])
                                    ? date('d/m/Y', strtotime($row['EMPDEVOLU']))
                                    : 'Pendente';
                                $dias_atraso = $row['dias_atraso'];
                                $multa = $row['multa_devida'];
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['EMPCODLIV']) ?></td>
                                    <td><?= htmlspecialchars($row['TITULO']) ?></td>
                                    <td><?= htmlspecialchars($row['NOME']) ?></td>
                                    <td><?= $data_emprestimo ?></td>
                                    <td><?= $data_prevista ?></td>
                                    <td><?= $data_devolucao ?></td>
                                    <td>
                                        <?php if ($dias_atraso > 0): ?>
                                            <span class='badge bg-danger'><?= $dias_atraso ?> dia(s) de atraso</span><br>
                                            <span class='multa-info'>Multa: R$ <?= number_format($multa, 2, ',', '.') ?></span>
                                        <?php else: ?>
                                            <span class='badge bg-success'>No prazo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-success btn-devolver" data-bs-toggle="modal"
                                            data-bs-target="#confirmarDevolucaoModal"
                                            data-emprestimoid="<?= $row['emprestimo_id'] ?>"
                                            data-livro="<?= htmlspecialchars($row['TITULO']) ?>"
                                            data-usuario="<?= htmlspecialchars($row['NOME']) ?>"
                                            data-multa="<?= $dias_atraso > 0 ? number_format($multa, 2, ',', '.') : '0,00' ?>"
                                            data-diasatraso="<?= $dias_atraso ?>">
                                            ✔ Devolver
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan='7' class='text-center'>Nenhum empréstimo ativo</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_paginas > 1): ?>
                <div class="pagination-container mt-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            Página <?= $pagina ?> de <?= $total_paginas ?> |
                            Registros: <?= $total_registros ?>
                        </small>

                        <ul class="pagination pagination-sm mb-0">
                            <?php if ($pagina > 1): ?>
                                <li class="page-item">
                                    <a class="page-link"
                                        href="?pagina=1<?= !empty($termo_pesquisa) ? '&pesquisa=' . urlencode($termo_pesquisa) : '' ?>">
                                        &laquo;
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link"
                                        href="?pagina=<?= $pagina - 1 ?><?= !empty($termo_pesquisa) ? '&pesquisa=' . urlencode($termo_pesquisa) : '' ?>">
                                        ‹
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $pagina - 2);
                            $end = min($total_paginas, $pagina + 2);

                            for ($i = $start; $i <= $end; $i++): ?>
                                <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                                    <a class="page-link"
                                        href="?pagina=<?= $i ?><?= !empty($termo_pesquisa) ? '&pesquisa=' . urlencode($termo_pesquisa) : '' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($pagina < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link"
                                        href="?pagina=<?= $pagina + 1 ?><?= !empty($termo_pesquisa) ? '&pesquisa=' . urlencode($termo_pesquisa) : '' ?>">
                                        ›
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link"
                                        href="?pagina=<?= $total_paginas ?><?= !empty($termo_pesquisa) ? '&pesquisa=' . urlencode($termo_pesquisa) : '' ?>">
                                        &raquo;
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de Confirmação de Devolução -->
    <div class="modal fade" id="confirmarDevolucaoModal" tabindex="-1" aria-labelledby="confirmarDevolucaoModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="confirmarDevolucaoModalLabel">Confirmar Devolução</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Deseja registrar a devolução do livro?</p>
                    <p><strong>Livro:</strong> <span id="modalLivroTitulo"></span></p>
                    <p><strong>Usuário:</strong> <span id="modalUsuarioNome"></span></p>
                    <p id="modalMultaInfo" style="display: none;">
                        <strong>Multa por atraso:</strong> R$ <span id="modalMultaValor"></span>
                        (<span id="modalDiasAtraso"></span> dias)
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" action="devolucao.php?pagina=<?= $pagina ?><?= !empty($termo_pesquisa) ? '&pesquisa=' . urlencode($termo_pesquisa) : '' ?>">
                        <input type="hidden" name="emprestimo_id" id="modalEmprestimoId">
                        <button type="submit" name="devolver" class="btn btn-success">Confirmar Devolução</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalDevolucao = new bootstrap.Modal(document.getElementById('confirmarDevolucaoModal'));
            const botoesDevolver = document.querySelectorAll('.btn-devolver');

            botoesDevolver.forEach(botao => {
                botao.addEventListener('click', function () {
                    const livroTitulo = this.getAttribute('data-livro');
                    const usuarioNome = this.getAttribute('data-usuario');
                    const emprestimoId = this.getAttribute('data-emprestimoid');
                    const diasAtraso = this.getAttribute('data-diasatraso');
                    const multa = this.getAttribute('data-multa');

                    // Preencher o modal
                    document.getElementById('modalLivroTitulo').textContent = livroTitulo;
                    document.getElementById('modalUsuarioNome').textContent = usuarioNome;
                    document.getElementById('modalEmprestimoId').value = emprestimoId;

                    // Se houver multa, mostrar a linha de multa
                    const multaInfo = document.getElementById('modalMultaInfo');
                    if (diasAtraso > 0) {
                        document.getElementById('modalMultaValor').textContent = multa;
                        document.getElementById('modalDiasAtraso').textContent = diasAtraso;
                        multaInfo.style.display = 'block';
                    } else {
                        multaInfo.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>

</html>
<?php ob_end_flush(); ?>