<?php
session_start();
require __DIR__ . '/../app/conexao.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Query corrigida para usar sua estrutura real (tabela pm_acerv e coluna TEMA)
    $query = "
        SELECT 
            TEMA AS categoria,
            COUNT(*) AS total
        FROM pm_acerv
        WHERE TEMA IS NOT NULL AND TEMA != ''
        GROUP BY TEMA
        ORDER BY total DESC
        LIMIT 15
    ";

    $result = $conn->query($query);
    $colors = [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#6c757d',
        '#3a3b45', '#5de9d9', '#17a673', '#2c9faf'
    ];
    $data = ['labels' => [], 'data' => [], 'colors' => []];
    $colorIndex = 0;

    while ($row = $result->fetch_assoc()) {
        $data['labels'][] = $row['categoria'];
        $data['data'][] = (int)$row['total'];
        $data['colors'][] = $colors[$colorIndex % count($colors)];
        $colorIndex++;
    }

    // Forçar retorno mesmo se vazio
    if(empty($data['labels'])) {
        $data = [
            'labels' => ['Sem dados'],
            'data' => [1],
            'colors' => ['#ff6699']
        ];
    }

    echo json_encode($data);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}