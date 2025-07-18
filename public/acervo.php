<?php
session_start();
require __DIR__ . '/../app/funcoes.php';

// Padronização: garantir inclusão do menu e estrutura HTML
if (!isset($_SESSION['logado'])) {
    header("Location: login.php");
    exit();
}
require __DIR__ . '/../app/conexao.php';
include __DIR__ . '/../app/menu.php';
?>
<div class="dp-banner">
    <div class="container text-center">
        <h1><i class="fas fa-book me-3"></i>Acervo Completo</h1>
        <p class="lead">Consulte e gerencie todos os livros cadastrados no sistema</p>
        <div class="mt-4">
            <span class="badge bg-light text-dark me-2"><i class="fas fa-search me-1"></i> Pesquisa Avançada</span>
            <span class="badge bg-light text-dark me-2"><i class="fas fa-database me-1"></i> Gerenciamento</span>
        </div>
    </div>
</div>
<?php
// Verifica se é admin
$isAdmin = isAdmin();

// Modo de edição
$editando = false;
$livro_editando = null;

// Processar exclusão de livro
if ($isAdmin && isset($_POST['excluir'])) {
    $codigo_excluir = (int) $_POST['codigo_excluir'];

    try {
        // Verificar se o livro está emprestado
        $stmt_verifica = $conn->prepare("SELECT EMPRESTIMO FROM pm_acerv WHERE CODIGO = ?");
        $stmt_verifica->bind_param('i', $codigo_excluir);
        $stmt_verifica->execute();
        $result_verifica = $stmt_verifica->get_result();

        if ($result_verifica->num_rows > 0) {
            $livro = $result_verifica->fetch_assoc();
            if ($livro['EMPRESTIMO']) {
                $erro_exclusao = "Não é possível excluir um livro que está emprestado.";
            } else {
                // Excluir o livro
                $stmt_excluir = $conn->prepare("DELETE FROM pm_acerv WHERE CODIGO = ?");
                $stmt_excluir->bind_param('i', $codigo_excluir);
                if ($stmt_excluir->execute()) {
                    $sucesso_exclusao = "Livro excluído com sucesso!";
                } else {
                    $erro_exclusao = "Erro ao excluir: " . $stmt_excluir->error;
                }
            }
        } else {
            $erro_exclusao = "Livro não encontrado.";
        }
    } catch (Exception $e) {
        $erro_exclusao = "Erro: " . $e->getMessage();
    }
}

// Se estiver editando e for admin
if ($isAdmin && isset($_GET['editar'])) {
    $codigo_editar = (int) $_GET['editar'];
    $sql_editar = "SELECT * FROM pm_acerv WHERE CODIGO = ?";
    $stmt_editar = $conn->prepare($sql_editar);
    $stmt_editar->bind_param('i', $codigo_editar);
    $stmt_editar->execute();
    $result_editar = $stmt_editar->get_result();

    if ($result_editar->num_rows > 0) {
        $livro_editando = $result_editar->fetch_assoc();
        $editando = true;
    }
}

// Processar atualização de livro existente
if ($isAdmin && isset($_POST['atualizar'])) {
    $codigo_original = (int) $_POST['codigo_original'];
    $codigo = (int) $_POST['codigo'];
    $titulo = trim($_POST['titulo']);
    $autor = trim($_POST['autor']);
    $ano = (int) $_POST['ano'];
    $isbn = trim($_POST['isbn']);
    $tema = trim($_POST['tema']);

    // Validação dos campos obrigatórios
    if (empty($titulo) || empty($autor) || empty($ano) || empty($tema)) {
        $erro = "Preencha todos os campos obrigatórios (Título, Autor, Ano, Tema).";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE pm_acerv SET 
                CODIGO = ?,
                TITULO = ?,
                AUTOR = ?,
                ANO = ?,
                ISBN = ?,
                TEMA = ?
                WHERE CODIGO = ?
            ");

            $stmt->bind_param(
                "ississi",
                $codigo,
                $titulo,
                $autor,
                $ano,
                $isbn,
                $tema,
                $codigo_original
            );

            if ($stmt->execute()) {
                $sucesso = "Livro atualizado com sucesso!";
                $editando = false;
            } else {
                $erro = "Erro ao atualizar: " . $stmt->error;
            }
        } catch (Exception $e) {
            $erro = "Erro: " . $e->getMessage();
        }
    }
}

