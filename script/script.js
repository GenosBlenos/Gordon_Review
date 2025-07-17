document.querySelector('.menu-toggle').addEventListener('click', function() {
    const menu = document.querySelector('.menu');
    const isExpanded = this.getAttribute('aria-expanded') === 'true';
    
    // Alterna o menu
    menu.classList.toggle('active');
    
    // Atualiza atributo ARIA
    this.setAttribute('aria-expanded', !isExpanded);
});

// --- Autocomplete usuário: limpa campo hidden ao digitar ou usar autocomplete do navegador ---
document.addEventListener('DOMContentLoaded', function () {
    const usuarioInput = document.getElementById('usuario');
    const usuarioCodigo = document.getElementById('usuario_codigo');
    if (usuarioInput && usuarioCodigo) {
        usuarioInput.addEventListener('input', function() {
            usuarioCodigo.value = '';
        });
    }
    
    // --- Autocomplete livro: limpa campo hidden ao digitar ou usar autocomplete do navegador ---
    const livroInput = document.getElementById('busca_livro');
    const livroCodigo = document.getElementById('livro_id');
    if (livroInput && livroCodigo) {
        livroInput.addEventListener('input', function() {
            livroCodigo.value = '';
        });
    }
});
