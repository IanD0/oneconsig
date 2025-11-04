<?php
require __DIR__ . '/conexao.php';

$stmt = $conn->query('SELECT DATA_NASCIMENTO, CARTAO_RCC, IDADE FROM entrantes LIMIT 5');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<pre>';
foreach ($rows as $row) {
    echo 'DATA_NASCIMENTO: ' . var_export($row['DATA_NASCIMENTO'], true) . "\n";
    echo 'CARTAO_RCC: ' . var_export($row['CARTAO_RCC'], true) . "\n";
    echo 'IDADE: ' . var_export($row['IDADE'], true) . "\n";
    echo "---\n";
}
echo '</pre>';
