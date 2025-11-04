<?php
@set_time_limit(0);
require __DIR__ . '/conexao.php';

// Mesmas colunas do index (mantenha em sincronia)
$COLUMNS = [
    'CPF','BENEFICIO','NOME','DDB','MARGEM_35','VALOR_BENEFICIO','DATA_NASCIMENTO','IDADE',
    'CODIGO_ESPECIE','CIDADE','UF','LEMIT1','LEMIT2','LEMIT3','cartao_rcc'
];

// Filtros (idênticos ao index)
$cpf       = trim($_GET['cpf']       ?? '');
$nome      = trim($_GET['nome']      ?? '');
$cidade    = trim($_GET['cidade']    ?? '');
$uf        = trim($_GET['uf']        ?? '');
$beneficio = trim($_GET['beneficio'] ?? '');
$telefone  = trim($_GET['telefone']  ?? '');

$where = [];
$params = [];

if ($cpf !== '')    { $where[] = 'CPF LIKE :cpf';       $params[':cpf'] = "%{$cpf}%"; }
if ($nome !== '')   { $where[] = 'NOME LIKE :nome';     $params[':nome'] = "%{$nome}%"; }
if ($cidade !== '') { $where[] = 'CIDADE LIKE :cidade'; $params[':cidade'] = "%{$cidade}%"; }
if ($uf !== '')     { $where[] = 'UF LIKE :uf';         $params[':uf'] = "%{$uf}%"; }
if ($beneficio !== '') { $where[] = 'BENEFICIO LIKE :beneficio'; $params[':beneficio'] = "%{$beneficio}%"; }
if ($telefone !== '') {
    $where[] = '(LEMIT1 LIKE :tel1 OR LEMIT2 LIKE :tel2 OR LEMIT3 LIKE :tel3)';
    $params[':tel1'] = "%{$telefone}%";
    $params[':tel2'] = "%{$telefone}%";
    $params[':tel3'] = "%{$telefone}%";
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$selectCols = implode(',', array_map(fn($c)=>"`$c`", $COLUMNS));
$sql = "SELECT {$selectCols} FROM entrantes {$whereSql} ORDER BY NOME ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

// Cabeçalhos CSV
$filename = 'entrantes_export_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"{$filename}\"");

$out = fopen('php://output', 'w');

// BOM UTF-8 para Excel (opcional)
fprintf($out, "\xEF\xBB\xBF");

// Cabeçalho
fputcsv($out, $COLUMNS, ';');

// Linhas
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $line = [];
    foreach ($COLUMNS as $c) {
        $line[] = $row[$c] ?? '';
    }
    fputcsv($out, $line, ';');
}

fclose($out);
exit;