<?php
// acesso_negado.php
session_start();
include __DIR__ . '/../app/menu.php';
?>

<div class="container mt-5">
    <div class="alert alert-danger text-center">
        <h2>🔒 Acesso Negado</h2>
        <p>Você não tem permissão para acessar esta página</p>
        <a href="home.php" class="btn btn-lg btn-primary mt-3">
            <span style="font-size:1.2em;">🏠</span> Voltar à Página Inicial
        </a>
    </div>
</div>