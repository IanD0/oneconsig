<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/conexao.php';

$resultado = null;
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $beneficio = trim($_POST['beneficio'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    
    $where = [];
    $params = [];
    
    if ($nome) {
        $where[] = 'NOME LIKE :nome';
        $params[':nome'] = "%{$nome}%";
    }
    if ($cpf) {
        $where[] = 'CPF = :cpf';
        $params[':cpf'] = $cpf;
    }
    if ($beneficio) {
        $where[] = 'BENEFICIO = :beneficio';
        $params[':beneficio'] = $beneficio;
    }
    if ($telefone) {
        $where[] = '(LEMIT1 LIKE :tel OR LEMIT2 LIKE :tel2 OR LEMIT3 LIKE :tel3)';
        $params[':tel'] = "%{$telefone}%";
        $params[':tel2'] = "%{$telefone}%";
        $params[':tel3'] = "%{$telefone}%";
    }
    
    if ($where) {
        $whereSql = 'WHERE ' . implode(' AND ', $where);
        $stmt = $conn->prepare("SELECT * FROM entrantes {$whereSql} LIMIT 1");
        $stmt->execute($params);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$resultado) {
            $erro = "Registro não encontrado";
        }
    }
}

function calcularIdade($dataNascimento) {
    if (!$dataNascimento) return '';
    
    // Tenta vários formatos de data
    $formatos = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'];
    $data = null;
    
    foreach ($formatos as $formato) {
        $data = DateTime::createFromFormat($formato, $dataNascimento);
        if ($data) break;
    }
    
    if (!$data) return '';
    $hoje = new DateTime();
    return $hoje->diff($data)->y;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Benefício</title>
    <link rel="stylesheet" href="consulta.css">
</head>
<body>
    <div class="top-bar">
        <div class="navbar-left">
            <div class="navbar-logo">
                <img src="img/WhatsApp Image 2025-11-03 at 16.28.26 (1).jpeg" alt="One Consig">
            </div>
            <div class="navbar-breadcrumb">
                <span class="breadcrumb-arrow">‹</span>
                <span class="breadcrumb-title">CONSULTAS</span>
            </div>
        </div>
        <div class="navbar-right">
            <span style="margin-right: 15px; color: #666;">👤 <?= htmlspecialchars($_SESSION['user_nome']) ?></span>
            <button class="btn-theme" onclick="toggleTheme()" title="Alternar tema">☀️</button>
            <button class="btn-theme" onclick="window.location.href='logout.php'" title="Sair">🚪</button>
        </div>
    </div>
    
    <div class="layout">
        <div class="sidebar">
            <button class="menu-item active" onclick="showSection('consulta')">📋 Consulta</button>
            <button class="menu-item" onclick="showSection('importacao')">📥 Importação</button>
            <button class="menu-item" onclick="showSection('exportacao')">📤 Exportação</button>
            <button class="menu-item" onclick="showSection('cadastro')">➕ Cadastro Manual</button>
        </div>
        
        <div class="content">
            <div id="consulta" class="section active">
                <?php if (!empty($_SESSION['flash'])): ?>
                    <div class="alert success"><?php echo htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
                <?php endif; ?>
                <?php if (!empty($_SESSION['flash_error'])): ?>
                    <div class="alert error"><?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
                <?php endif; ?>
                
                <div class="consultar-section">
            <h2>Consultar</h2>
            
            <form method="POST">
                <div class="form-row-inline">
                    <input type="text" name="nome" placeholder="Nome" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
                    <input type="text" name="cpf" placeholder="CPF" value="<?= htmlspecialchars($_POST['cpf'] ?? '') ?>">
                    <input type="text" name="beneficio" placeholder="Benefício" value="<?= htmlspecialchars($_POST['beneficio'] ?? '') ?>">
                    <input type="text" name="telefone" placeholder="Telefone" value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>">
                    <button type="submit" class="btn-consultar">Consultar</button>
                </div>
            </form>
            
            <?php if ($erro): ?>
                <div class="erro-msg"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>
        </div>

        <?php if ($resultado): ?>
        <div class="cpf-box">
            <span class="cpf-label">CPF</span>
            <span class="cpf-value"><?= htmlspecialchars($resultado['CPF'] ?? '') ?></span>
        </div>
        
        <div class="resultado-card">
            <div class="info-grid">
                <div class="info-item">
                    <div class="icon">👤</div>
                    <div class="label">Nome</div>
                    <div class="value nome-value"><?= strtoupper(htmlspecialchars($resultado['NOME'] ?? '')) ?></div>
                </div>
                
                <div class="info-item">
                    <div class="icon">📅</div>
                    <div class="label">Idade</div>
                    <div class="value"><?php 
                        // Tenta usar o campo IDADE direto, senão calcula
                        $idade = $resultado['IDADE'] ?? '';
                        if (!$idade) {
                            $idade = calcularIdade($resultado['DATA_NASCIMENTO'] ?? '');
                        }
                        echo $idade ? $idade . ' Anos' : '-';
                    ?></div>
                </div>
                
                <div class="info-item">
                    <div class="icon">💰</div>
                    <div class="label">Margem 35</div>
                    <div class="value verde"><?php 
                        $margem = $resultado['margem_35'] ?? null;
                        if ($margem !== null && $margem !== '' && $margem !== '0' && $margem !== '0.00') {
                            echo 'R$ ' . number_format((float)str_replace(',', '.', $margem), 2, ',', '.');
                        } else {
                            echo 'R$ 0,00';
                        }
                    ?></div>
                </div>
                
                <div class="info-item">
                    <div class="icon">💵</div>
                    <div class="label">Valor Benefício</div>
                    <div class="value">R$ <?= number_format((float)($resultado['VALOR_BENEFICIO'] ?? 0), 2, ',', '.') ?></div>
                </div>
                
                <div class="info-item">
                    <div class="icon">💳</div>
                    <div class="label">Cartão Benefício (RCC)</div>
                    <div class="value amarelo"><?php 
                        $cartao = $resultado['cartao_rcc'] ?? null;
                        if ($cartao !== null && trim($cartao) !== '' && strtoupper(trim($cartao)) !== 'NULL') {
                            echo htmlspecialchars(trim($cartao));
                        } else {
                            echo '-';
                        }
                    ?></div>
                </div>
                
                <div class="info-item">
                    <div class="icon">📄</div>
                    <div class="label">Espécie</div>
                    <div class="value"><?= htmlspecialchars($resultado['CODIGO_ESPECIE'] ?? '') ?></div>
                </div>
                
                <div class="info-item">
                    <div class="icon">🛡️</div>
                    <div class="label">Situação</div>
                    <div class="value verde">ATIVO</div>
                </div>
            </div>
            
            <div class="center-btn">
                <button class="btn-alterar" onclick="mostrarFormularioEdicao()">✏️ Alterar Registro</button>
                <button class="btn-apagar" onclick="confirmarExclusao('<?= htmlspecialchars($resultado['CPF'] ?? '') ?>')">🗑️ Apagar Registro</button>
            </div>
            
            <div id="form-edicao" style="display:none;">
                <form method="post" action="atualizar.php">
                    <input type="hidden" name="cpf_original" value="<?= htmlspecialchars($resultado['CPF'] ?? '') ?>">
                    <div class="form-grid">
                        <input type="text" name="cpf" placeholder="CPF *" value="<?= htmlspecialchars($resultado['CPF'] ?? '') ?>" required>
                        <input type="text" name="nome" placeholder="Nome *" value="<?= htmlspecialchars($resultado['NOME'] ?? '') ?>" required>
                        <input type="text" name="beneficio" placeholder="Benefício *" value="<?= htmlspecialchars($resultado['BENEFICIO'] ?? '') ?>" required>
                        <input type="text" name="idade" placeholder="Idade *" value="<?= htmlspecialchars($resultado['IDADE'] ?? '') ?>" required>
                    </div>
                    <div class="form-grid">
                        <input type="text" name="data_nascimento" placeholder="Data Nascimento (dd/mm/aaaa) *" value="<?= htmlspecialchars($resultado['DATA_NASCIMENTO'] ?? '') ?>" required>
                        <input type="text" name="valor_beneficio" placeholder="Valor Benefício *" value="<?= htmlspecialchars($resultado['VALOR_BENEFICIO'] ?? '') ?>" required>
                        <input type="text" name="ddb" placeholder="DDB (dd/mm/aaaa)" value="<?= htmlspecialchars($resultado['DDB'] ?? '') ?>">
                        <input type="text" name="codigo_especie" placeholder="Código Espécie" value="<?= htmlspecialchars($resultado['CODIGO_ESPECIE'] ?? '') ?>">
                    </div>
                    <div class="form-grid">
                        <input type="text" name="cidade" placeholder="Cidade" value="<?= htmlspecialchars($resultado['CIDADE'] ?? '') ?>">
                        <input type="text" name="uf" placeholder="UF" value="<?= htmlspecialchars($resultado['UF'] ?? '') ?>">
                        <input type="text" name="lemit1" placeholder="Telefone 1" value="<?= htmlspecialchars($resultado['LEMIT1'] ?? '') ?>">
                        <input type="text" name="lemit2" placeholder="Telefone 2" value="<?= htmlspecialchars($resultado['LEMIT2'] ?? '') ?>">
                    </div>
                    <div class="form-grid">
                        <input type="text" name="lemit3" placeholder="Telefone 3" value="<?= htmlspecialchars($resultado['LEMIT3'] ?? '') ?>">
                    </div>
                    <div class="center-btn">
                        <button class="btn-consultar" type="submit">Salvar Alterações</button>
                        <button class="btn-secondary" type="button" onclick="ocultarFormularioEdicao()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
            </div>
            
            <div id="importacao" class="section">
                <div class="consultar-section">
                    <h2>Importar CSV</h2>
                    <form action="import_preview.php" method="post" enctype="multipart/form-data">
                        <div class="field">
                            <label>Arquivo CSV</label>
                            <input type="file" name="csv" accept=".csv,text/csv" required>
                        </div>
                        <details class="muted">
                            <summary>Formato esperado</summary>
                            <p>CSV com cabeçalho. Colunas: CPF, BENEFICIO, NOME, DDB, VALOR_BENEFICIO, DATA_NASCIMENTO, IDADE, CODIGO_ESPECIE, CIDADE, UF, LEMIT1, LEMIT2, LEMIT3, margem_35, cartao_rcc</p>
                            <p>Delimitador: ponto e vírgula (;). Codificação: UTF-8.</p>
                        </details>
                        <div class="actions">
                            <button class="btn-consultar" type="submit">Importar</button>
                            <a class="btn-secondary" href="modelo_csv.php">Baixar modelo</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div id="exportacao" class="section">
                <div class="consultar-section">
                    <h2>Exportar CSV</h2>
                    <p class="muted" style="margin-bottom: 20px;">Exportar todos os registros da tabela entrantes em formato CSV.</p>
                    <form method="get" action="export.php">
                        <button class="btn-consultar" type="submit">Exportar Tabela Completa</button>
                    </form>
                </div>
            </div>
            
            <div id="cadastro" class="section">
                <div class="consultar-section">
                    <h2>Cadastro Manual</h2>
                    <form method="post" action="cadastro_manual.php" id="formCadastro">
                        <div class="form-grid">
                            <input type="text" name="cpf" placeholder="CPF *" required>
                            <input type="text" name="nome" placeholder="Nome *" required>
                            <input type="text" name="beneficio" placeholder="Benefício *" required>
                            <input type="text" name="idade" placeholder="Idade *" required>
                        </div>
                        <div class="form-grid">
                            <input type="text" name="data_nascimento" placeholder="Data Nascimento (dd/mm/aaaa) *" required>
                            <input type="text" name="valor_beneficio" placeholder="Valor Benefício *" required>
                            <input type="text" name="ddb" placeholder="DDB (dd/mm/aaaa)">
                            <input type="text" name="codigo_especie" placeholder="Código Espécie">
                        </div>
                        <div class="form-grid">
                            <input type="text" name="cidade" placeholder="Cidade">
                            <input type="text" name="uf" placeholder="UF">
                            <input type="text" name="lemit1" placeholder="Telefone 1">
                            <input type="text" name="lemit2" placeholder="Telefone 2">
                        </div>
                        <div class="form-grid">
                            <input type="text" name="lemit3" placeholder="Telefone 3">
                        </div>
                        <p class="muted" style="margin-top: 15px; margin-bottom: 20px;">* Campos obrigatórios</p>
                        <button class="btn-consultar" type="submit">Cadastrar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function showSection(sectionId) {
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.menu-item').forEach(m => m.classList.remove('active'));
        document.getElementById(sectionId).classList.add('active');
        event.target.classList.add('active');
    }
    
    function confirmarExclusao(cpf) {
        if (confirm('Tem certeza que deseja apagar este registro?\nCPF: ' + cpf)) {
            window.location.href = 'deletar.php?cpf=' + encodeURIComponent(cpf);
        }
    }
    
    function mostrarFormularioEdicao() {
        document.getElementById('form-edicao').style.display = 'block';
    }
    
    function ocultarFormularioEdicao() {
        document.getElementById('form-edicao').style.display = 'none';
    }
    
    function toggleTheme() {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        localStorage.setItem('darkMode', isDark);
        document.querySelector('.btn-theme').textContent = isDark ? '🌙' : '☀️';
    }
    
    // Carregar tema salvo
    document.addEventListener('DOMContentLoaded', function() {
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
            document.querySelector('.btn-theme').textContent = '🌙';
        }
    });
    
    // Máscaras de formatação
    function maskDate(input) {
        let value = input.value.replace(/\D/g, '');
        if (value.length >= 2) value = value.slice(0,2) + '/' + value.slice(2);
        if (value.length >= 5) value = value.slice(0,5) + '/' + value.slice(5,9);
        input.value = value;
    }
    
    function maskPhone(input) {
        let value = input.value.replace(/\D/g, '');
        if (value.length <= 10) {
            if (value.length >= 2) value = '(' + value.slice(0,2) + ') ' + value.slice(2);
            if (value.length >= 9) value = value.slice(0,9) + '-' + value.slice(9,13);
        } else {
            value = '(' + value.slice(0,2) + ') ' + value.slice(2,7) + '-' + value.slice(7,11);
        }
        input.value = value;
    }
    
    function maskMoney(input) {
        let value = input.value.replace(/\D/g, '');
        value = (parseInt(value) / 100).toFixed(2);
        value = value.replace('.', ',');
        value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        input.value = 'R$ ' + value;
    }
    
    // Aplica máscaras
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('input[name="data_nascimento"], input[name="ddb"]').forEach(input => {
            input.addEventListener('input', function() { maskDate(this); });
        });
        
        document.querySelectorAll('input[name="lemit1"], input[name="lemit2"], input[name="lemit3"]').forEach(input => {
            input.addEventListener('input', function() { maskPhone(this); });
        });
        
        document.querySelectorAll('input[name="valor_beneficio"]').forEach(input => {
            input.addEventListener('input', function() { maskMoney(this); });
        });
        
        document.getElementById('formCadastro').addEventListener('submit', function(e) {
            const valorInput = this.querySelector('input[name="valor_beneficio"]');
            if (valorInput) {
                valorInput.value = valorInput.value.replace(/[^0-9,]/g, '').replace(',', '.');
            }
        });
    });
    </script>
</body>
</html>