// Configuração de paginação
$registros_por_pagina = 25;
$pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$offset = ($pagina - 1) * $registros_por_pagina;

$termo_pesquisa = $_GET['pesquisa'] ?? '';
$where = '';
$params = [];
$tipos = '';

// Se for usuário comum, só mostra livros disponíveis
if (!$isAdmin || isset($_GET['disponivel'])) {
    $where = "WHERE (EMPRESTIMO = '' OR EMPRESTIMO IS NULL)";
}

if (!empty($termo_pesquisa)) {
    $termo_like = "%{$termo_pesquisa}%";
    $where .= ($where ? ' AND ' : 'WHERE ') . "(TITULO LIKE ? OR AUTOR LIKE ? OR ISBN LIKE ? OR TEMA LIKE ?)";
    $params = array_fill(0, 4, $termo_like);
    $tipos = 'ssss';
}

// Filtro de disponibilidade para usuários comuns
$filtro_disponibilidade = $_GET['filtro_disponibilidade'] ?? '';
if (!$isAdmin) {
    $disponibilidade_where = '';
    if ($filtro_disponibilidade === 'disponivel') {
        $disponibilidade_where = "(EMPRESTIMO = '' OR EMPRESTIMO IS NULL)";
    } elseif ($filtro_disponibilidade === 'emprestado') {
        $disponibilidade_where = "(EMPRESTIMO IS NOT NULL AND EMPRESTIMO != '')";
    }
    if ($disponibilidade_where) {
        if ($where) {
            $where = str_replace('WHERE', 'WHERE ' . $disponibilidade_where . ' AND', $where);
        } else {
            $where = 'WHERE ' . $disponibilidade_where;
        }
    }
}

// Query para contar total de registros
$sql_count = "SELECT COUNT(*) AS total FROM pm_acerv $where";
// Conta quantos parâmetros existem no SQL
$param_count = substr_count($sql_count, '?');
if (!empty($params) && !empty($tipos) && $param_count === count($params)) {
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param($tipos, ...$params);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $total_registros = $result_count->fetch_assoc()['total'];
    $stmt_count->close();
} else {
    $result_count = $conn->query($sql_count);
    $total_registros = $result_count->fetch_assoc()['total'];
}

$total_paginas = ceil($total_registros / $registros_por_pagina);

// Query principal com paginação
$sql = "SELECT CODIGO, TITULO, AUTOR, ANO, ISBN, EMPRESTIMO 
        FROM pm_acerv $where 
        ORDER BY TITULO
        LIMIT ? OFFSET ?";

// Sempre faz bind dos dois ints da paginação
if (!empty($params) && !empty($tipos) && strpos($tipos, 's') !== false) {
    $params_bind = $params;
    $tipos_bind = $tipos;
    $params_bind[] = $registros_por_pagina;
    $params_bind[] = $offset;
    $tipos_bind .= 'ii';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($tipos_bind, ...$params_bind);
    $stmt->execute();
    $livros = $stmt->get_result();
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $registros_por_pagina, $offset);
    $stmt->execute();
    $livros = $stmt->get_result();
}

