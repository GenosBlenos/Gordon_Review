<?php
// Ativar relatórios de erro para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require __DIR__ . '/../app/funcoes.php';
// echo "<pre>";
// print_r($_SESSION);
// echo "</pre>";
// exit;

// Função para obter o link de download do livro
function obter_link_download($titulo)
{
    // Gera um nome de arquivo seguro e fictício para o exemplo
    $arquivo = preg_replace('/[^a-zA-Z0-9-_]/', '_', strtolower($titulo)) . '.pdf';
    return "downloads/" . $arquivo;
}

// Função para obter uma descrição fictícia do livro
function obter_descricao($titulo)
{
    $descricoes = [
        'Dom Casmurro' => 'Um dos romances mais famosos de Machado de Assis, explora temas como ciúme, dúvida e memória através do personagem Bentinho.',
        'O Cortiço' => 'Obra naturalista de Aluísio Azevedo que retrata a vida em um cortiço carioca e as relações sociais e raciais do Brasil do século XIX.',
        'Memórias Póstumas de Brás Cubas' => 'Romance inovador de Machado de Assis, narrado por um defunto, com crítica social e humor ácido.',
        'Quincas Borba' => 'Machado de Assis apresenta a filosofia do Humanitismo e a trajetória de Rubião, discípulo de Quincas Borba.',
        'Iracema' => 'Romance indianista de José de Alencar, narra o encontro entre o colonizador português e a índia Iracema.',
        'O Guarani' => 'Clássico de José de Alencar, mistura aventura, romance e o cenário da colonização do Brasil.',
        'A Moreninha' => 'Primeiro romance de Joaquim Manuel de Macedo, conta uma história de amor juvenil ambientada no Rio de Janeiro.',
        'O Ateneu' => 'Raul Pompeia narra a vida de Sérgio em um internato, abordando temas como educação e sociedade.',
        'Os Sertões' => 'Obra-prima de Euclides da Cunha sobre a Guerra de Canudos, mistura literatura, sociologia e história.',
        'Triste Fim de Policarpo Quaresma' => 'Lima Barreto critica o nacionalismo exagerado e a burocracia brasileira através do personagem Policarpo Quaresma.',
        'Senhora' => 'Romance de José de Alencar que aborda questões sociais e o papel da mulher na sociedade do século XIX.',
        'Ramo de Loiro, notícias em louvor' => 'Obra de João do Rio, reúne crônicas e textos sobre a vida carioca.',
        'O Seminarista' => 'Bernardo Guimarães narra o drama de Eugênio, dividido entre o amor e a vocação religiosa.',
        'Lucíola' => 'Romance urbano de José de Alencar, conta a história de uma cortesã e seu amor impossível.',
        'O Mulato' => 'Aluísio Azevedo aborda o preconceito racial e social no Maranhão do século XIX.',
        'Dom Quixote' => 'Miguel de Cervantes narra as aventuras do cavaleiro sonhador Dom Quixote e seu fiel escudeiro Sancho Pança.',
        'Orgulho e Preconceito' => 'Jane Austen explora as relações sociais e amorosas na Inglaterra do século XIX.',
        'Aventuras de Sherlock Holmes' => 'Coletânea de contos de Arthur Conan Doyle sobre o famoso detetive Sherlock Holmes.',
        'Os Miseráveis' => 'Victor Hugo narra a saga de Jean Valjean em meio à injustiça social na França.',
        'A Divina Comédia' => 'Poema épico de Dante Alighieri, descreve a jornada pelo Inferno, Purgatório e Paraíso.',
        'O Retrato de Dorian Gray' => 'Oscar Wilde explora temas de beleza, moralidade e decadência através de Dorian Gray.',
        'Moby Dick' => 'Herman Melville narra a obsessão do capitão Ahab pela baleia branca.',
        'As Aventuras de Tom Sawyer' => '',
    ];
    return $descricoes[$titulo] ?? "Descrição não disponível para o livro \"{$titulo}\".";
}

// Padronização: garantir inclusão do menu e estrutura HTML
if (!isset($_SESSION['logado'])) {
    header("Location: login.php");
    exit();
}
require __DIR__ . '/../app/conexao.php';
include __DIR__ . '/../app/menu.php';

