<?php
session_start();
require __DIR__ . '/../app/funcoes.php';
include __DIR__ . '/../app/conexao.php';

$termo = $_GET['termo'] ?? '';

if (strlen($termo) > 2) {
    $stmt = $conn->prepare("
        SELECT CODIGO, NOME 
        FROM pm_usua 
        WHERE NOME LIKE ? 
        ORDER BY NOME 
        LIMIT 10
    ");
    $termoLike = "%$termo%";
    $stmt->bind_param("s", $termoLike);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<div class="search-item usuario-item" 
                  data-id="' . $row['CODIGO'] . '" 
                  data-nome="' . htmlspecialchars($row['NOME']) . '">
                  ' . htmlspecialchars($row['NOME']) . '
                  </div>';
        }
    } else {
        echo '<div class="search-item">Nenhum usuário encontrado</div>';
    }
} else {
    echo '<div class="search-item">Digite pelo menos 3 caracteres</div>';
}
?>
