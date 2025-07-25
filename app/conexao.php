<?php
$host = 'localhost';
$db   = 'biblioteca';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// Tenta carregar configs do .env.php se existir
$env_path = __DIR__ . '/.env.php';
if (file_exists($env_path)) {
    $env = include $env_path;
    $host = $env['host'] ?? $host;
    $db = $env['db'] ?? $db;
    $user = $env['user'] ?? $user;
    $pass = $env['pass'] ?? $pass;
    $charset = $env['charset'] ?? $charset;
}

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    error_log("Falha na conexão: " . $conn->connect_error);
    die("Erro ao conectar com o banco de dados.");
}

// Configurações ESSENCIAIS
$conn->set_charset($charset);
$conn->query("SET NAMES 'utf8mb4'");
$conn->query("SET CHARACTER SET utf8mb4");
$conn->query("SET SESSION collation_connection = 'utf8mb4_unicode_ci'");

// Habilitar relatório de erros detalhados
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Depuração
error_log("Conexão bem-sucedida com o banco: $db");
error_log("Charset da conexão: " . $conn->character_set_name());
?>
