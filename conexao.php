<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Carrega variáveis do .env
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    die('Arquivo .env não encontrado em: ' . $envFile);
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$env = [];
foreach ($lines as $line) {
    if (strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
}

$host = $env['DB_HOST'];
$dbname = $env['DB_NAME'];
$username = $env['DB_USER'];
$password = $env['DB_PASS'];

try {
    $conn = new PDO(
        "mysql:host=$host;port={$env['DB_PORT']};dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            // habilita LOAD DATA LOCAL INFILE
            PDO::MYSQL_ATTR_LOCAL_INFILE => true,
        ]
    );

    $pdo = $conn;
} catch (PDOException $e) {
    die("Erro na conexão ao banco.");
}