$page_title = "Livros de Domínio Público";

$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$termo_pesquisa = isset($_GET['pesquisa']) ? $_GET['pesquisa'] : '';

// Corrigir a contagem de livros e páginas dinamicamente
$livros_por_pagina = 6;
$livros = [
    [
        'titulo' => 'Dom Casmurro',
        'autor' => 'Machado de Assis',
        'ano' => 1899,
        'fonte' => 'Brasiliana USP',
        'badge' => 'POPULAR',
        'link' => 'https://digital.bbm.usp.br/bitstream/bbm/4828/1/002038_COMPLETO.pdf'
    ],
    [
        'titulo' => 'O Cortiço',
        'autor' => 'Aluísio Azevedo',
        'ano' => 1890,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://digital.bbm.usp.br/bitstream/bbm/4817/1/002279_COMPLETO.pdf'
    ],
    [
        'titulo' => 'Memórias Póstumas de Brás Cubas',
        'autor' => 'Machado de Assis',
        'ano' => 1881,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://digital.bbm.usp.br/bitstream/bbm/4826/1/002078_COMPLETO.pdf'
    ],
    [
        'titulo' => 'Quincas Borba',
        'autor' => 'Machado de Assis',
        'ano' => 1891,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://digital.bbm.usp.br/bitstream/bbm/5251/1/002113_COMPLETO.pdf'
    ],
    [
        'titulo' => 'Iracema',
        'autor' => 'José de Alencar',
        'ano' => 1865,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://digital.bbm.usp.br/bitstream/bbm/4660/1/001783_COMPLETO.pdf'
    ],
    [
        'titulo' => 'O Guarani',
        'autor' => 'José de Alencar',
        'ano' => 1857,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://digital.bbm.usp.br/bitstream/bbm/4655/1/001775_COMPLETO.pdf'
    ],
    [
        'titulo' => 'A Moreninha',
        'autor' => 'Joaquim Manuel de Macedo',
        'ano' => 1844,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://digital.bbm.usp.br/bitstream/bbm/7839/1/45000017135_Output.o.pdf'
    ],
    [
        'titulo' => 'O Ateneu',
        'autor' => 'Raul Pompeia',
        'ano' => 1888,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://digital.bbm.usp.br/bitstream/bbm/5000/1/015056_COMPLETO.pdf'
    ],
    [
        'titulo' => 'Os Sertões',
        'autor' => 'Euclides da Cunha',
        'ano' => 1902,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://digital.bbm.usp.br/bitstream/bbm/5351/1/004518_COMPLETO.pdf'
    ],
    [
        'titulo' => 'Triste Fim de Policarpo Quaresma',
        'autor' => 'Lima Barreto',
        'ano' => 1915,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://digital.bbm.usp.br/bitstream/bbm/4361/1/001181_COMPLETO.pdf'
    ],
    [
        'titulo' => 'Senhora',
        'autor' => 'José de Alencar',
        'ano' => 1875,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://digital.bbm.usp.br/bitstream/bbm/4646/1/001813-1_COMPLETO.pdf'
    ],
    [
        'titulo' => 'Ramo de Loiro, notícias em louvor',
        'autor' => 'João do Rio',
        'ano' => 1905,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://digital.bbm.usp.br/bitstream/bbm/787/1/45000008006_Output.o.pdf'
    ],
    [
        'titulo' => 'O Seminarista',
        'autor' => 'Bernardo Guimarães',
        'ano' => 1872,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://digital.bbm.usp.br/bitstream/bbm/8401/1/45000018837_Output.o.pdf'
    ],
    [
        'titulo' => 'Lucíola',
        'autor' => 'José de Alencar',
        'ano' => 1862,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://digital.bbm.usp.br/bitstream/bbm/4664/1/001797_COMPLETO.pdf'
    ],
    [
        'titulo' => 'O Mulato',
        'autor' => 'Aluísio Azevedo',
        'ano' => 1881,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://digital.bbm.usp.br/bitstream/bbm/4812/1/002298_COMPLETO.pdf'
    ],
    // Internacionais
    [
        'titulo' => 'Dom Quixote',
        'autor' => 'Miguel de Cervantes',
        'ano' => 1605,
        'fonte' => 'Internet Archive (inglês)',
        'link' => 'https://archive.org/download/bwb_C0-CEK-602/bwb_C0-CEK-602.pdf'
    ],
    [
        'titulo' => 'Orgulho e Preconceito',
        'autor' => 'Jane Austen',
        'ano' => 1813,
        'fonte' => 'Internet Archive (inglês)',
        'link' => 'https://archive.org/download/isbn_9781905716883/isbn_9781905716883.pdf'
    ],
    [
        'titulo' => 'Aventuras de Sherlock Holmes',
        'autor' => 'Arthur Conan Doyle',
        'ano' => 1892,
        'fonte' => 'Internet Archive (inglês)',
        'link' => 'https://archive.org/download/bwb_S0-DTJ-317/bwb_S0-DTJ-317.pdf'
    ],
    [
        'titulo' => 'Os Miseráveis',
        'autor' => 'Victor Hugo',
        'ano' => 1862,
        'fonte' => 'Internet Archive (inglês)',
        'link' => 'https://archive.org/download/bwb_S0-CDV-892/bwb_S0-CDV-892.pdf'
    ],
    [
        'titulo' => 'A Divina Comédia',
        'autor' => 'Dante Alighieri',
        'ano' => 1320,
        'fonte' => 'Internet Archive (inglês)',
        'link' => 'https://archive.org/download/dantesinferno1880000unse/dantesinferno1880000unse.pdf'
    ],
    [
        'titulo' => 'O Retrato de Dorian Gray',
        'autor' => 'Oscar Wilde',
        'ano' => 1890,
        'fonte' => 'Internet Archive (inglês)',
        'link' => 'https://archive.org/download/pictureofdoriang0000osca_c1q1/pictureofdoriang0000osca_c1q1.pdf'
    ],
    [
        'titulo' => 'Moby Dick',
        'autor' => 'Herman Melville',
        'ano' => 1851,
        'fonte' => 'Projeto Gutenberg (inglês)',
        'link' => 'https://archive.org/download/bwb_C0-AMF-926/bwb_C0-AMF-926.pdf'
    ],
    [
        'titulo' => 'As Aventuras de Tom Sawyer',
        'autor' => 'Mark Twain',
        'ano' => 1910,
        'fonte' => 'Internet Archive (inglês)',
        'link' => 'https://archive.org/download/adventuresoftoms0000mark_v1s0/adventuresoftoms0000mark_v1s0_slip.png'
    ]
];
$total_registros = count($livros);
$total_paginas = ceil($total_registros / $livros_por_pagina);

