<div class="desktop-menu" style="background-color: #072a3a; width: 100vw; min-width: 0; overflow-x: auto;">
    <nav class="navbar navbar-expand-lg" style="padding: 0;">
        <div class="w-100 d-flex align-items-center justify-content-between flex-wrap" style="min-height: 56px;">
            <!-- Hamburguer para mobile -->
            <button class="btn btn-link text-white mobile-hamburger" type="button" style="font-size:2rem;display:none;" id="openMobileMenu" aria-label="Abrir menu">
                <i class="bi bi-list"></i>
            </button>
            <!-- Usuário à esquerda -->
            <div class="d-flex align-items-center ms-3" style="min-width:0;">
                <?php if (isset($_SESSION['nome'])): ?>
                    <span class="d-flex align-items-center bg-white bg-opacity-10 rounded-pill px-3 py-1 me-2" style="font-size: 0.7em; color: #fff; font-weight: 400; min-width:0;">
                        <i class="bi bi-person-fill me-1" style="font-size: 1em;"></i>
                        <span style="text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.95em; white-space: nowrap;">
                            <?php
                            $nome_bruto = trim($_SESSION['nome']);
                            if (strpos($nome_bruto, ',') !== false) {
                                $partes = explode(',', $nome_bruto, 2);
                                $nome = trim($partes[1]);
                            } else {
                                $nome = $nome_bruto;
                            }
                            $primeiro_nome = explode(' ', $nome)[0];
                            echo htmlspecialchars($primeiro_nome);
                            ?>
                        </span>
                    </span>
                <?php endif; ?>
                <!-- Opções do menu -->
                <ul class="navbar-nav flex-row ms-2" style="flex-wrap: wrap; gap: 0.25rem;">
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'usuario'): ?>
                        <li class="nav-item"><a class="nav-link px-2" style="color: #fff" href="acervo.php?disponivel=1">Acervo Disponível</a></li>
                        <li class="nav-item"><a class="nav-link px-2" style="color: #fff" href="dominio_publico.php">Domínio Público</a></li>
                        <li class="nav-item"><a class="nav-link px-2" style="color: #fff" href="extrato_multas.php">Meus Comprovantes</a></li>
                    <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link px-2" style="color: #fff" href="acervo.php">Acervo</a></li>
                        <li class="nav-item"><a class="nav-link px-2" style="color: #fff" href="dominio_publico.php">Dominio Público</a></li>
                        <li class="nav-item"><a class="nav-link px-2" style="color: #fff" href="emprestimo.php">Empréstimo</a></li>
                        <li class="nav-item"><a class="nav-link px-2" style="color: #fff" href="devolucao.php">Devolução</a></li>
                        <li class="nav-item"><a class="nav-link px-2" style="color: #fff" href="livros.php">Cadastro Livro</a></li>
                        <li class="nav-item"><a class="nav-link px-2" style="color: #fff" href="usuario.php">Usuários</a></li>
                        <li class="nav-item"><a class="nav-link px-2" style="color: #fff" href="extrato_multas.php">Multas</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <!-- Logo centralizada -->
            <a class="navbar-brand mx-auto position-absolute start-50 translate-middle-x" href="home.php" style="z-index: 1;">
                <img src="../src/gordon.jpg" alt="Gordon Logo" style="height: 32px; width: auto; max-width:60vw;">
            </a>
            <!-- Sair à direita -->
            <ul class="navbar-nav ms-auto me-3">
                <li class="nav-item"><a class="nav-link" style="color: #fff; font-weight: bold;" href="logout.php">Sair</a></li>
            </ul>
        </div>
    </nav>
