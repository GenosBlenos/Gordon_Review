<?php
ob_start();
session_start();
require __DIR__ . '/../app/funcoes.php';
include __DIR__ . '/../app/conexao.php';
include __DIR__ . '/../app/menu.php';
?>
<div class="dp-banner">
    <div class="container text-center">
        <h1><i class="fas fa-exchange-alt me-3"></i>Gestão de Empréstimos</h1>
        <p class="lead">Controle e acompanhe os empréstimos de livros realizados no sistema</p>
        <div class="mt-4">
            <span class="badge bg-light text-dark me-2"><i class="fas fa-book-reader me-1"></i> Empréstimos Ativos</span>
            <span class="badge bg-light text-dark me-2"><i class="fas fa-history me-1"></i> Histórico</span>
        </div>
    </div>
</div>

<?php
// Redireciona usuários não administradores para home.php
if (!isset($_SESSION['logado']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: home.php");
    exit();
}

// Variáveis de paginação dos empréstimos
$itens_por_pagina = 5;

// Conta total de empréstimos ativos
if (isset($_SESSION['role']) && $_SESSION['role'] === 'usuario' && isset($_GET['meus'])) {
    $usuario_id = $_SESSION['usuario_id'] ?? null;
    if ($usuario_id) {
        // Conta todos os empréstimos ativos, mesmo sem livro correspondente
        $count_emp_query = "SELECT COUNT(*) as total 
            FROM emprest e 
            LEFT JOIN pm_acerv p ON e.EMPCODLIV = p.CODIGO 
            WHERE e.EMPPAGO = 0 
            AND e.EMPDEVOLU IS NULL 
            AND e.EMPUSUA = ?";
        $stmt_count_emp = $conn->prepare($count_emp_query);
        $stmt_count_emp->bind_param('i', $usuario_id);
        $stmt_count_emp->execute();
        $result_count_emp = $stmt_count_emp->get_result();
        $total_emp = $result_count_emp->fetch_assoc()['total'];
        $stmt_count_emp->close();
    } else {
        $total_emp = 0;
    }
} else {
    // Admin vê todos os empréstimos ativos, mesmo sem livro correspondente
    $count_emp_query = "SELECT COUNT(*) as total 
        FROM emprest e 
        LEFT JOIN pm_acerv p ON e.EMPCODLIV = p.CODIGO 
        WHERE e.EMPPAGO = 0 
        AND e.EMPDEVOLU IS NULL";
    $result_count_emp = $conn->query($count_emp_query);
    $total_emp = $result_count_emp->fetch_assoc()['total'];
}

// Cálculo correto do total de páginas e página atual
$total_paginas_emp = $total_emp > 0 ? ceil($total_emp / $itens_por_pagina) : 1;
$pagina_emp = isset($_GET['pagina_emp']) ? (int) $_GET['pagina_emp'] : 1;

// Garantir que a página atual seja válida
if ($pagina_emp < 1) {
    $pagina_emp = 1;
} elseif ($total_emp > 0 && $pagina_emp > $total_paginas_emp) {
    $pagina_emp = $total_paginas_emp;
}

// Calcular o offset
$offset_emp = ($pagina_emp - 1) * $itens_por_pagina;

if (isset($_POST['emprestar'])) {
    $livro_id = (int) $_POST['livro'];
    $usuario_id = (int) $_POST['usuario'];

    // Verificar se o livro está disponível
    $check_livro = $conn->prepare("SELECT EMPRESTIMO FROM pm_acerv WHERE CODIGO = ?");
    $check_livro->bind_param("i", $livro_id);
    $check_livro->execute();
    $resultado = $check_livro->get_result()->fetch_assoc();

    if (!$resultado) {
        $_SESSION['error'] = "Livro não encontrado ou já emprestado.";
        header("Location: emprestimo.php");
        exit();
    }

    if ($resultado['EMPRESTIMO'] == 'S') {
        $_SESSION['error'] = "Livro não encontrado ou já emprestado.";
        header("Location: emprestimo.php");
        exit();
    }

    if ($livro_id <= 0 || $usuario_id <= 0) {
        $_SESSION['error'] = "Por favor, selecione um usuário e um livro válidos para realizar o empréstimo.";
        header("Location: emprestimo.php");
        exit();
    }

    $conn->begin_transaction();

    try {
        // Marcar livro como emprestado
        $stmt = $conn->prepare("UPDATE pm_acerv SET EMPRESTIMO = 'S' WHERE CODIGO = ?");
        $stmt->bind_param("i", $livro_id);
        $stmt->execute();

        // Calcular data de devolução (7 dias)
        $data_devolucao = date('Y-m-d', strtotime('+7 days'));

        // Inserir empréstimo na tabela emprest
        $stmt = $conn->prepare("
            INSERT INTO emprest 
            (EMPCODLIV, EMPUSUA, EMPDATA, EMPADEV, EMPDEVOLU, EMPMULTA, EMPATRASO, EMPPAGO) 
            VALUES (?, ?, CURDATE(), ?, NULL, 0, 0, 0)
        ");
        $stmt->bind_param("iis", $livro_id, $usuario_id, $data_devolucao);
        $stmt->execute();

        $conn->commit();
        $_SESSION['success'] = "Empréstimo realizado com sucesso!";
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log("Erro SQL: " . $e->getMessage());
        $_SESSION['error'] = "Ocorreu um problema ao acessar o banco de dados. Tente novamente ou contate o suporte.";
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Erro geral: " . $e->getMessage());
        $_SESSION['error'] = "Ocorreu um erro inesperado. Tente novamente.";
    }

    header("Location: emprestimo.php");
    exit();
}

// Buscar empréstimos ativos
if (isset($_SESSION['role']) && $_SESSION['role'] === 'usuario' && isset($_GET['meus'])) {
    // Só mostra empréstimos do usuário logado
    $usuario_id = $_SESSION['usuario_id'] ?? null;
    if ($usuario_id) {
        $emprestimos_query = "SELECT p.TITULO, u.NOME, e.EMPDATA, e.EMPADEV, e.EMPCODLIV AS CODIGO,
            CASE 
                WHEN DATE_ADD(e.EMPDATA, INTERVAL 7 DAY) < CURDATE() 
                THEN DATEDIFF(CURDATE(), DATE_ADD(e.EMPDATA, INTERVAL 7 DAY)) 
                ELSE 0 
            END AS dias_atraso
            FROM emprest e 
            LEFT JOIN pm_acerv p ON e.EMPCODLIV = p.CODIGO 
            LEFT JOIN pm_usua u ON e.EMPUSUA = u.CODIGO 
            WHERE e.EMPPAGO = 0 AND e.EMPUSUA = ? AND e.EMPDEVOLU IS NULL
            ORDER BY e.EMPDATA DESC
            LIMIT $itens_por_pagina OFFSET $offset_emp";
        $stmt = $conn->prepare($emprestimos_query);
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $emprestimos = $stmt->get_result();
    } else {
        $emprestimos = false;
    }
} else {
    // Admin vê todos
    $emprestimos_query = "SELECT p.TITULO, u.NOME, e.EMPDATA, e.EMPADEV, e.EMPCODLIV AS CODIGO,
        CASE 
            WHEN DATE_ADD(e.EMPDATA, INTERVAL 7 DAY) < CURDATE() 
            THEN DATEDIFF(CURDATE(), DATE_ADD(e.EMPDATA, INTERVAL 7 DAY)) 
            ELSE 0 
        END AS dias_atraso
        FROM emprest e 
        LEFT JOIN pm_acerv p ON e.EMPCODLIV = p.CODIGO 
        LEFT JOIN pm_usua u ON e.EMPUSUA = u.CODIGO 
        WHERE e.EMPPAGO = 0 AND e.EMPDEVOLU IS NULL
        ORDER BY e.EMPDATA DESC
        LIMIT $itens_por_pagina OFFSET $offset_emp";
    $emprestimos = $conn->query($emprestimos_query);
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empréstimo</title>
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
    </style>
</head>
<body>
    <div class="container deep">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php
                $msg = $_SESSION['error'];
                unset($_SESSION['error']);
                // Mensagens amigáveis
                if (strpos($msg, 'ID de livro ou usuário inválido') !== false) {
                    echo 'Por favor, selecione um usuário e um livro válidos para realizar o empréstimo.';
                } elseif (strpos($msg, 'Este livro já está emprestado') !== false) {
                    echo 'O livro selecionado já está emprestado. Escolha outro livro disponível.';
                } elseif (strpos($msg, 'Erro no banco de dados') !== false) {
                    echo 'Ocorreu um problema ao acessar o banco de dados. Tente novamente ou contate o suporte.';
                } elseif (strpos($msg, 'Ocorreu um erro inesperado') !== false) {
                    echo 'Ocorreu um erro inesperado. Tente novamente.';
                } else {
                    echo htmlspecialchars($msg);
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Usuário:</label>
                    <input type="hidden" name="usuario" id="usuario_id">
                    <input type="text" class="form-control" id="busca_usuario" placeholder="Digite o nome do usuário" autocomplete="off">
                    <div id="resultado_usuario" class="list-group resultado-busca"></div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Livro Disponível:</label>
                    <input type="hidden" name="livro" id="livro_id">
                    <input type="text" class="form-control" id="busca_livro" placeholder="Digite título ou autor" autocomplete="off">
                    <div id="resultado_livro" class="list-group resultado-busca"></div>
                </div>

                <div class="col-12">
                    <button type="submit" name="emprestar" class="btn btn-primary w-100">
                        ✨ Realizar Empréstimo
                    </button>
                </div>
            </div>
        </form>

        <div class="mt-5">
            <h4>🔍 Empréstimos Ativos</h4>
            <div class="table-responsive">
                <table class="tabela">
                    <thead class="table-light">
                        <tr>
                            <th>Livro</th>
                            <th>Usuário</th>
                            <th>Data Empréstimo</th>
                            <th>Data Devolução</th>
                            <th>Situação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($emprestimos && $emprestimos->num_rows > 0): ?>
                            <?php while ($emp = $emprestimos->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php
                                        $titulo = $emp['TITULO'] ?? '';
                                        $codigo = $emp['CODIGO'] ?? '';
                                        if ($codigo !== '') {
                                            if ($titulo !== '' && $titulo !== null) {
                                                echo htmlspecialchars($titulo . ' - ' . $codigo);
                                            } else {
                                                echo htmlspecialchars($codigo);
                                            }
                                        } else {
                                            echo '<span class="text-danger">(Sem código)</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($emp['NOME'] ?? '') ?></td>
                                    <td><?= date('d/m/Y', strtotime($emp['EMPDATA'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($emp['EMPADEV'])) ?></td>
                                    <td>
                                        <?php if ($emp['dias_atraso'] > 0): ?>
                                            <span class="badge bg-danger">
                                                <?= $emp['dias_atraso'] ?> dia(s) de atraso
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Em dia</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Nenhum empréstimo ativo</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>            <?php if ($total_emp > 0): ?>
            <div class="mt-2 d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Página <?= $pagina_emp ?> de <?= $total_paginas_emp ?>
                    | Registros: <?= $total_emp ?>
                </small>
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($pagina_emp > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?pagina_emp=<?= $pagina_emp - 1 ?><?= isset($_GET['meus']) ? '&meus=1' : '' ?>">&laquo;</a>
                        </li>
                    <?php endif; ?>

                    <?php
                    $inicio = max(1, $pagina_emp - 1);
                    $fim = min($total_paginas_emp, $pagina_emp + 1);

                    for ($i = $inicio; $i <= $fim; $i++): ?>
                        <li class="page-item <?= $i == $pagina_emp ? 'active' : '' ?>">
                            <a class="page-link" href="?pagina_emp=<?= $i ?><?= isset($_GET['meus']) ? '&meus=1' : '' ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($pagina_emp < $total_paginas_emp): ?>
                        <li class="page-item">
                            <a class="page-link" href="?pagina_emp=<?= $pagina_emp + 1 ?><?= isset($_GET['meus']) ? '&meus=1' : '' ?>">&raquo;</a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled">
                            <span class="page-link">&raquo;</span>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Busca de usuário
        const buscaUsuario = document.getElementById('busca_usuario');
        const resultadoUsuario = document.getElementById('resultado_usuario');
        const usuarioId = document.getElementById('usuario_id');

        buscaUsuario.addEventListener('input', function () {
            const termo = this.value.trim();
            if (termo.length > 2) {
                fetch(`../api/pesquisar-usuario.php?termo=${encodeURIComponent(termo)}`)
                    .then(response => response.text())
                    .then(data => {
                        resultadoUsuario.innerHTML = data;
                        resultadoUsuario.style.display = 'block';
                    });
            } else {
                resultadoUsuario.style.display = 'none';
            }
        });

        // Evento de clique em um item de usuário
        resultadoUsuario.addEventListener('click', function (e) {
            if (e.target.classList.contains('usuario-item')) {
                usuarioId.value = e.target.getAttribute('data-id');
                buscaUsuario.value = e.target.getAttribute('data-nome');
                resultadoUsuario.style.display = 'none';
            }
        });

        // Busca de livro (mesma lógica para livros)
        const buscaLivro = document.getElementById('busca_livro');
        const resultadoLivro = document.getElementById('resultado_livro');
        const livroId = document.getElementById('livro_id');

        buscaLivro.addEventListener('input', function () {
            const termo = this.value.trim();
            if (termo.length > 2) {
                fetch(`../api/pesquisar-livro.php?termo=${encodeURIComponent(termo)}`)
                    .then(response => response.text())
                    .then(function(data) {
                        resultadoLivro.innerHTML = data;
                        resultadoLivro.style.display = 'block';
                    });
            } else {
                resultadoLivro.style.display = 'none';
            }
        });

        resultadoLivro.addEventListener('click', function (e) {
            if (e.target.classList.contains('livro-item')) {
                livroId.value = e.target.getAttribute('data-id');
                buscaLivro.value = e.target.getAttribute('data-titulo');
                resultadoLivro.style.display = 'none';
            }
        });

        // Fechar resultados ao clicar fora
        document.addEventListener('click', function (e) {
            if (!buscaUsuario.contains(e.target)) {
                resultadoUsuario.style.display = 'none';
            }
            if (!buscaLivro.contains(e.target)) {
                resultadoLivro.style.display = 'none';
            }
        });
    });
</script>

</html>
<?php ob_end_flush(); ?>