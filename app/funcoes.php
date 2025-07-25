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
    try {
        $dataEmp = new DateTime($dataEmprestimo);
        $dataDev = new DateTime($dataDevolucao);

        $prazoLimite = clone $dataEmp;
        $prazoLimite->add(new DateInterval('P7D'));

        if ($dataDev > $prazoLimite) {
            $diasAtraso = $dataDev->diff($prazoLimite)->days;
            return $diasAtraso * 2;
        }
    } catch (Exception $e) {
        error_log("Erro ao calcular multa: " . $e->getMessage());
    }
    return 0;
}
