<?php
session_start();
require __DIR__ . '/../app/funcoes.php';

if (!isset($_SESSION['logado']) || !isAdmin()) {
    header("Location: login.php");
    exit();
}

require __DIR__ . '/../app/conexao.php';

// Processar quitação de multa ANTES de qualquer include/menu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quitar_multa'])) {
    $usuario_id = (int) $_POST['usuario_id'];
    $valor_multa = (float) str_replace(',', '.', str_replace('.', '', $_POST['valor_multa']));
    
    // Verificar se há multa pendente
    $stmt_check = $conn->prepare("SELECT COUNT(*) as total FROM emprest WHERE EMPUSUA = ? AND EMPMULTA > 0 AND EMPPAGO = FALSE");
    $stmt_check->bind_param("i", $usuario_id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    $row_check = $res_check->fetch_assoc();
    if ($row_check['total'] == 0) {
        $_SESSION['error'] = "Não há multa pendente para este usuário.";
        header("Location: usuario.php");
        exit();
    }
    
    // Validar valor da multa
    if ($valor_multa <= 0) {
        $_SESSION['error'] = "Valor da multa inválido";
        header("Location: usuario.php");
        exit();
    }
    
    // Registrar pagamento no histórico
    $stmt = $conn->prepare("INSERT INTO pagamentos (usuario_id, valor, data_pagamento) VALUES (?, ?, NOW())");
    $stmt->bind_param("id", $usuario_id, $valor_multa);
    $stmt->execute();
    $pagamento_id = $conn->insert_id;
    
    // Marcar todos os empréstimos com multa pendente como pagos
    $stmt2 = $conn->prepare("UPDATE emprest SET EMPPAGO = TRUE WHERE EMPUSUA = ? AND EMPMULTA > 0 AND EMPPAGO = FALSE");
    $stmt2->bind_param("i", $usuario_id);
    $stmt2->execute();
    
    if ($stmt->affected_rows > 0) {
        header("Location: comprovante_multa.php?id={$pagamento_id}");
        exit();
    } else {
        $_SESSION['error'] = "Erro ao quitar multa: " . $stmt->error;
        header("Location: usuario.php");
        exit();
    }
}

include __DIR__ . '/../app/menu.php'; ?>
<div class="dp-banner">
    <div class="container text-center">
        <h1><i class="fas fa-users me-3"></i>Gestão de Usuários</h1>
        <p class="lead">Gerencie, edite e visualize os usuários cadastrados no sistema</p>
        <div class="mt-4">
            <span class="badge bg-light text-dark me-2"><i class="fas fa-user me-1"></i> Usuários Ativos</span>
            <span class="badge bg-light text-dark me-2"><i class="fas fa-id-card me-1"></i> Dados Cadastrais</span>
        </div>
    </div>
</div>

<?php
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