// Processa pedido de aviso de devolução (usuário comum)
if (!$isAdmin && isset($_POST['livro_aviso'])) {
    $livro_aviso = (int)$_POST['livro_aviso'];
    $usuario_id = $_SESSION['usuario_id'] ?? null;
    if ($usuario_id) {
        // Buscar e-mail e nome do usuário
        $stmt_user = $conn->prepare("SELECT NOME, EMAIL FROM pm_usuario WHERE ID = ? LIMIT 1");
        $stmt_user->bind_param('i', $usuario_id);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();
        $user = $result_user->fetch_assoc();
        $stmt_user->close();

        // Buscar título do livro
        $stmt_livro = $conn->prepare("SELECT TITULO FROM pm_acerv WHERE CODIGO = ? LIMIT 1");
        $stmt_livro->bind_param('i', $livro_aviso);
        $stmt_livro->execute();
        $result_livro = $stmt_livro->get_result();
        $livro_info = $result_livro->fetch_assoc();
        $stmt_livro->close();

        // Registrar interesse na tabela de avisos
        $conn->query("CREATE TABLE IF NOT EXISTS pm_aviso_devolucao (id INT AUTO_INCREMENT PRIMARY KEY, usuario_id INT, livro_codigo INT, data_pedido DATETIME, avisado TINYINT DEFAULT 0)");
        $conn->query("INSERT INTO pm_aviso_devolucao (usuario_id, livro_codigo, data_pedido, avisado) VALUES ($usuario_id, $livro_aviso, NOW(), 0)");

        $sucesso = "Seu interesse foi registrado. Você receberá um e-mail quando o livro estiver disponível para empréstimo.";
    }
}

