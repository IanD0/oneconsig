<?php

session_start();
require __DIR__ . '/conexao.php';

$ALLOWED = [
  'CPF','BENEFICIO','NOME','DDB','VALOR_BENEFICIO','DATA_NASCIMENTO','IDADE',
  'CODIGO_ESPECIE','CIDADE','UF','LEMIT1','LEMIT2','LEMIT3','cartao_rcc'
];

if (empty($_POST['token']) || empty($_SESSION['import_token']['token']) ||
    $_POST['token'] !== $_SESSION['import_token']['token']) {
  $_SESSION['flash_error'] = 'Sessão de importação inválida.';
  header('Location: consulta.php'); exit;
}

$meta = $_SESSION['import_token'];
$path = $meta['path'];
$delimiter = $_POST['delimiter'] ?? $meta['delimiter'] ?? ';';
$skipHeader = (int)($_POST['skip_header'] ?? 1);
$mode = ($_POST['mode'] ?? 'fast') === 'safe' ? 'safe' : 'fast';
$batchSize = max(100, (int)($_POST['batch'] ?? 500)); // mínimo 100

// mapeamento: índice da coluna no arquivo -> coluna da tabela (ou vazio para ignorar)
$map = array_map('trim', $_POST['map'] ?? []);
if (!is_array($map) || !$map) {
  $_SESSION['flash_error'] = 'Mapeamento ausente.';
  header('Location: consulta.php'); exit;
}

// valida colunas mapeadas
$mappedCols = array_values(array_filter(array_unique(array_values($map))));
foreach ($mappedCols as $c) {
  if (!in_array($c, $ALLOWED, true)) {
    $_SESSION['flash_error'] = 'Coluna inválida no mapeamento: ' . htmlspecialchars($c);
    header('Location: consulta.php'); exit;
  }
}

// tenta modo rápido (LOAD DATA) com REPLACE (substitui duplicados pela PK/UK)
if ($mode === 'fast') {
  try {
    // constrói lista na ordem do arquivo, usando @lixo para ignorados
    $fileHeader = $meta['header'];
    $colsClause = [];
    $setClause = [];

    foreach ($fileHeader as $i => $colNameFromFile) {
      $dbcol = $map[$i] ?? '';
      if ($dbcol) {
        $colsClause[] = "`$dbcol`";
      } else {
        $var = '@ign' . $i;
        $colsClause[] = $var; // descarta
      }
    }

    // REPLACE INTO substitui registros conforme PK/UK (requer índice único, ex: UNIQUE(CPF))
    $ignore = $skipHeader ? "IGNORE 1 LINES" : "";
    $sql = sprintf(
      "LOAD DATA LOCAL INFILE %s REPLACE INTO TABLE `entrantes`
       CHARACTER SET utf8mb4
       FIELDS TERMINATED BY %s ENCLOSED BY '\"' ESCAPED BY '\"'
       LINES TERMINATED BY '\r\n'
       %s
       (%s)",
      $conn->quote($path),
      $conn->quote($delimiter),
      $ignore,
      implode(', ', $colsClause)
    );

    // aumenta limites de tempo
    @set_time_limit(0);
    $conn->exec($sql);

    $_SESSION['flash'] = 'Importação rápida concluída com LOAD DATA (REPLACE).';
    unset($_SESSION['import_token']);
    header('Location: consulta.php'); exit;

  } catch (Throwable $e) {
    // fallback silencioso para modo seguro
    $mode = 'safe';
  }
}

if ($mode === 'safe') {
  // UPSERT em lotes com prepared statements
  $f = fopen($path, 'r');
  if (!$f) {
    $_SESSION['flash_error'] = 'Não foi possível abrir o arquivo para leitura.';
    header('Location: consulta.php'); exit;
  }
  @set_time_limit(0);

  if ($skipHeader) { fgetcsv($f, 0, $delimiter); }

  // colunas destino na ordem do mapeamento do arquivo
  $destCols = [];
  foreach ($map as $i => $dbcol) {
    if ($dbcol) { $destCols[] = $dbcol; }
  }
  if (!$destCols) {
    fclose($f);
    $_SESSION['flash_error'] = 'Nenhuma coluna mapeada.';
    header('Location: consulta.php'); exit;
  }

  // monta SQL base para INSERT múltiplo
  $updates = array_filter($destCols, fn($c)=>$c !== 'CPF');
  $setUpd = $updates ? implode(', ', array_map(fn($c)=>"`$c`=VALUES(`$c`)", $updates)) : '';
  
  $inserted=0; $updated=0; $skipped=0; $count=0;
  $batch = [];

  try {
    $conn->beginTransaction();

    while (($row = fgetcsv($f, 0, $delimiter)) !== false) {
      $count++;

      // monta valores da linha
      $rowVals = [];
      foreach ($map as $i => $dbcol) {
        if (!$dbcol) continue;
        $val = $row[$i] ?? null;
        // limpa valores monetários
        if ($dbcol === 'VALOR_BENEFICIO' && $val) {
          $val = preg_replace('/[^0-9,.]/', '', $val);
          $val = str_replace(['.', ','], ['', '.'], $val);
        }
        $rowVals[] = $val;
      }
      $batch[] = $rowVals;

      // executa lote quando atingir o tamanho
      if (count($batch) >= $batchSize) {
        $placeholders = [];
        $values = [];
        foreach ($batch as $bRow) {
          $placeholders[] = '(' . implode(',', array_fill(0, count($destCols), '?')) . ')';
          $values = array_merge($values, $bRow);
        }
        $sql = "INSERT INTO `entrantes` (`".implode('`,`',$destCols)."`) VALUES ".implode(',', $placeholders);
        if ($setUpd) { $sql .= " ON DUPLICATE KEY UPDATE $setUpd"; }
        
        try {
          $stmt = $conn->prepare($sql);
          $stmt->execute($values);
          $inserted += $stmt->rowCount();
        } catch (Throwable $e) {
          $skipped += count($batch);
        }
        
        $batch = [];
        if ($conn->inTransaction()) $conn->commit();
        $conn->beginTransaction();
      }
    }

    // processa lote restante
    if ($batch) {
      $placeholders = [];
      $values = [];
      foreach ($batch as $bRow) {
        $placeholders[] = '(' . implode(',', array_fill(0, count($destCols), '?')) . ')';
        $values = array_merge($values, $bRow);
      }
      $sql = "INSERT INTO `entrantes` (`".implode('`,`',$destCols)."`) VALUES ".implode(',', $placeholders);
      if ($setUpd) { $sql .= " ON DUPLICATE KEY UPDATE $setUpd"; }
      
      try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($values);
        $inserted += $stmt->rowCount();
      } catch (Throwable $e) {
        $skipped += count($batch);
      }
    }

    if ($conn->inTransaction()) $conn->commit();
    fclose($f);
    $_SESSION['flash'] = "Importação segura concluída. Inseridos: $inserted, Atualizados: $updated, Ignorados: $skipped.";
    unset($_SESSION['import_token']);
    header('Location: consulta.php'); exit;

  } catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    if (is_resource($f)) fclose($f);
    $_SESSION['flash_error'] = 'Erro ao importar: ' . $e->getMessage();
    header('Location: consulta.php'); exit;
  }
}