// Processar atualização de usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_usuario'])) {
    $codigo = (int) $_POST['codigo'];
    $nome = $_POST['nome'];
    $endereco = $_POST['endereco'];
    $cidade = $_POST['cidade'];
    $bairro = $_POST['bairro'];
    $cep = $_POST['cep'];
    $estado = $_POST['estado'];
    $rg = $_POST['rg'];
    $cic = $_POST['cic'];
    $telefone = $_POST['telefone'];
    $codbarras = $_POST['codbarras'];
    $email = $_POST['email'];

    // Validações
    if (empty($nome) || empty($endereco) || empty($cep) || empty($estado) || empty($rg) || empty($cic) || empty($email)) {
        $_SESSION['error'] = 'Preencha todos os campos obrigatórios.';
        header("Location: usuario.php");
        exit();
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'E-mail inválido.';
        header("Location: usuario.php");
        exit();
    } elseif (!preg_match('/^\(?\d{2}\)?[\s-]?\d{4,5}-?\d{4}$/', $telefone)) {
        $_SESSION['error'] = 'Telefone inválido. Use o formato (99) 99999-9999.';
        header("Location: usuario.php");
        exit();
    } elseif (!validar_cpf($cic)) {
        $_SESSION['error'] = 'CPF inválido.';
        header("Location: usuario.php");
        exit();
    } elseif (!validar_rg($rg)) {
        $_SESSION['error'] = 'RG inválido.';
        header("Location: usuario.php");
        exit();
    } else {
        // Impedir duplicidade de e-mail, CPF e RG (exceto do próprio usuário)
        $stmt_check = $conn->prepare("SELECT CODIGO, EMAIL, CIC, RG FROM pm_usua WHERE (EMAIL = ? OR CIC = ? OR RG = ?) AND CODIGO != ? LIMIT 1");
        $stmt_check->bind_param("sssi", $email, $cic, $rg, $codigo);
        $stmt_check->execute();
        $dup = $stmt_check->get_result()->fetch_assoc();
        if ($dup) {
            if ($dup['EMAIL'] === $email) {
                $_SESSION['error'] = 'Este e-mail já está cadastrado.';
            } elseif ($dup['CIC'] === $cic) {
                $_SESSION['error'] = 'Este CPF já está cadastrado.';
            } elseif ($dup['RG'] === $rg) {
                $_SESSION['error'] = 'Este RG já está cadastrado.';
            } else {
                $_SESSION['error'] = 'Dados já cadastrados.';
            }
            header("Location: usuario.php");
            exit();
        }
    }

    $stmt = $conn->prepare("UPDATE pm_usua SET NOME = ?, ENDERECO = ?, CIDADE = ?, BAIRRO = ?, CEP = ?, ESTADO = ?, RG = ?, CIC = ?, TELEFONE = ?, CODBARRAS = ?, EMAIL = ? WHERE CODIGO = ?");
    $stmt->bind_param("sssssssssssi", $nome, $endereco, $cidade, $bairro, $cep, $estado, $rg, $cic, $telefone, $codbarras, $email, $codigo);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Usuário atualizado com sucesso!";
    } else {
        $_SESSION['error'] = "Erro ao atualizar usuário: " . $stmt->error;
    }

    header("Location: usuario.php");
    exit();
}

// Configuração de pesquisa
$termo_pesquisa = isset($_GET['pesquisa']) ? trim($_GET['pesquisa']) : '';
$campo_pesquisa = isset($_GET['campo']) ? $_GET['campo'] : 'nome';

// Configuração de paginação
$registros_por_pagina = 25;
$pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$offset = max(0, ($pagina - 1) * $registros_por_pagina);

// Construir query base
$where = '';
$params = [];
$types = '';

if (!empty($termo_pesquisa)) {
    $termo_like = "%" . $conn->real_escape_string($termo_pesquisa) . "%";

    switch ($campo_pesquisa) {
        case 'codigo':
            $where = " WHERE CODIGO LIKE ?";
            $types = 'i';
            $params = [&$termo_pesquisa];
            break;
        case 'cidade':
            $where = " WHERE CIDADE LIKE ?";
            $types = 's';
            $params = [&$termo_like];
            break;
        case 'telefone':
            $where = " WHERE TELEFONE LIKE ?";
            $types = 's';
            $params = [&$termo_like];
            break;
        case 'email':
            $where = " WHERE EMAIL LIKE ?";
            $types = 's';
            $params = [&$termo_like];
            break;
        default: // nome
            $where = " WHERE NOME LIKE ?";
            $types = 's';
            $params = [&$termo_like];
            break;
    }
}

// Total de usuários
$sql_count = "SELECT COUNT(*) AS total FROM pm_usua $where";
$stmt_count = $conn->prepare($sql_count);

if (!empty($where)) {
    $stmt_count->bind_param($types, ...$params);
}

$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_usuarios = $result_count->fetch_assoc()['total'];
$total_paginas = ceil($total_usuarios / $registros_por_pagina);

// Query com paginação
$sql_usuarios = "SELECT 
                    u.CODIGO, 
                    u.NOME, 
                    u.CIDADE, 
                    u.TELEFONE, 
                    u.EMAIL, 
                    u.DATACAD,
                    (
                        COALESCE((SELECT SUM(EMPMULTA) FROM emprest WHERE EMPUSUA = u.CODIGO AND EMPPAGO = FALSE), 0) +
                        COALESCE((SELECT SUM(Multa) FROM emprest_cd WHERE Usuario = u.CODIGO AND Pago = FALSE), 0) +
                        COALESCE((SELECT SUM(Multa) FROM emprest_video WHERE Usuario = u.CODIGO AND Pago = FALSE), 0)
                    ) AS MULTA_PENDENTE
                 FROM pm_usua u
                 $where
                 ORDER BY u.NOME
                 LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql_usuarios);
