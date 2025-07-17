<?php
session_start();
require __DIR__ . '/../app/funcoes.php';

// Padronização: garantir inclusão do menu e estrutura HTML
if (!isset($_SESSION['logado']) || !isAdmin()) {
    header("Location: login.php");
    exit();
}
require __DIR__ . '/../app/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_livro'])) {
    $titulo = $_POST['titulo'] ?? '';
    $autor = $_POST['autor'] ?? '';
    $tema = $_POST['tema'] ?? '';
    $editora = $_POST['editora'] ?? '';
    $ano = $_POST['ano'] ?? '';
    $isbn = $_POST['isbn'] ?? '';

    // Validação de campos obrigatórios
    if (empty($titulo) || empty($autor) || empty($tema) || empty($editora) || empty($ano) || empty($isbn)) {
        $error = 'Preencha todos os campos obrigatórios.';
    } else {
        // Campos obrigatórios do banco pm_acerv
        $pais = 'BRASIL';
        $tipo = 'LIVRO';
        $classifica = 'GERAL';
        $inicialtit = strtoupper(substr($titulo, 0, 1));
        $edicao = '1ª';
        $localpub = 'NAO_INFORMADO';
        $estado = 'SP';
        $statusliv = 'ATIVO';
        $entrada = date('Y-m-d');
        $volume = 1;
        $origem = 'COMPRA';
        $valor = 0.0;
        $paginas = 100; // Valor padrão
        $emprestimo = 0; // FALSE
        $quantidade = 1;

        try {
            $stmt = $conn->prepare("INSERT INTO pm_acerv (
                CODIGO, TITULO, AUTOR, ANO, ISBN, TEMA, PAIS, TIPO, 
                CLASSIFICA, INICIALTIT, EDICAO, LOCALPUB, ESTADO, 
                STATUSLIV, ENTRADA, VOLUME, ORIGEM, VALOR, PAGINAS, 
                EMPRESTIMO, QUANTIDADE
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ississssssssssisissii", 
                $codigo, $titulo, $autor, $ano, $isbn, $tema, $pais, $tipo,
                $classifica, $inicialtit, $edicao, $localpub, $estado,
                $statusliv, $entrada, $volume, $origem, $valor, $paginas,
                $emprestimo, $quantidade
            );
            if ($stmt->execute()) {
                $sucesso = "Livro cadastrado com sucesso!";
            } else {
                $erro = "Erro ao cadastrar: " . $stmt->error;
            }
        } catch (Exception $e) {
            $erro = "Erro: " . $e->getMessage();
        }
    }
}

// Listar livros existentes
$livros = $conn->query("
    SELECT CODIGO, TITULO, AUTOR, ANO, ISBN, TEMA, EMPRESTIMO
    FROM pm_acerv
    ORDER BY CODIGO DESC
    LIMIT 20
");
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Livros</title>
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
    <?php include __DIR__ . '/../app/menu.php'; ?>
    <div class="dp-banner">
        <div class="container text-center">
            <h1><i class="fas fa-book-open me-3"></i>Cadastro de Livros</h1>
            <p class="lead">Adicione novos livros ao acervo e visualize os últimos cadastrados</p>
            <div class="mt-4">
                <span class="badge bg-light text-dark me-2"><i class="fas fa-plus me-1"></i> Novo Livro</span>
                <span class="badge bg-light text-dark me-2"><i class="fas fa-list me-1"></i> Últimos Cadastrados</span>
            </div>
        </div>
    </div>

    <!-- Padronização de mensagens -->
    <?php if (isset($erro)): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <?php if (isset($sucesso)): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <div class="container mt-4">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Código/Tombo</label>
                    <input type="number" name="codigo" class="form-control" style="border: 2px solid gray;" 
                           required min="1" step="1" value="<?= rand(100000, 999999) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Título *</label>
                    <input type="text" name="titulo" class="form-control" style="border: 2px solid gray;" 
                           required maxlength="255">
                </div>

                <div class="col-md-4">
                    <label class="form-label">ISBN</label>
                    <input type="text" name="isbn" class="form-control" style="border: 2px solid gray;" 
                           maxlength="20" placeholder="Ex: 978-85-xxxxx-xx-x">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Autor(a) *</label>
                    <input type="text" name="autor" class="form-control" style="border: 2px solid gray;" 
                           required maxlength="100">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Ano de Publicação *</label>
                    <input type="number" name="ano" class="form-control" style="border: 2px solid gray;" 
                           min="1900" max="<?= date('Y') ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Tema *</label>
                    <input type="text" name="tema" class="form-control" style="border: 2px solid gray;" 
                           required maxlength="20" placeholder="Ex: Romance, Ficção">
                </div>
            </div>
            
            <button type="submit" name="cadastrar" class="btn btn-success mt-3">
                📖 Cadastrar Livro
            </button>
        </form>

        <hr class="my-5">

        <h3 class="mb-3">📚 Últimos Livros Cadastrados</h3>
        <div class="table-responsive">
            <table class="tabela">
                <thead class="table-dark">
                    <tr>
                        <th>Código</th>
                        <th>Título</th>
                        <th>Autor</th>
                        <th>Ano</th>
                        <th>ISBN</th>
                        <th>Tema</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($livros && $livros->num_rows > 0): ?>
                        <?php while ($livro = $livros->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($livro['CODIGO']) ?></td>
                                <td><?= htmlspecialchars($livro['TITULO']) ?></td>
                                <td><?= htmlspecialchars($livro['AUTOR']) ?></td>
                                <td><?= htmlspecialchars($livro['ANO']) ?></td>
                                <td><?= htmlspecialchars($livro['ISBN'] ?? 'Não informado') ?></td>
                                <td><?= htmlspecialchars($livro['TEMA']) ?></td>
                                <td>
                                    <span class="badge <?= $livro['EMPRESTIMO'] ? 'bg-danger' : 'bg-success' ?>">
                                        <?= $livro['EMPRESTIMO'] ? 'Emprestado' : 'Disponível' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center">Nenhum livro cadastrado</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>