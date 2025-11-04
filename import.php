<?php

session_start();
require __DIR__ . '/conexao.php';

// Colunas suportadas
$ALLOWED = [
    'CPF','BENEFICIO','NOME','DDB','VALOR_BENEFICIO','DATA_NASCIMENTO','IDADE',
    'CODIGO_ESPECIE','CIDADE','UF','LEMIT1','LEMIT2','LEMIT3'
];

if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash_error'] = 'Falha ao enviar o arquivo.';
    header('Location: index.php');
    exit;
}

$ext = strtolower(pathinfo($_FILES['csv']['name'] ?? '', PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    $_SESSION['flash_error'] = 'Envie um arquivo .csv (planilha salva como CSV).';
    header('Location: index.php');
    exit;
}

$path = $_FILES['csv']['tmp_name'];
$f = fopen($path, 'r');
if (!$f) {
    $_SESSION['flash_error'] = 'Não foi possível ler o arquivo.';
    header('Location: index.php');
    exit;
}

// Detecta separador (padrão ;)
$firstLine = fgets($f);
rewind($f);
$delimiter = (substr_count($firstLine, ';') >= substr_count($firstLine, ',')) ? ';' : ',';

// Lê cabeçalho
$header = fgetcsv($f, 0, $delimiter);
if (!$header) {
    $_SESSION['flash_error'] = 'Cabeçalho CSV ausente.';
    header('Location: index.php');
    exit;
}

// Normaliza cabeçalho para mapear com ALLOWED
$header = array_map(fn($h) => strtoupper(trim($h)), $header);
$cols = array_values(array_intersect($header, $ALLOWED));

if (empty($cols)) {
    $_SESSION['flash_error'] = 'Nenhuma coluna reconhecida no CSV.';
    header('Location: index.php');
    exit;
}

// Monta UPSERT (requer índice único sobre CPF)
$placeholders = array_map(fn($c)=>":$c", $cols);
$updates = array_map(fn($c)=>"`$c`=VALUES(`$c`)", array_diff($cols, ['CPF'])); // evita sobrescrever CPF

$sql = "INSERT INTO entrantes (`".implode('`,`',$cols)."`) VALUES (".implode(',', $placeholders).")";
if ($updates) {
    $sql .= " ON DUPLICATE KEY UPDATE " . implode(', ', $updates);
}

$conn->beginTransaction();
$inserted = 0; $updated = 0; $skipped = 0;

try {
    $stmt = $conn->prepare($sql);

    // Processa linhas
    while (($row = fgetcsv($f, 0, $delimiter)) !== false) {
        if (!is_array($row) || count($row) < count($header)) { $skipped++; continue; }

        // Mapeia linha pelo cabeçalho original
        $map = [];
        foreach ($header as $i => $colName) {
            $map[$colName] = $row[$i] ?? null;
        }

        // Liga parâmetros das colunas reconhecidas
        foreach ($cols as $c) {
            $val = $map[$c] ?? null;
            $stmt->bindValue(":$c", $val);
        }

        $stmt->execute();
        // Em ON DUPLICATE KEY UPDATE, rowCount(): 1=insert, 2=update (MySQL PDO)
        $rc = $stmt->rowCount();
        if ($rc === 1) $inserted++;
        elseif ($rc === 2) $updated++;
        else $skipped++;
    }

    $conn->commit();
    fclose($f);

    $_SESSION['flash'] = "Importação concluída. Inseridos: {$inserted}, Atualizados: {$updated}, Ignorados: {$skipped}.";
    header('Location: index.php');
    exit;

} catch (Throwable $e) {
    $conn->rollBack();
    fclose($f);
    $_SESSION['flash_error'] = 'Erro ao importar: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}