$limit = $registros_por_pagina;
$offset_val = $offset;

if (!empty($where)) {
    $params[] = &$limit;
    $params[] = &$offset_val;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param("ii", $limit, $offset_val);
}

$stmt->execute();
$usuarios = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Usuários</title>
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
        .multa-pendente {
            font-weight: bold;
            color: #dc3545;
        }
        .btn-quitar {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
    <script>
        function editarUsuario(codigo, nome, cidade, telefone, email, multa, endereco = '', bairro = '', cep = '', estado = '', rg = '', cic = '', codbarras = '') {
            document.getElementById('editCodigo').value = codigo;
            document.getElementById('editNome').value = nome;
            document.getElementById('editEndereco').value = endereco;
            document.getElementById('editCidade').value = cidade;
            document.getElementById('editBairro').value = bairro;
            document.getElementById('editCep').value = cep;
            document.getElementById('editEstado').value = estado;
            document.getElementById('editRg').value = rg;
            document.getElementById('editCic').value = cic;
            document.getElementById('editTelefone').value = telefone;
            document.getElementById('editCodbarras').value = codbarras;
            document.getElementById('editEmail').value = email;
            document.getElementById('editMulta').value = multa.toFixed(2);

            const modal = new bootstrap.Modal(document.getElementById('editarUsuarioModal'));
            modal.show();
        }
        
        function quitarMulta(usuarioId, multa) {
            document.getElementById('quitarUsuarioId').value = usuarioId;
            document.getElementById('quitarMultaValor').value = multa.toFixed(2);
            document.getElementById('valorMultaDisplay').textContent = multa.toFixed(2).replace('.', ',');
            
            const modal = new bootstrap.Modal(document.getElementById('quitarMultaModal'));
            modal.show();
        }
    </script>
</head>
<body>
    <div class="container mt-4">
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

        <!-- Formulário de Pesquisa -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 style="color: white">🔍 Pesquisar Usuários</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-5">
                        <input type="text" name="pesquisa" class="form-control" placeholder="Digite sua pesquisa..."
                            value="<?= htmlspecialchars($termo_pesquisa) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="campo" class="form-select">
                            <option value="nome" <?= $campo_pesquisa == 'nome' ? 'selected' : '' ?>>Nome</option>
                            <option value="codigo" <?= $campo_pesquisa == 'codigo' ? 'selected' : '' ?>>Código</option>
                            <option value="cidade" <?= $campo_pesquisa == 'cidade' ? 'selected' : '' ?>>Cidade</option>
                            <option value="telefone" <?= $campo_pesquisa == 'telefone' ? 'selected' : '' ?>>Telefone
                            </option>
                            <option value="email" <?= $campo_pesquisa == 'email' ? 'selected' : '' ?>>E-mail</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Pesquisar</button>
                    </div>
                    <div class="col-md-2">
                        <a href="cadastro_usuario.php" class="btn btn-success w-100">➕ Cadastrar</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Usuários -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 style="color: white;">📋 Usuários Cadastrados</h5>
                <span class="badge bg-primary"><?= number_format($total_usuarios) ?> registros</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="tabela">
                        <thead class="table-dark">
                            <tr>
                                <th>Código</th>
                                <th>Nome</th>
                                <th>Cidade</th>
                                <th>Telefone</th>
                                <th>E-mail</th>
                                <th>Data Cadastro</th>
                                <th>Multa Pendente</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($usuarios && $usuarios->num_rows > 0): ?>
                                <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($usuario['CODIGO']) ?></td>
                                        <td><?= htmlspecialchars($usuario['NOME']) ?></td>
                                        <td><?= htmlspecialchars($usuario['CIDADE']) ?></td>
                                        <td><?= htmlspecialchars($usuario['TELEFONE']) ?></td>
                                        <td><?= htmlspecialchars($usuario['EMAIL'] ?? 'Não informado') ?></td>
                                        <td><?= $usuario['DATACAD'] ? date('d/m/Y', strtotime($usuario['DATACAD'])) : 'N/A' ?>
                                        </td>
                                        <td class="multa-pendente">
                                            <?php if ($usuario['MULTA_PENDENTE'] > 0): ?>
                                                R$ <?= number_format($usuario['MULTA_PENDENTE'], 2, ',', '.') ?>
                                                <button class="btn btn-sm btn-danger btn-quitar ms-2" 
                                                        onclick="quitarMulta(<?= $usuario['CODIGO'] ?>, <?= $usuario['MULTA_PENDENTE'] ?>)">
                                                    Quitar
                                                </button>
                                            <?php else: ?>
                                                R$ 0,00
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editarUsuario(
                                                        '<?= $usuario['CODIGO'] ?>',
                                                        '<?= htmlspecialchars($usuario['NOME'], ENT_QUOTES) ?>',
                                                        '<?= htmlspecialchars($usuario['CIDADE'], ENT_QUOTES) ?>',
                                                        '<?= htmlspecialchars($usuario['TELEFONE'], ENT_QUOTES) ?>',
                                                        '<?= htmlspecialchars($usuario['EMAIL'], ENT_QUOTES) ?>',
                                                        <?= $usuario['MULTA_PENDENTE'] ?>
                                                    )">
                                                ✏️ Editar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">Nenhum usuário encontrado</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Paginação -->
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="text-muted">
                Página <?= $pagina ?> de <?= $total_paginas ?>
            </div>

            <ul class="pagination pagination-sm mb-0">
                <?php
                $query_params = $_GET;
                unset($query_params['pagina']);
                $base_url = '?' . http_build_query($query_params);
                ?>

                <?php if ($pagina > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= $base_url ?>&pagina=1">
                            &laquo;
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="<?= $base_url ?>&pagina=<?= $pagina - 1 ?>">
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
                        <a class="page-link" href="<?= $base_url ?>&pagina=<?= $pagina + 1 ?>">
                            &rsaquo;
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="<?= $base_url ?>&pagina=<?= $total_paginas ?>">
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

    <!-- Modal de Edição -->
    <div class="modal fade" id="editarUsuarioModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">✏️ Editar Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="codigo" id="editCodigo">

                        <div class="mb-3">
                            <label class="form-label">Nome</label>
                            <input type="text" name="nome" id="editNome" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Endereço</label>
                            <input type="text" name="endereco" id="editEndereco" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cidade</label>
                            <input type="text" name="cidade" id="editCidade" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bairro</label>
                            <input type="text" name="bairro" id="editBairro" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">CEP</label>
                            <input type="text" name="cep" id="editCep" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estado</label>
                            <input type="text" name="estado" id="editEstado" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">RG</label>
                            <input type="text" name="rg" id="editRg" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">CPF</label>
                            <input type="text" name="cic" id="editCic" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Telefone</label>
                            <input type="text" name="telefone" id="editTelefone" class="form-control" pattern="^\(?\d{2}\)?[\s-]?\d{4,5}-?\d{4}$" title="Digite um telefone válido (ex: (11) 91234-5678)">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Código de Barras</label>
                            <input type="text" name="codbarras" id="editCodbarras" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="email" id="editEmail" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Multa Pendente</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="text" id="editMulta" class="form-control" readonly>
                            </div>
                            <small class="text-muted">Para quitar multas, use o botão "Quitar" na lista de usuários</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="atualizar_usuario" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="quitarMultaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">💵 Quitar Multa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="usuario_id" id="quitarUsuarioId">
                        
                        <div class="alert alert-warning">
                            <strong>Atenção:</strong> Esta ação registrará o pagamento da multa e não pode ser desfeita.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Valor da Multa</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="text" id="quitarMultaValor" name="valor_multa" class="form-control" required
                                       pattern="^\d+(,\d{1,2})?$" 
                                       title="Digite um valor monetário válido (ex: 15,50)">
                            </div>
                            <div class="form-text">Valor pendente: R$ <span id="valorMultaDisplay"></span></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Data do Pagamento</label>
                            <input type="date" name="data_pagamento" class="form-control" 
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="quitar_multa" class="btn btn-danger">Confirmar Quitação</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validação do campo de multa
        document.querySelector('input[name="valor_multa"]').addEventListener('input', function(e) {
            // Permite apenas números e vírgula
            this.value = this.value.replace(/[^0-9,]/g, '');
            
            // Limita a duas casas decimais
            if(this.value.split(',')[1] && this.value.split(',')[1].length > 2) {
                this.value = this.value.substring(0, this.value.length - 1);
            }
        });
    </script>
</body>

</html>