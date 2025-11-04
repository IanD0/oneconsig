<?php
require __DIR__ . '/conexao.php';

// Busca o mesmo registro que você está consultando
$beneficio = '39390659515'; // Coloque o benefício que você está testando

$stmt = $conn->prepare("SELECT * FROM entrantes WHERE CPF = :cpf LIMIT 1");
$stmt->execute([':cpf' => $beneficio]);
$resultado = $stmt->fetch(PDO::FETCH_ASSOC);

echo '<pre>';
echo "Todos os campos do registro:\n\n";
print_r($resultado);
echo "\n\nCampos específicos:\n";
echo "MARGEM_35: [" . ($resultado['MARGEM_35'] ?? 'NULL') . "]\n";
echo "CARTAO_RCC: [" . ($resultado['CARTAO_RCC'] ?? 'NULL') . "]\n";
echo "IDADE: [" . ($resultado['IDADE'] ?? 'NULL') . "]\n";
echo '</pre>';