// Cálculo dos cards
$autores_brasileiros = [];
$livros_seculo_xix = 0;
foreach ($livros as $livro) {
    // Considera "Brasiliana USP" como fonte brasileira
    if (strpos($livro['fonte'], 'Brasiliana USP') !== false || strpos($livro['fonte'], 'Domínio Público Portugal') !== false) {
        $autores_brasileiros[] = $livro['autor'];
    }
    // Século XIX: 1801 a 1900
    if ($livro['ano'] >= 1801 && $livro['ano'] <= 1900) {
        $livros_seculo_xix++;
    }
}
$autores_brasileiros = count(array_unique($autores_brasileiros));
$avaliacao_media = 'N/A'; // Não há dados de avaliação

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['titulo'], $_POST['autor'], $_POST['ano'], $_POST['fonte'], $_POST['link'])) {
    $novo_livro = [
        'titulo' => $_POST['titulo'],
        'autor' => $_POST['autor'],
        'ano' => intval($_POST['ano']),
        'fonte' => $_POST['fonte'],
        'link' => $_POST['link'],
        'badge' => 'NOVO'
    ];
    array_unshift($livros, $novo_livro); // Adiciona no início do array
    $total_registros = count($livros);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Livros de Domínio Público' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../styles/sense.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08);
            padding: 32px 0 18px 0;
            text-align: center;
            transition: box-shadow 0.3s, transform 0.3s;
            margin-bottom: 16px;
        }

        .stat-card:hover {
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.16);
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
            box-shadow: 0 4px 18px rgba(52, 152, 219, 0.08);
            padding: 32px 0 24px 0;
            margin-bottom: 32px;
        }

        .dp-banner h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #fff;
            text-shadow: 1px 1px 8px rgba(0, 0, 0, 0.18);
        }

        .dp-banner .lead {
            color: #f5f5f5;
            font-size: 1.2rem;
            margin-bottom: 12px;
            text-shadow: 1px 1px 6px rgba(0, 0, 0, 0.12);
        }

        .dp-banner .badge {
            background: #fff;
            color: #3498db;
            font-weight: 600;
            border-radius: 18px;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.10);
        }

        .btn-success,
        .btn-atualizar {
            background: linear-gradient(90deg, #283e51 0%, #485563 100%);
            border: none;
            font-weight: 600;
            color: #fff;
            box-shadow: 0 2px 8px rgba(40, 62, 81, 0.12);
            transition: background 0.3s;
        }

        .btn-success:hover,
        .btn-atualizar:hover {
            background: linear-gradient(90deg, #485563 0%, #283e51 100%);
            color: #fff;
        }

        .btn-download {
            background: #007bff;
            border: none;
            color: #fff;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.12);
            transition: background 0.3s;
        }

        .btn-download:hover {
            background: #0056b3;
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="dp-banner">
        <div class="container text-center">
            <h1><i class="fas fa-crown me-3"></i>Livros de Domínio Público</h1>
            <p class="lead">Explore nossa coleção de obras literárias clássicas disponíveis gratuitamente</p>
            <div class="mt-4">
                <span class="badge bg-light text-dark me-2"><i class="fas fa-book me-1"></i> Download Gratuito</span>
                <span class="badge bg-light text-dark me-2"><i class="fas fa-copyright me-1"></i> Sem Restrições</span>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row mb-4">
            <?php if (function_exists('isAdmin') && isAdmin()): ?>
                <div class="col-md-3 text-end">
                    <div class="d-grid">
                        <button class="btn btn-success btn-atualizar" data-bs-toggle="modal"
                            data-bs-target="#modalNovoLivro"><i class="fas fa-sync-alt me-2"></i>Adicionar ao
                            Acervo</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-md-12">
            <form method="GET" action="dominio_publico.php" id="search-form">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="pesquisa" class="form-control form-control-lg"
                        placeholder="Pesquisar livros, autores, temas..."
                        value="<?= htmlspecialchars($termo_pesquisa) ?>">
                    <button class="btn btn-primary" type="submit">Buscar</button>
                </div>
            </form>
        </div>
        <!-- Modal Novo Livro -->
        <div class="modal fade" id="modalNovoLivro" tabindex="-1" aria-labelledby="modalNovoLivroLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="dominio_publico.php">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalNovoLivroLabel">Cadastrar Novo Livro</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" required>
                            </div>
                            <div class="mb-3">
                                <label for="autor" class="form-label">Autor</label>
                                <input type="text" class="form-control" id="autor" name="autor" required>
                            </div>
                            <div class="mb-3">
                                <label for="ano" class="form-label">Ano</label>
                                <input type="number" class="form-control" id="ano" name="ano" required>
                            </div>
                            <div class="mb-3">
                                <label for="fonte" class="form-label">Fonte</label>
                                <input type="text" class="form-control" id="fonte" name="fonte" required>
                            </div>
                            <div class="mb-3">
                                <label for="link" class="form-label">Link</label>
                                <input type="url" class="form-control" id="link" name="link" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Cadastrar Livro</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-number"><?= $total_registros ?></div>
                    <div class="stat-label">Livros Disponíveis</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-number"><?= $autores_brasileiros ?></div>
                    <div class="stat-label">Autores Brasileiros</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-number"><?= $livros_seculo_xix ?></div>
                    <div class="stat-label">Século XIX</div>
                </div>
            </div>
        </div>

        <div class="filter-section">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Ordenar por:</label>
                    <select class="form-select">
                        <option>Mais Recentes</option>
                        <option>Mais Antigos</option>
                        <option>Título (A-Z)</option>
                        <option>Título (Z-A)</option>
                        <option>Mais Baixados</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Século:</label>
                    <select class="form-select">
                        <option>Todos</option>
                        <option>Século XIX</option>
                        <option>Século XX</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Formato:</label>
                    <select class="form-select">
                        <option>Todos</option>
                        <option>PDF</option>
                        <option>ePUB</option>
                        <option>Texto</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Fonte:</label>
                    <select class="form-select">
                        <option>Todas</option>
                        <option>Portal Domínio Público</option>
                        <option>Biblioteca Nacional</option>
                        <option>Projeto Gutenberg</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="page-content" id="livros-container">
            <?php
            $inicio = ($pagina - 1) * $livros_por_pagina;
            $livros_pagina = array_slice($livros, $inicio, $livros_por_pagina);

            $cores = [
                'linear-gradient(135deg, #6a89cc 0%, #4a69bd 100%)',
                'linear-gradient(135deg, #e55039 0%, #eb2f06 100%)',
                'linear-gradient(135deg, #1e3799 0%, #0c2461 100%)',
                'linear-gradient(135deg, #78e08f 0%, #38ada9 100%)',
                'linear-gradient(135deg, #f6b93b 0%, #e55039 100%)',
                'linear-gradient(135deg, #b71540 0%, #6a1b9a 100%)'
            ];

            if (count($livros_pagina) > 0):
                ?>
                <div class="row">
                    <?php foreach ($livros_pagina as $index => $livro):
                        $link_download = obter_link_download($livro['titulo']);
                        ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="dp-card card h-100">
                                <div class="position-relative">
                                    <div class="card-img-placeholder"
                                        style="background: <?= $cores[$index % count($cores)] ?>;">
                                        <div class="book-info">
                                            <div class="book-icon">
                                                <i class="fas fa-book"></i>
                                            </div>
                                            <h3><?= $livro['titulo'] ?></h3>
                                            <p><strong><?= $livro['autor'] ?></strong></p>
                                            <p><strong><?= $livro['ano'] ?></strong></p>
                                        </div>
                                    </div>
                                    <?php if (isset($livro['badge'])): ?>
                                        <div class="dp-badge"><?= $livro['badge'] ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?= $livro['titulo'] ?></h5>
                                    <p class="card-text"><strong>Autor:</strong> <?= $livro['autor'] ?></p>
                                    <p class="card-text"><strong>Ano:</strong> <?= $livro['ano'] ?></p>
                                    <p class="card-text book-description"><?= obter_descricao($livro['titulo']) ?></p>
                                    <div class="source-badge">Fonte: <?= $livro['fonte'] ?></div>
                                </div>
                                <div class="card-footer">
                                    <div class="d-grid gap-2">
                                        <?php if (strpos($livro['fonte'], 'Internet Archive') !== false): ?>
                                            <a href="<?= $livro['link'] ?>" class="btn btn-outline-secondary" target="_blank">
                                                <i class="fas fa-download me-2"></i>Ler Online
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= $livro['link'] ?>" class="btn btn-outline-secondary btn-download"
                                                target="_blank">
                                                <i class="fas fa-download me-2"></i>Baixar PDF
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>Nenhum livro encontrado para sua pesquisa.
                </div>
            <?php endif; ?>
        </div>

        <div class="pagination-container">
            <div class="page-info">
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

                <?php
                $inicio_pag = max(1, $pagina - 2);
                $fim_pag = min($total_paginas, $pagina + 2);

                if ($inicio_pag > 1) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }

                for ($i = $inicio_pag; $i <= $fim_pag; $i++):
                    ?>
                    <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $i ?>&pesquisa=<?= urlencode($termo_pesquisa) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($fim_pag < $total_paginas): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>

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
        // Adicionando efeito de transição entre páginas
        document.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function (e) {
                if (!this.parentElement.classList.contains('disabled') &&
                    !this.parentElement.classList.contains('active')) {

                    document.getElementById('livros-container').classList.add('fade-out');

                    setTimeout(() => {
                        window.location.href = this.href;
                    }, 300);
                }
            });
        });
        // Removido loader do botão Baixar PDF
    </script>
</body>

</html>