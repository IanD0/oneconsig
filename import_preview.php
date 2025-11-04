<?php

session_start();
require __DIR__ . '/conexao.php';

$ALLOWED = [
  'CPF','BENEFICIO','NOME','DDB','VALOR_BENEFICIO','DATA_NASCIMENTO','IDADE',
  'CODIGO_ESPECIE','CIDADE','UF','LEMIT1','LEMIT2','LEMIT3','cartao_rcc'
];

if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
  $_SESSION['flash_error'] = 'Falha ao enviar o arquivo.';
  header('Location: index.php'); exit;
}

$ext = strtolower(pathinfo($_FILES['csv']['name'] ?? '', PATHINFO_EXTENSION));
if ($ext !== 'csv') {
  $_SESSION['flash_error'] = 'Envie um arquivo .csv';
  header('Location: index.php'); exit;
}

// salva o CSV em uploads/ para a etapa 2
$dir = __DIR__ . '/uploads';
if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
$token = bin2hex(random_bytes(16));
$stored = $dir . "/{$token}.csv";
if (!move_uploaded_file($_FILES['csv']['tmp_name'], $stored)) {
  $_SESSION['flash_error'] = 'Não foi possível salvar o arquivo.';
  header('Location: index.php'); exit;
}

// detecta delimitador
$f = fopen($stored, 'r');
$first = fgets($f);
rewind($f);
$delimiter = (substr_count($first, ';') >= substr_count($first, ',')) ? ';' : ',';

// lê cabeçalho e amostra
$header = fgetcsv($f, 0, $delimiter);
$header = $header ? array_map(fn($h)=>trim($h), $header) : [];
$preview = [];
for ($i=0; $i<10 && ($row = fgetcsv($f, 0, $delimiter)) !== false; $i++) {
  $preview[] = $row;
}
fclose($f);

// sugere mapeamento (case-insensitive)
$upperAllowed = array_flip(array_map('strtoupper', $ALLOWED));
$mapping = [];
foreach ($header as $col) {
  $key = strtoupper($col);
  $mapping[$col] = isset($upperAllowed[$key]) ? $ALLOWED[$upperAllowed[$key]] : '';
}

// guarda sessão para etapa 2
$_SESSION['import_token'] = [
  'token' => $token,
  'path' => $stored,
  'delimiter' => $delimiter,
  'header' => $header,
];

?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Pré-visualização do CSV</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="./consulta.css" rel="stylesheet">
  <style>
    body { background: #f0f0f0; padding: 20px; }
    .import-container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 4px; border: 1px solid #ddd; }
    h1 { color: #333; font-size: 22px; margin-bottom: 10px; }
    .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    .table th, .table td { padding: 10px; border: 1px solid #ddd; text-align: left; font-size: 13px; }
    .table th { background: #f5f5f5; font-weight: 600; }
    .overflow { overflow-x: auto; margin: 20px 0; }
    details { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 4px; }
    summary { cursor: pointer; font-weight: 600; color: #007bff; }
    .grid { display: grid; gap: 15px; margin-top: 15px; }
    .field label { display: block; font-size: 13px; color: #666; margin-bottom: 5px; }
    .field select, .field input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
    .actions { display: flex; gap: 10px; margin-top: 20px; }
    .btn { padding: 10px 20px; border-radius: 4px; border: 1px solid #ccc; background: #f5f5f5; color: #333; text-decoration: none; cursor: pointer; font-size: 14px; }
    .btn.primary { background: #007bff; color: white; border-color: #007bff; }
    .btn.primary:hover { background: #0056b3; }
    #overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: none; align-items: center; justify-content: center; z-index: 9999; }
    #overlay .box { background: white; padding: 30px; border-radius: 8px; text-align: center; min-width: 300px; }
    .progress { width: 100%; height: 8px; background: #e0e0e0; border-radius: 4px; overflow: hidden; margin-top: 15px; }
    .progress span { display: block; height: 100%; background: #007bff; transition: width 0.3s; }
  </style>
</head>
<body>
<div class="import-container">
  <h1>Pré-visualização e mapeamento (Etapa 1 → 2)</h1>
  <p class="muted">Arquivo salvo. Delimitador detectado: <strong><?php echo htmlspecialchars($delimiter); ?></strong></p>

  <form action="import_run.php" method="post" onsubmit="document.getElementById('overlay').style.display='flex'">
    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
    <input type="hidden" name="delimiter" value="<?php echo htmlspecialchars($delimiter); ?>">
    <details open>
      <summary>Mapeamento de colunas</summary>
      <div class="grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
        <?php foreach ($header as $i => $col): ?>
          <div class="field">
            <label>Coluna do arquivo: "<?php echo htmlspecialchars($col); ?>"</label>
            <select name="map[<?php echo $i; ?>]">
              <option value="">— Ignorar —</option>
              <?php foreach ($ALLOWED as $dbcol): ?>
                <option value="<?php echo $dbcol; ?>" <?php echo ($mapping[$col] === $dbcol ? 'selected' : ''); ?>>
                  <?php echo $dbcol; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endforeach; ?>
      </div>
    </details>

    <details>
      <summary>Opções</summary>
      <div class="grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
        <div class="field">
          <label>Ignorar primeira linha</label>
          <select name="skip_header">
            <option value="1" selected>Sim</option>
            <option value="0">Não</option>
          </select>
        </div>
        <div class="field">
          <label>Modo de importação</label>
          <select name="mode">
            <option value="fast" selected>Rápido (LOAD DATA REPLACE)</option>
            <option value="safe">Seguro (UPSERT em lotes)</option>
          </select>
        </div>
        <div class="field">
          <label>Tamanho do lote (modo seguro)</label>
          <input type="text" name="batch" value="500">
        </div>
      </div>
    </details>

    <h2>Amostra (10 linhas)</h2>
    <div class="overflow">
      <table class="table">
        <thead><tr><?php foreach ($header as $h) echo '<th>'.htmlspecialchars($h).'</th>'; ?></tr></thead>
        <tbody>
          <?php foreach ($preview as $r): ?>
            <tr><?php foreach ($r as $c) echo '<td>'.htmlspecialchars($c).'</td>'; ?></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="actions">
      <a class="btn" href="index.php">Cancelar</a>
      <button type="submit" class="btn primary">Iniciar importação (Etapa 2)</button>
    </div>
  </form>
</div>

  <div id="overlay">
    <div class="box">
      <div class="muted">Importando, aguarde...</div>
      <div class="progress"><span id="progbar" style="width: 100%"></span></div>
    </div>
  </div>
</body>
</html>