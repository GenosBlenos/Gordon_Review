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
function obter_link_download($titulo) {
    // Gera um nome de arquivo seguro e fictício para o exemplo
    $arquivo = preg_replace('/[^a-zA-Z0-9-_]/', '_', strtolower($titulo)) . '.pdf';
    return "downloads/" . $arquivo;
}

// Função para obter uma descrição fictícia do livro
function obter_descricao($titulo) {
    // Você pode personalizar as descrições conforme necessário
    return "Descrição não disponível para o livro \"{$titulo}\".";
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
        'link' => 'https://www.brasiliana.usp.br/bbd/handle/1918/00405500'
    ],
    [
        'titulo' => 'O Cortiço',
        'autor' => 'Aluísio Azevedo',
        'ano' => 1890,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://www.brasiliana.usp.br/bbd/handle/1918/00406000'
    ],
    [
        'titulo' => 'Memórias Póstumas de Brás Cubas',
        'autor' => 'Machado de Assis',
        'ano' => 1881,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://www.brasiliana.usp.br/bbd/handle/1918/00405400'
    ],
    [
        'titulo' => 'O Alienista',
        'autor' => 'Machado de Assis',
        'ano' => 1882,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://www.brasiliana.usp.br/bbd/handle/1918/00405300'
    ],
    [
        'titulo' => 'Iracema',
        'autor' => 'José de Alencar',
        'ano' => 1865,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://www.brasiliana.usp.br/bbd/handle/1918/00405100'
    ],
    [
        'titulo' => 'O Guarani',
        'autor' => 'José de Alencar',
        'ano' => 1857,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://www.brasiliana.usp.br/bbd/handle/1918/00405000'
    ],
    [
        'titulo' => 'A Moreninha',
        'autor' => 'Joaquim Manuel de Macedo',
        'ano' => 1844,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://www.brasiliana.usp.br/bbd/handle/1918/00404900'
    ],
    [
        'titulo' => 'O Ateneu',
        'autor' => 'Raul Pompeia',
        'ano' => 1888,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://www.brasiliana.usp.br/bbd/handle/1918/00404800'
    ],
    [
        'titulo' => 'Os Sertões',
        'autor' => 'Euclides da Cunha',
        'ano' => 1902,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://www.brasiliana.usp.br/bbd/handle/1918/00404700'
    ],
    [
        'titulo' => 'Triste Fim de Policarpo Quaresma',
        'autor' => 'Lima Barreto',
        'ano' => 1915,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://www.brasiliana.usp.br/bbd/handle/1918/00404600'
    ],
    // Adicionais nacionais
    [
        'titulo' => 'Senhora',
        'autor' => 'José de Alencar',
        'ano' => 1875,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://www.brasiliana.usp.br/bbd/handle/1918/00405200'
    ],
    [
        'titulo' => 'O Primo Basílio',
        'autor' => 'Eça de Queirós',
        'ano' => 1878,
        'fonte' => 'Domínio Público Portugal',
        'link' => 'https://www.dominiopublico.pt/download/ficheiros/201003181048-O_Primo_Basilio.pdf'
    ],
    // Internacionais
    [
        'titulo' => 'Dom Quixote',
        'autor' => 'Miguel de Cervantes',
        'ano' => 1605,
        'fonte' => 'Projeto Gutenberg (espanhol)',
        'link' => 'https://www.gutenberg.org/ebooks/2000.pdf.utf-8'
    ],
    [
        'titulo' => 'Orgulho e Preconceito',
        'autor' => 'Jane Austen',
        'ano' => 1813,
        'fonte' => 'Projeto Gutenberg (inglês)',
        'link' => 'https://www.gutenberg.org/files/1342/1342-pdf.pdf'
    ],
    [
        'titulo' => 'Aventuras de Sherlock Holmes',
        'autor' => 'Arthur Conan Doyle',
        'ano' => 1892,
        'fonte' => 'Projeto Gutenberg (inglês)',
        'link' => 'https://www.gutenberg.org/files/1661/1661-pdf.pdf'
    ],
    [
        'titulo' => 'O Pequeno Príncipe',
        'autor' => 'Antoine de Saint-Exupéry',
        'ano' => 1943,
        'fonte' => 'Domínio Público (francês)',
        'link' => 'https://www.planetebook.com/free-ebooks/the-little-prince.pdf'
    ],
    [
        'titulo' => 'O Conde de Monte Cristo',
        'autor' => 'Alexandre Dumas',
        'ano' => 1844,
        'fonte' => 'Projeto Gutenberg (inglês)',
        'link' => 'https://www.gutenberg.org/files/1184/1184-pdf.pdf'
    ],
    [
        'titulo' => 'Os Miseráveis',
        'autor' => 'Victor Hugo',
        'ano' => 1862,
        'fonte' => 'Projeto Gutenberg (inglês)',
        'link' => 'https://www.gutenberg.org/files/135/135-pdf.pdf'
    ],
    [
        'titulo' => 'A Divina Comédia',
        'autor' => 'Dante Alighieri',
        'ano' => 1320,
        'fonte' => 'Projeto Gutenberg (inglês)',
        'link' => 'https://www.gutenberg.org/files/8800/8800-pdf.pdf'
    ],
    [
        'titulo' => 'A Metamorfose',
        'autor' => 'Franz Kafka',
        'ano' => 1915,
        'fonte' => 'Projeto Gutenberg (inglês)',
        'link' => 'https://www.gutenberg.org/files/5200/5200-pdf.pdf'
    ],
    [
        'titulo' => 'O Retrato de Dorian Gray',
        'autor' => 'Oscar Wilde',
        'ano' => 1890,
        'fonte' => 'Projeto Gutenberg (inglês)',
        'link' => 'https://www.gutenberg.org/files/174/174-pdf.pdf'
    ],
    [
        'titulo' => 'Moby Dick',
        'autor' => 'Herman Melville',
        'ano' => 1851,
        'fonte' => 'Projeto Gutenberg (inglês)',
        'link' => 'https://www.gutenberg.org/files/2701/2701-pdf.pdf'
    ],
    [
        'titulo' => 'O Livro do Desassossego',
        'autor' => 'Fernando Pessoa',
        'ano' => 1935,
        'fonte' => 'Domínio Público Portugal',
        'link' => 'https://www.dominiopublico.pt/download/ficheiros/201003181048-O_Livro_do_Desassossego.pdf'
    ],
    [
        'titulo' => 'A Moreninha',
        'autor' => 'Joaquim Manuel de Macedo',
        'ano' => 1844,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://www.brasiliana.usp.br/bbd/handle/1918/00404900'
    ],
    [
        'titulo' => 'O Seminarista',
        'autor' => 'Bernardo Guimarães',
        'ano' => 1872,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://www.brasiliana.usp.br/bbd/handle/1918/00404500'
    ],
    [
        'titulo' => 'Lucíola',
        'autor' => 'José de Alencar',
        'ano' => 1862,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://www.brasiliana.usp.br/bbd/handle/1918/00404400'
    ],
    [
        'titulo' => 'O Mulato',
        'autor' => 'Aluísio Azevedo',
        'ano' => 1881,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://www.brasiliana.usp.br/bbd/handle/1918/00404300'
    ],
    [
        'titulo' => 'Senhora',
        'autor' => 'José de Alencar',
        'ano' => 1875,
        'fonte' => 'Brasiliana USP',
        'link' => 'https://www.brasiliana.usp.br/bbd/handle/1918/00405200'
    ],
    [
        'titulo' => 'O Primo Basílio',
        'autor' => 'Eça de Queirós',
        'ano' => 1878,
        'fonte' => 'Domínio Público Portugal',
        'link' => 'https://www.dominiopublico.pt/download/ficheiros/201003181048-O_Primo_Basilio.pdf'
    ],
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
            <div class="col-md-9">
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
            <div class="col-md-3 text-end">
                <div class="d-grid">
                    <button class="btn btn-success btn-atualizar"><i class="fas fa-sync-alt me-2"></i>Atualizar Acervo</button>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= $total_registros ?></div>
                    <div class="stat-label">Livros Disponíveis</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= $autores_brasileiros ?></div>
                    <div class="stat-label">Autores Brasileiros</div>
                </div>
            </div>
            <div class="col-md-3">
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
                            <div class="card-img-placeholder" style="background: <?= $cores[$index % count($cores)] ?>;">
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
                                <a href="<?= $livro['link'] ?>" class="btn btn-primary btn-download" 
                                   data-title="<?= $livro['titulo'] ?>" download target="_blank">
                                    <i class="fas fa-download me-2"></i>Baixar PDF
                                </a>
                                <a href="#" class="btn btn-outline-secondary">
                                    <i class="fas fa-book-open me-2"></i>Ler Online
                                </a>
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
                        <a class="page-link" href="?pagina=<?= $total_paginas ?>&pesquisa=<?= urlencode($termo_pesquisa) ?>">
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

        <div class="sources-section">
            <h3 class="mb-4 text-center"><i class="fas fa-database me-2"></i>Fontes Confiáveis</h3>
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <img src="https://www.dominiopublico.gov.br/imagens/logo_dp.png" alt="Domínio Público" class="source-logo">
                    <h5>Portal Domínio Público</h5>
                    <p class="small">Biblioteca digital do MEC com mais de 180 mil obras</p>
                </div>
                <div class="col-md-4 mb-4">
                    <img src="https://www.bn.gov.br/sites/default/files/logo_bn.png" alt="Biblioteca Nacional" class="source-logo">
                    <h5>Biblioteca Nacional</h5>
                    <p class="small">Principal repositório do patrimônio bibliográfico brasileiro</p>
                </div>
                <div class="col-md-4 mb-4">
                    <img src="https://www.gutenberg.org/gutenberg/pg-logo-129x80.png" alt="Projeto Gutenberg" class="source-logo">
                    <h5>Projeto Gutenberg</h5>
                    <p class="small">Primeira biblioteca digital do mundo com mais de 60 mil livros</p>
                </div>
            </div>
        </div>
    </div>

    <div class="download-progress" id="downloadProgress">
        <div class="d-flex justify-content-between">
            <span id="progressTitle">Preparando download...</span>
            <span id="progressPercent">0%</span>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Adicionando efeito de transição entre páginas
        document.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                if (!this.parentElement.classList.contains('disabled') && 
                    !this.parentElement.classList.contains('active')) {
                    
                    document.getElementById('livros-container').classList.add('fade-out');
                    
                    setTimeout(() => {
                        window.location.href = this.href;
                    }, 300);
                }
            });
        });

        // Simulação de download com feedback visual
        document.querySelectorAll('.btn-download').forEach(button => {
            button.addEventListener('click', function(e) {
                const bookTitle = this.getAttribute('data-title');
                const progressBar = document.getElementById('downloadProgress');
                const progressFill = document.getElementById('progressFill');
                const progressTitle = document.getElementById('progressTitle');
                const progressPercent = document.getElementById('progressPercent');
                
                // Exibir o indicador de progresso
                progressBar.style.display = 'block';
                progressTitle.textContent = `Baixando: ${bookTitle}.pdf`;
                
                // Simular progresso de download
                let progress = 0;
                const interval = setInterval(() => {
                    progress += Math.floor(Math.random() * 10) + 5;
                    if (progress >= 100) {
                        progress = 100;
                        clearInterval(interval);
                        
                        // Redirecionar para o link de download real após simulação
                        setTimeout(() => {
                            window.location.href = this.href;
                        }, 500);
                    }
                    
                    progressFill.style.width = `${progress}%`;
                    progressPercent.textContent = `${progress}%`;
                }, 200);
                
                // Impedir o comportamento padrão do link durante a simulação
                e.preventDefault();
            });
        });
    </script>
</body>
</html>