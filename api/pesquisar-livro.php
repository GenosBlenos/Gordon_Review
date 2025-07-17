<?php
session_start();
require __DIR__ . '/../app/funcoes.php';
include __DIR__ . '/../app/conexao.php';

$termo = $_GET['termo'] ?? '';

if (strlen($termo) > 2) {
    $stmt = $conn->prepare("
        SELECT CODIGO, TITULO 
        FROM pm_acerv 
        WHERE (TITULO LIKE ? OR AUTOR LIKE ? OR CODIGO = ? OR ISBN LIKE ?)
        AND (EMPRESTIMO = 'N' OR EMPRESTIMO = 0 OR EMPRESTIMO IS NULL)
        ORDER BY TITULO 
        LIMIT 10
    ");
    $termoLike = "%$termo%";
    $codigo = is_numeric($termo) ? (int)$termo : 0;
    $stmt->bind_param("ssis", $termoLike, $termoLike, $codigo, $termoLike);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<div class="search-item livro-item" 
                  data-id="' . $row['CODIGO'] . '" 
                  data-titulo="' . htmlspecialchars($row['TITULO']) . '">
                  ' . htmlspecialchars($row['TITULO']) . ' <span class="text-muted">[' . $row['CODIGO'] . ']</span>
                  </div>';
        }
    } else {
        echo '<div class="search-item">Nenhum livro disponível encontrado</div>';
    }
} else {
    echo '<div class="search-item">Digite pelo menos 3 caracteres</div>';
}