// Ao registrar devolução (admin), notificar interessados
if ($isAdmin && isset($_POST['devolver'])) {
    $codigo_devolver = (int)$_POST['codigo_devolver'];
    // Atualiza status do livro para disponível
    $conn->query("UPDATE pm_acerv SET EMPRESTIMO = 'N' WHERE CODIGO = $codigo_devolver");
    // Busca interessados
    $result_avisos = $conn->query("SELECT a.id, u.NOME, u.EMAIL, l.TITULO FROM pm_aviso_devolucao a JOIN pm_usuario u ON a.usuario_id = u.ID JOIN pm_acerv l ON a.livro_codigo = l.CODIGO WHERE a.livro_codigo = $codigo_devolver AND a.avisado = 0");
    while ($aviso = $result_avisos->fetch_assoc()) {
        if (filter_var($aviso['EMAIL'], FILTER_VALIDATE_EMAIL)) {
            $to = $aviso['EMAIL'];
            $subject = 'Aviso de disponibilidade de livro - Biblioteca GORDON';
            $message = "Olá, {$aviso['NOME']}!\n\nO livro '{$aviso['TITULO']}' está disponível para empréstimo na biblioteca.\nAcesse o sistema para realizar o empréstimo.\n\nAtenciosamente,\nEquipe GORDON";
            $headers = "From: biblioteca@gordon.com\r\nReply-To: biblioteca@gordon.com\r\nContent-Type: text/plain; charset=UTF-8";
            mail($to, $subject, $message, $headers);
        }
        // Marca como avisado
        $conn->query("UPDATE pm_aviso_devolucao SET avisado = 1 WHERE id = {$aviso['id']}");
    }
    $sucesso = "Livro devolvido e usuários interessados foram avisados por e-mail.";
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acervo Completo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../styles/sense.css" rel="stylesheet">
    <style>
        @media (max-width: 767.98px) {
            .tabela th.tombo-col,
            .tabela td.tombo-col {
                display: none;
            }

            .tabela td {
                vertical-align: top;
            }

            .mobile-livro-info {
                display: block;
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>

    <div class="container deep">
        <!-- Padronização de mensagens -->
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <?php if (isset($sucesso)): ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <?php if ($isAdmin && $editando): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">✏️ Editando Livro: <?= htmlspecialchars($livro_editando['TITULO']) ?></h4>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="codigo_original" value="<?= $livro_editando['CODIGO'] ?>">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label">Tombo</label>
                                <input type="number" name="codigo" class="form-control" required min="1" step="1"
                                    value="<?= $livro_editando['CODIGO'] ?>" readonly>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label">Título *</label>
                                <input type="text" name="titulo" class="form-control" required maxlength="255"
                                    value="<?= htmlspecialchars($livro_editando['TITULO']) ?>">
                            </div>

                            <div class="col-md-5">
                                <label class="form-label">Autor(a) *</label>
                                <input type="text" name="autor" class="form-control" required maxlength="100"
                                    value="<?= htmlspecialchars($livro_editando['AUTOR']) ?>">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Ano de Publicação *</label>
                                <input type="number" name="ano" class="form-control" min="1900" max="<?= date('Y') ?>"
                                    required value="<?= $livro_editando['ANO'] ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">ISBN</label>
                                <input type="text" name="isbn" class="form-control" maxlength="20"
                                    placeholder="Ex: 978-85-xxxxx-xx-x"
                                    value="<?= htmlspecialchars($livro_editando['ISBN']) ?>">
                            </div>

                            <div class="col-md-5">
                                <label class="form-label">Tema *</label>
                                <input type="text" name="tema" class="form-control" required maxlength="20"
                                    placeholder="Ex: Romance, Ficção"
                                    value="<?= htmlspecialchars($livro_editando['TEMA']) ?>">
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" name="atualizar" class="btn btn-primary">
                                📘 Atualizar Livro
                            </button>
                            <a href="acervo.php?pagina=<?= $pagina ?>&pesquisa=<?= urlencode($termo_pesquisa) ?>"
                                class="btn btn-secondary">
                                Cancelar Edição
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <form method="GET" class="mb-4">
            <div class="input-group mb-2">
                <input style="border: solid 2px black;" type="text" name="pesquisa" class="form-control"
                    placeholder="Pesquisar por título, autor, ISBN ou tema..." value="<?= htmlspecialchars($termo_pesquisa) ?>">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
            </div>
            <?php if (!$isAdmin): ?>
            <div class="mb-2">
                <label class="me-2">Filtrar:</label>
                <select name="filtro_disponibilidade" class="form-select d-inline w-auto" onchange="this.form.submit()">
                    <option value="" <?= $filtro_disponibilidade === '' ? 'selected' : '' ?>>Todos</option>
                    <option value="disponivel" <?= $filtro_disponibilidade === 'disponivel' ? 'selected' : '' ?>>Apenas disponíveis</option>
                    <option value="emprestado" <?= $filtro_disponibilidade === 'emprestado' ? 'selected' : '' ?>>Apenas emprestados</option>
                </select>
            </div>
            <?php endif; ?>
        </form>

        <?php if (!empty($termo_pesquisa)): ?>
            <div class="alert alert-info mb-3">
                Resultados para: <strong>"<?= $termo_pesquisa ?>"</strong>
                <a href="acervo.php" class="float-end">Limpar pesquisa</a>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Status</th>
                        <?php if ($isAdmin): ?>
                            <th class="tombo-col">Tombo</th>
                        <?php endif; ?>
                        <th>Título</th>
                        <th>Autor(a)</th>
                        <th>Ano</th>
                        <th>ISBN</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($livro = $livros->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <span class="badge <?= $livro['EMPRESTIMO'] ? 'bg-danger' : 'bg-success' ?>">
                                    <?= $livro['EMPRESTIMO'] ? 'Emprestado' : 'Disponível' ?>
                                </span>
                            </td>
                            <?php if ($isAdmin): ?>
                                <td class="tombo-col"><?= htmlspecialchars($livro['CODIGO']) ?></td>
                            <?php endif; ?>
                            <td>
                                <span class="mobile-livro-info"><strong><?= htmlspecialchars($livro['TITULO']) ?></strong></span>
                                <span class="mobile-livro-info">Autor(a): <?= htmlspecialchars($livro['AUTOR']) ?></span>
                                <span class="mobile-livro-info">Ano: <?= $livro['ANO'] ?></span>
                                <span class="mobile-livro-info">ISBN: <?= $livro['ISBN'] ?? 'ISBN não cadastrado' ?></span>
                            </td>
                            <td class="d-none d-md-table-cell"> <?= htmlspecialchars($livro['AUTOR']) ?> </td>
                            <td class="d-none d-md-table-cell"> <?= $livro['ANO'] ?> </td>
                            <td class="d-none d-md-table-cell"> <?= $livro['ISBN'] ?? 'ISBN não cadastrado' ?> </td>
                            <td>
                                <?php if ($isAdmin): ?>
                                    <a href="acervo.php?editar=<?= $livro['CODIGO'] ?>&pagina=<?= $pagina ?>&pesquisa=<?= urlencode($termo_pesquisa) ?>" class="btn btn-sm btn-warning">Editar</a>
                                    <button class="btn btn-sm btn-danger btn-excluir" data-codigo="<?= $livro['CODIGO'] ?>" data-titulo="<?= htmlspecialchars($livro['TITULO']) ?>">Excluir</button>
                                <?php else: ?>
                                    <?php if (!$livro['EMPRESTIMO']): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="solicitar_livro" value="<?= $livro['CODIGO'] ?>">
                                            <button type="submit" class="btn btn-sm btn-success">Solicitar</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="livro_aviso" value="<?= $livro['CODIGO'] ?>">
                                            <button type="submit" class="btn btn-sm btn-warning btn-aviso-disponivel">Avisar quando disponível</button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal de Confirmação de Exclusão -->
        <div class="modal fade" id="confirmarExclusaoModal" tabindex="-1" aria-labelledby="confirmarExclusaoModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="confirmarExclusaoModalLabel">Confirmar Exclusão</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Tem certeza que deseja excluir o livro <strong id="livroTituloExclusao"></strong>?</p>
                        <p class="text-danger"><strong>Esta ação não pode ser desfeita.</strong></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <form method="POST"
                            action="acervo.php?pagina=<?= $pagina ?>&pesquisa=<?= urlencode($termo_pesquisa) ?>">
                            <input type="hidden" name="codigo_excluir" id="codigoExcluir">
                            <button type="submit" name="excluir" class="btn btn-danger">Confirmar Exclusão</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Paginação -->
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="text-muted">
                Página <?= $pagina ?> de <?= $total_paginas ?> |
                Total: <?= number_format($total_registros) ?> registros
            </div>

            <ul class="pagination pagination-sm mb-0">
                <?php if ($pagina > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=1&pesquisa=<?= urlencode($termo_pesquisa) ?>">
                            &laquo;
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=<?= $pagina - 1 ?>&pesquisa=<?= urlencode($termo_pesquisa) ?>">
                            &lsaquo;
                        </a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled">
                        <span class="page-link">&laquo;</span>
                    </li>
                    <li class="page-item disabled">
                        <span class="page-link">&lsaquo;</span>
                    </li>
                <?php endif; ?>

                <li class="page-item active">
                    <span class="page-link"><?= $pagina ?></span>
                </li>

                <?php if ($pagina < $total_paginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=<?= $pagina + 1 ?>&pesquisa=<?= urlencode($termo_pesquisa) ?>">
                            &rsaquo;
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link"
                            href="?pagina=<?= $total_paginas ?>&pesquisa=<?= urlencode($termo_pesquisa) ?>">
                            &raquo;
                        </a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled">
                        <span class="page-link">&rsaquo;</span>
                    </li>
                    <li class="page-item disabled">
                        <span class="page-link">&raquo;</span>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script para o modal de confirmação de exclusão
        document.addEventListener('DOMContentLoaded', function () {
            const botoesExcluir = document.querySelectorAll('.btn-excluir');
            const modalExclusao = new bootstrap.Modal(document.getElementById('confirmarExclusaoModal'));
            const livroTituloExclusao = document.getElementById('livroTituloExclusao');
            const codigoExcluirInput = document.getElementById('codigoExcluir');

            botoesExcluir.forEach(botao => {
                botao.addEventListener('click', function () {
                    const codigo = this.getAttribute('data-codigo');
                    const titulo = this.getAttribute('data-titulo');

                    livroTituloExclusao.textContent = titulo;
                    codigoExcluirInput.value = codigo;

                    modalExclusao.show();
                });
            });
        });
    </script>
</body>
</html>