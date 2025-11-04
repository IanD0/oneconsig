<?php

// Colunas do modelo (mantenha em sincronia com index/export/import)
$COLUMNS = [
  'CPF','BENEFICIO','NOME','DDB','VALOR_BENEFICIO','DATA_NASCIMENTO','IDADE',
  'CODIGO_ESPECIE','CIDADE','UF','LEMIT1','LEMIT2','LEMIT3','cartao_rcc'
];

$filename = 'modelo_entrantes_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"{$filename}\"");

// BOM para Excel
echo "\xEF\xBB\xBF";

// Cabeçalho usando ';' como separador
$out = fopen('php://output', 'w');
fputcsv($out, $COLUMNS, ';');
fclose($out);
exit;