</div>
<!-- Mobile menu overlay e drawer -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
<div class="mobile-menu" id="mobileMenu">
    <button class="btn btn-link text-white mb-4" id="closeMobileMenu" style="font-size:2rem;">
        <i class="bi bi-x-lg"></i>
    </button>
    <div class="mb-4">
        <?php if (isset($_SESSION['nome'])): ?>
            <span class="d-flex align-items-center bg-white bg-opacity-10 rounded-pill px-3 py-1" style="font-size: 1em; color: #fff; font-weight: 400;">
                <i class="bi bi-person-fill me-1" style="font-size: 1.1em;"></i>
                <span style="text-transform: uppercase; letter-spacing: 0.5px; font-size: 1em; white-space: nowrap;">
                    <?php
                    $nome_bruto = trim($_SESSION['nome']);
                    if (strpos($nome_bruto, ',') !== false) {
                        $partes = explode(',', $nome_bruto, 2);
                        $nome = trim($partes[1]);
                    } else {
                        $nome = $nome_bruto;
                    }
                    $primeiro_nome = explode(' ', $nome)[0];
                    echo htmlspecialchars($primeiro_nome);
                    ?>
                </span>
            </span>
        <?php endif; ?>
    </div>
    <ul class="navbar-nav flex-column">
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'usuario'): ?>
            <li class="nav-item"><a class="nav-link" href="acervo.php?disponivel=1">Acervo Disponível</a></li>
            <li class="nav-item"><a class="nav-link" href="dominio_publico.php">Domínio Público</a></li>
            <li class="nav-item"><a class="nav-link" href="extrato_multas.php">Meus Comprovantes</a></li>
        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li class="nav-item"><a class="nav-link" href="acervo.php">Acervo</a></li>
            <li class="nav-item"><a class="nav-link" href="dominio_publico.php">Dominio Público</a></li>
            <li class="nav-item"><a class="nav-link" href="emprestimo.php">Empréstimo</a></li>
            <li class="nav-item"><a class="nav-link" href="devolucao.php">Devolução</a></li>
            <li class="nav-item"><a class="nav-link" href="livros.php">Cadastro Livro</a></li>
            <li class="nav-item"><a class="nav-link" href="usuario.php">Usuários</a></li>
            <li class="nav-item"><a class="nav-link" href="extrato_multas.php">Multas</a></li>
        <?php endif; ?>
        <li class="nav-item mt-3"><a class="nav-link text-danger fw-bold" href="logout.php">Sair</a></li>
    </ul>
</div>
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<style>
.desktop-menu .navbar-nav.flex-row {
    display: none !important;
}
.mobile-hamburger {
    display: block !important;
}
@media (max-width: 991.98px) {
    .desktop-menu .navbar-nav.flex-row {
        display: none !important;
    }
    .mobile-hamburger {
        display: block !important;
    }
}
@media (min-width: 992px) {
    .desktop-menu .navbar-nav.flex-row {
        display: none !important;
    }
    .mobile-hamburger {
        display: block !important;
    }
}
.mobile-menu-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1050;
    display: none;
}
.mobile-menu {
    position: fixed;
    top: 0; left: 0;
    width: 75vw;
    max-width: 320px;
    height: 100vh;
    background: #072a3a;
    z-index: 1100;
    transform: translateX(-100%);
    transition: transform 0.3s;
    padding: 2rem 1.5rem 1.5rem 1.5rem;
    box-shadow: 2px 0 8px rgba(0,0,0,0.15);
}
.mobile-menu.open {
    transform: translateX(0);
}
.mobile-menu .nav-link {
    color: #fff;
    font-size: 1.1em;
    margin-bottom: 1rem;
    display: block;
}
.mobile-menu .nav-link.active {
    font-weight: bold;
    color: #0dcaf0;
}
</style>
<script>
// Hamburguer menu mobile
const openBtn = document.getElementById('openMobileMenu');
const closeBtn = document.getElementById('closeMobileMenu');
const mobileMenu = document.getElementById('mobileMenu');
const overlay = document.getElementById('mobileMenuOverlay');

function openMenu() {
    mobileMenu.classList.add('open');
    overlay.style.display = 'block';
    document.body.style.overflow = 'hidden';
}
function closeMenu() {
    mobileMenu.classList.remove('open');
    overlay.style.display = 'none';
    document.body.style.overflow = '';
}
openBtn && openBtn.addEventListener('click', openMenu);
closeBtn && closeBtn.addEventListener('click', closeMenu);
overlay && overlay.addEventListener('click', closeMenu);
// Fecha menu ao navegar
mobileMenu.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', closeMenu);
});
</script>