<?php
session_start();
require __DIR__ . '/conexao.php';

$cpf = trim($_GET['cpf'] ?? '');

if (!$cpf) {
    $_SESSION['flash_error'] = 'CPF não informado.';
    header('Location: consulta.php');
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM entrantes WHERE CPF = :cpf");
    $stmt->execute([':cpf' => $cpf]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['flash'] = 'Registro apagado com sucesso!';
    } else {
        $_SESSION['flash_error'] = 'Registro não encontrado.';
    }
} catch (Exception $e) {
    $_SESSION['flash_error'] = 'Erro ao apagar: ' . $e->getMessage();
}

header('Location: consulta.php');
exit;
