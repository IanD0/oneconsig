<?php

session_start();
require __DIR__ . '/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Método não permitido';
    exit;
}

if (empty($_POST['csrf']) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
    http_response_code(403);
    $_SESSION['flash_error'] = 'CSRF inválido.';
    header('Location: index.php');
    exit;
}

try {
    // Use TRUNCATE para ser mais rápido (sem FKs na tabela)
    $conn->exec("TRUNCATE TABLE entrantes");
    $_SESSION['flash'] = 'Todos os registros foram apagados.';
} catch (Throwable $e) {
    $_SESSION['flash_error'] = 'Falha ao apagar registros: ' . $e->getMessage();
}

header('Location: index.php');
exit;