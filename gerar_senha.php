<?php
// Script auxiliar para gerar hash de senhas
// Acesse: gerar_senha.php?senha=suasenha

if (isset($_GET['senha'])) {
    $senha = $_GET['senha'];
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    echo "<h3>Hash gerado:</h3>";
    echo "<p><strong>Senha:</strong> " . htmlspecialchars($senha) . "</p>";
    echo "<p><strong>Hash:</strong> <code>" . htmlspecialchars($hash) . "</code></p>";
    echo "<hr>";
    echo "<p>Use este SQL para inserir o usuário:</p>";
    echo "<pre>INSERT INTO usuarios (usuario, senha, nome) VALUES ('usuario', '$hash', 'Nome Completo');</pre>";
} else {
    echo "<h3>Gerador de Hash de Senha</h3>";
    echo "<p>Use: gerar_senha.php?senha=suasenha</p>";
}
