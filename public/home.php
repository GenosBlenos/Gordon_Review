<?php
session_start();
require __DIR__ . '/../app/funcoes.php';
// echo "<pre>";
// print_r($_SESSION);
// echo "</pre>";
// exit;

if (!isset($_SESSION['logado'])) {
    header("Location: login.php");
    exit();
}

require __DIR__ . '/../app/conexao.php';
include __DIR__ . '/../app/menu.php';

$total_livros = $conn->query("SELECT COUNT(*) FROM pm_acerv")->fetch_row()[0];
// Corrigido: conta apenas empréstimos não pagos e não devolvidos
$emprestimos_ativos = $conn->query("SELECT COUNT(*) FROM emprest WHERE EMPPAGO = FALSE AND EMPDEVOLU IS NULL")->fetch_row()[0];
$usuarios_cadastrados = $conn->query("SELECT COUNT(*) FROM pm_usua")->fetch_row()[0];

// CORREÇÃO 1: Nome correto da variável (sem espaço)
$dias_prazo = 7; // Prazo padrão de 7 dias

// CORREÇÃO 2: Sintaxe SQL correta
// Empréstimos atrasados: não pagos, não devolvidos e com data prevista já vencida
$query = "SELECT COUNT(*) 
          FROM emprest 
          WHERE EMPADEV < CURDATE() 
            AND EMPPAGO = FALSE 
            AND EMPDEVOLU IS NULL";

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$atrasos = $result->fetch_row()[0];
$stmt->close();

