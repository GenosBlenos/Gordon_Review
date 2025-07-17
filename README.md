# GORDON - Sistema de Biblioteca

Este projeto é um sistema web para gestão de bibliotecas, desenvolvido em PHP com MySQL, focado em usabilidade, responsividade e visual moderno. Ideal para controle de acervo, empréstimos, devoluções e administração de usuários.

## Funcionalidades
- **Login seguro** para administradores e usuários
- **Menu responsivo** com exibição do nome do usuário e botão de logout destacado
- **Controle de acervo**: cadastro, pesquisa e listagem de livros
- **Gestão de empréstimos e devoluções** com cálculo automático de multas
- **Cadastro e administração de usuários**
- **Dashboard** com cards de estatísticas e gráficos (Chart.js)
- **Visualização de livros populares e distribuição por tema**
- **Design responsivo**: experiência otimizada para desktop e mobile (mínimo 375x667px)
- **Acessibilidade** e navegação intuitiva

## Estrutura do Projeto

```
GORDON/
├── app/                            # Lógica de negócio e componentes compartilhados
│   ├── menu.php                    # Menu principal (responsivo, exibe nome, opções e logout)
│   ├── conexao.php                 # Conexão com o banco de dados MySQL
│   ├── funcoes.php                 # Funções utilitárias PHP
│   ├── acesso_negado.php           # Página de acesso negado
├── api/
│   ├── categorias-chart.php        # Dados para gráfico de temas
│   ├── pesquisar-livro.php         # Busca dinâmica de livros
│   └── pesquisar-usuario.php       # Busca dinâmica de usuários
├── public/
│   ├── acervo.php                  # Listagem e pesquisa de livros
│   ├── cadastro_usuario.php        # Cadastro de novos usuários
│   ├── cadastro-admin.php          # Cadastro de administradores
│   ├── comprovante_multa.php       # Controle de multas e verificação de comprovantes
│   ├── devolucao.php               # Controle de devoluções
│   ├── dominio_publico.php         # Listagens de livros digitais de dominio público para download
│   ├── emprestimo.php              # Controle de empréstimos
│   ├── home.php                    # Dashboard com cards e gráficos
│   ├── usuario.php                 # Gestão de usuários
│   ├── livros.php                  # Cadastro de livros
│   ├── login.php                   # Tela de login
│   ├── logout.php                  # Logout do sistema
├── styles/
│   └── sense.css                   # CSS principal, responsivo e customizado
├── script/
│   └── script.js                   # Scripts JS para interações do menu e busca
├── src/                            # Imagens e assets
│   ├── gordon.jpg                  # Logo principal
│   ├── barra.png                   # Imagem decorativa
│   ├── barra2.png                  # Imagem decorativa
│   └── salto.png                   # Imagem decorativa
├── db.sql                          # Estrutura e dados iniciais do banco de dados
├── httpd-vhosts.conf               # (Opcional) Configuração de virtual host para Apache
└── README.txt                      # Este arquivo de documentação
```

- **app/**: Código PHP compartilhado, lógica de negócio, conexão e menu.
- **api/**: Endpoints para AJAX, busca dinâmica e gráficos (JSON).
- **public/**: Todas as páginas acessíveis pelo usuário/admin (interface).
- **styles/**: CSS customizado e responsivo para todo o sistema.
- **script/**: JavaScript para interações dinâmicas (menu, busca, etc).
- **src/**: Imagens, logo e assets visuais.
- **db.sql**: Estrutura do banco de dados MySQL.
- **README.txt**: Documentação do projeto.

## Instalação e Uso
1. **Clone o repositório:**
   ```
   git clone https://github.com/GenosBlenos/Gordon_Biblioteca.git
   ```
2. **Configure o ambiente:**
   - PHP 7.4+
   - MySQL 5.7+
   - Servidor Apache (WAMP, XAMPP, etc.)
3. **Importe o banco de dados:**
   - Importe o arquivo `db.sql` no seu MySQL
4. **Configure a conexão:**
   - Edite `app/conexao.php` com os dados do seu banco
5. **Acesse o sistema:**
   - Via navegador: `http://localhost/GORDON/public/login.php`

## Responsividade
- O sistema é totalmente responsivo, adaptando-se a telas de 375x667px ou maiores.
- Menu, cards e gráficos otimizados para mobile.

## Créditos e Licença
- Desenvolvido por Matheus de Oliveira Zinna Di'Mauro
- Ícones: [Bootstrap Icons](https://icons.getbootstrap.com/)
- Gráficos: [Chart.js](https://www.chartjs.org/)
- Licença: MIT
