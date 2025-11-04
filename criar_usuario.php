<?php
require __DIR__ . '/conexao.php';

// Gera hash da senha admin123
$senha = 'admin123';
$hash = password_hash($senha, PASSWORD_DEFAULT);

echo "<h3>Criando usuário admin...</h3>";

try {
    // Cria a tabela se não existir
    $conn->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario VARCHAR(50) NOT NULL UNIQUE,
            senha VARCHAR(255) NOT NULL,
            nome VARCHAR(100) NOT NULL,
            ativo TINYINT(1) DEFAULT 1,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✓ Tabela usuarios criada/verificada</p>";
    
    // Remove usuário admin se existir
    $conn->exec("DELETE FROM usuarios WHERE usuario = 'admin'");
    
    // Insere novo usuário admin
    $stmt = $conn->prepare("INSERT INTO usuarios (usuario, senha, nome) VALUES ('admin', :senha, 'Administrador')");
    $stmt->execute([':senha' => $hash]);
    
    echo "<p>✓ Usuário admin criado com sucesso!</p>";
    echo "<p><strong>Usuário:</strong> admin</p>";
    echo "<p><strong>Senha:</strong> admin123</p>";
    echo "<p><strong>Hash gerado:</strong> <code>$hash</code></p>";
    echo "<hr>";
    echo "<p><a href='login.php'>Ir para o login</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Erro: " . $e->getMessage() . "</p>";
}