// Empréstimos ativos do usuário comum
$emprestimos_usuario = [];
if (isset($_SESSION['role']) && $_SESSION['role'] === 'usuario' && isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];
    $query_emprestimos = "SELECT p.TITULO, e.EMPDATA, e.EMPADEV, e.EMPCODLIV AS CODIGO,
        CASE 
            WHEN DATE_ADD(e.EMPDATA, INTERVAL 7 DAY) < CURDATE() 
            THEN DATEDIFF(CURDATE(), DATE_ADD(e.EMPDATA, INTERVAL 7 DAY)) 
            ELSE 0 
        END AS dias_atraso
        FROM emprest e 
        LEFT JOIN pm_acerv p ON e.EMPCODLIV = p.CODIGO 
        WHERE e.EMPPAGO = 0 AND e.EMPUSUA = ? AND e.EMPDEVOLU IS NULL
        ORDER BY e.EMPDATA DESC
        LIMIT 10";
    $stmt = $conn->prepare($query_emprestimos);
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $result_emprestimos = $stmt->get_result();
    while ($row = $result_emprestimos->fetch_assoc()) {
        $emprestimos_usuario[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    <div class="container-fluid mt-4">
        <div class="row g-4 mb-5">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                echo '<div class="col-12 col-sm-6 col-xl-3">
                <div class="card data-card shadow-sm border-primary">
                    <div class="card-body">
                        <h5 class="card-title text-primary">📚 Total de Livros</h5>
                        <div class="card-counter text-primary">' . number_format($total_livros) . '</div>
                    </div>
                </div>
            </div>';
            } ?>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                echo '<div class="col-12 col-sm-6 col-xl-3">
                <div class="card data-card shadow-sm border-success">
                    <div class="card-body">
                        <h5 class="card-title text-success">🔄 Empréstimos Ativos</h5>
                        <div class="card-counter text-success">' . number_format($emprestimos_ativos) . '</div>
                    </div>
                </div>
            </div>';
            } ?>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                echo '<div class="col-12 col-sm-6 col-xl-3">
                <div class="card data-card shadow-sm border-info">
                    <div class="card-body">
                        <h5 class="card-title text-info">👥 Usuários Cadastrados</h5>
                        <div class="card-counter text-info"> ' . number_format($usuarios_cadastrados) . '</div>
                    </div>
                </div>
            </div>';
            } ?>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                echo '<div class="col-12 col-sm-6 col-xl-3">
                <div class="card data-card shadow-sm border-danger">
                    <div class="card-body">
                        <h5 class="card-title text-danger">⏳ Empréstimos Atrasados</h5>
                        <div class="card-counter text-danger">' . number_format($atrasos) . '</div>
                    </div>
                </div>
            </div>';
            } ?>
        </div>
        <div class="row g-4">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <div class="col-12 col-lg-6">
                <div style="border: 1px solid gray; border-radius: 16px;" class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title">🔥 Livros Populares</h5>
                        <ul class="list-group">
                            <?php
                            $livros_populares = $conn->query("
                                SELECT p.TITULO, COUNT(e.EMPCODLIV) AS total
                                FROM emprest e
                                JOIN pm_acerv p ON e.EMPCODLIV = p.CODIGO
                                GROUP BY p.CODIGO, p.TITULO
                                ORDER BY total DESC
                                LIMIT 5
                            ");

                            if ($livros_populares->num_rows > 0) {
                                while ($livro = $livros_populares->fetch_assoc()) {
                                    echo '<li class="list-group-item d-flex justify-content-between align-items-center">'
                                        . htmlspecialchars($livro['TITULO'])
                                        . '<span class="badge bg-primary rounded-pill">' . $livro['total'] . '</span>'
                                        . '</li>';
                                }
                            } else {
                                echo "<li class='list-group-item'>Nenhum livro popular encontrado.</li>";
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div style="border: 1px solid gray; border-radius: 16px;" class="card-body">
                        <h5 style="margin-bottom: 60px;" class="card-title">📊 Distribuição por Tema</h5>
                        <div style="position: relative; height: 500px;">
                            <canvas id="temasChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'usuario'): ?>
        <div class="row g-4 mb-5">
            <div class="col-12 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <h5 class="card-title">📚 Meus Empréstimos Ativos</h5>
                        <?php if (count($emprestimos_usuario) > 0): ?>
                        <div class="table-responsive">
                            <table class="tabela">
                                <thead class="table-light">
                                    <tr>
                                        <th>Livro</th>
                                        <th>Data Empréstimo</th>
                                        <th>Data Devolução</th>
                                        <th>Situação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($emprestimos_usuario as $emp): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(($emp['TITULO'] ?? '') . (isset($emp['CODIGO']) ? ' - ' . $emp['CODIGO'] : '')) ?></td>
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
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info mb-0">Nenhum empréstimo ativo.</div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Histórico de Empréstimos -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <h5 class="card-title">📖 Histórico de Empréstimos</h5>
                        <?php
                        $historico = [];
                        $usuario_id = $_SESSION['usuario_id'];
                        $sql_hist = "SELECT p.TITULO, e.EMPDATA, e.EMPADEV, e.EMPDEVOLU, e.EMPPAGO
                            FROM emprest e
                            LEFT JOIN pm_acerv p ON e.EMPCODLIV = p.CODIGO
                            WHERE e.EMPUSUA = ?
                            ORDER BY e.EMPDATA DESC
                            LIMIT 10";
                        $stmt_hist = $conn->prepare($sql_hist);
                        $stmt_hist->bind_param('i', $usuario_id);
                        $stmt_hist->execute();
                        $res_hist = $stmt_hist->get_result();
                        while ($row = $res_hist->fetch_assoc()) {
                            $historico[] = $row;
                        }
                        $stmt_hist->close();
                        ?>
                        <?php if (count($historico) > 0): ?>
                        <div class="table-responsive">
                            <table class="tabela">
                                <thead class="table-light">
                                    <tr>
                                        <th>Livro</th>
                                        <th>Data Empréstimo</th>
                                        <th>Data Devolução Prevista</th>
                                        <th>Data Devolvida</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historico as $h): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($h['TITULO'] ?? '') ?></td>
                                        <td><?= date('d/m/Y', strtotime($h['EMPDATA'])) ?></td>
                                        <td><?= date('d/m/Y', strtotime($h['EMPADEV'])) ?></td>
                                        <td><?= $h['EMPDEVOLU'] ? date('d/m/Y', strtotime($h['EMPDEVOLU'])) : '<span class="text-warning">Pendente</span>' ?></td>
                                        <td>
                                            <?php if ($h['EMPDEVOLU']): ?>
                                                <span class="badge bg-success">Devolvido</span>
                                            <?php elseif (!$h['EMPDEVOLU'] && !$h['EMPPAGO']): ?>
                                                <span class="badge bg-warning text-dark">Em aberto</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Atrasado</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info mb-0">Nenhum histórico encontrado.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Sugestões de Livros -->
            <div class="col-12 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <h5 class="card-title">✨ Sugestões de Leitura</h5>
                        <?php
                        // Sugestões de Leitura Personalizadas
                        $sugestoes = [];
                        $usuario_id = $_SESSION['usuario_id'];
                        // 1. Buscar temas e autores mais frequentes dos empréstimos anteriores
                        $sql_freq = "SELECT p.TEMA, p.AUTOR, COUNT(*) as freq
                            FROM emprest e
                            JOIN pm_acerv p ON e.EMPCODLIV = p.CODIGO
                            WHERE e.EMPUSUA = ?
                            GROUP BY p.TEMA, p.AUTOR
                            ORDER BY freq DESC
                            LIMIT 3";
                        $stmt_freq = $conn->prepare($sql_freq);
                        $stmt_freq->bind_param('i', $usuario_id);
                        $stmt_freq->execute();
                        $res_freq = $stmt_freq->get_result();
                        $preferencias = [];
                        while ($row = $res_freq->fetch_assoc()) {
                            $preferencias[] = $row;
                        }
                        $stmt_freq->close();

                        // 2. Buscar livros disponíveis desses temas/autores, excluindo já lidos
                        $lidos = [];
                        $sql_lidos = "SELECT DISTINCT EMPCODLIV FROM emprest WHERE EMPUSUA = ?";
                        $stmt_lidos = $conn->prepare($sql_lidos);
                        $stmt_lidos->bind_param('i', $usuario_id);
                        $stmt_lidos->execute();
                        $res_lidos = $stmt_lidos->get_result();
                        while ($row = $res_lidos->fetch_assoc()) {
                            $lidos[] = $row['EMPCODLIV'];
                        }
                        $stmt_lidos->close();

                        $where_lidos = '';
                        if (count($lidos) > 0) {
                            $in = implode(',', array_map('intval', $lidos));
                            $where_lidos = "AND CODIGO NOT IN ($in)";
                        }

                        $sugestoes = [];
                        if (count($preferencias) > 0) {
                            foreach ($preferencias as $pref) {
                                $tema = $conn->real_escape_string($pref['TEMA']);
                                $autor = $conn->real_escape_string($pref['AUTOR']);
                                $sql_sug = "SELECT TITULO, AUTOR, TEMA FROM pm_acerv WHERE EMPRESTIMO = 'N' AND (TEMA = '$tema' OR AUTOR = '$autor') $where_lidos LIMIT 5";
                                $res_sug = $conn->query($sql_sug);
                                while ($row = $res_sug->fetch_assoc()) {
                                    $sugestoes[] = $row;
                                }
                                if (count($sugestoes) >= 5) break;
                            }
                            // Limitar a 5 sugestões únicas
                            $sugestoes = array_slice(array_unique($sugestoes, SORT_REGULAR), 0, 5);
                        }
                        // Se não houver sugestões personalizadas, buscar aleatórias
                        if (count($sugestoes) == 0) {
                            $sql_sug = "SELECT TITULO, AUTOR, TEMA FROM pm_acerv WHERE EMPRESTIMO = 'N' $where_lidos ORDER BY RAND() LIMIT 5";
                            $res_sug = $conn->query($sql_sug);
                            while ($row = $res_sug->fetch_assoc()) {
                                $sugestoes[] = $row;
                            }
                        }
                        ?>
                        <?php if (count($sugestoes) > 0): ?>
                        <div class="table-responsive">
                            <table class="tabela">
                                <thead class="table-light">
                                    <tr>
                                        <th>Título</th>
                                        <th>Autor</th>
                                        <th>Tema</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sugestoes as $s): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($s['TITULO']) ?></td>
                                        <td><?= htmlspecialchars($s['AUTOR']) ?></td>
                                        <td><?= htmlspecialchars($s['TEMA']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info mb-0">Nenhuma sugestão disponível.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('temasChart').getContext('2d');
            const config = {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: []
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 10,
                                boxWidth: 15,
                                font: { size: 12 }
                            }
                        }
                    }
                }
            };

            const chart = new Chart(ctx, config);

            fetch('../api/categorias-chart.php')
                .then(response => response.json())
                .then(data => {
                    chart.data.labels = data.labels;
                    chart.data.datasets[0].data = data.data;
                    chart.data.datasets[0].backgroundColor = data.colors;
                    chart.update();
                })
                .catch(error => {
                    console.error('Erro:', error);
                    document.getElementById('temasChart').closest('.card').innerHTML = `
                        <div class="alert alert-danger m-3">
                            Não foi possível carregar o gráfico
                        </div>
                    `;
                });

            // Timeout de sessão (15 minutos)
            let timeout = setTimeout(() => window.location.href = 'login.php', 900000);
            document.addEventListener('mousemove', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => window.location.href = 'login.php', 900000);
            });
        });
    </script>
</body>

</html>