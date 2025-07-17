<?php
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isUser() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'usuario';
}

function isLoggedIn() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['usuario', 'admin']);
}

function calcularMulta($dataEmprestimo, $dataDevolucao) {
    $dataEmp = new DateTime($dataEmprestimo);
    $dataDev = new DateTime($dataDevolucao);
    $hoje = new DateTime();
    
    // Prazo padrão: 7 dias
    $prazoLimite = clone $dataEmp;
    $prazoLimite->add(new DateInterval('P7D'));
    
    if ($hoje > $prazoLimite) {
        $diasAtraso = $hoje->diff($prazoLimite)->days;
        return $diasAtraso * 2; // R$ 2,00 por dia de atraso
    }
    
    return 0;
}