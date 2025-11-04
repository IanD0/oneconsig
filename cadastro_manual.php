<?php
session_start();
require __DIR__ . '/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cpf = trim($_POST['cpf'] ?? '');
    $beneficio = trim($_POST['beneficio'] ?? '');
    $nome = trim($_POST['nome'] ?? '');
    $ddb = trim($_POST['ddb'] ?? '') ?: null;
    $valor_beneficio = trim($_POST['valor_beneficio'] ?? '');
    $valor_beneficio = $valor_beneficio ? str_replace(',', '.', $valor_beneficio) : null;
    $data_nascimento = trim($_POST['data_nascimento'] ?? '');
    $idade = trim($_POST['idade'] ?? '') ?: null;
    $codigo_especie = trim($_POST['codigo_especie'] ?? '') ?: null;
    $cidade = trim($_POST['cidade'] ?? '') ?: null;
    $uf = trim($_POST['uf'] ?? '') ?: null;
    $lemit1 = trim($_POST['lemit1'] ?? '') ?: null;
    $lemit2 = trim($_POST['lemit2'] ?? '') ?: null;
    $lemit3 = trim($_POST['lemit3'] ?? '') ?: null;
    
    try {
        $stmt = $conn->prepare(
            "INSERT INTO entrantes (CPF, BENEFICIO, NOME, DDB, VALOR_BENEFICIO, DATA_NASCIMENTO, IDADE, CODIGO_ESPECIE, CIDADE, UF, LEMIT1, LEMIT2, LEMIT3) 
             VALUES (:cpf, :beneficio, :nome, :ddb, :valor_beneficio, :data_nascimento, :idade, :codigo_especie, :cidade, :uf, :lemit1, :lemit2, :lemit3)"
        );
        
        $stmt->bindValue(':cpf', $cpf, PDO::PARAM_STR);
        $stmt->bindValue(':beneficio', $beneficio, PDO::PARAM_STR);
        $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
        $stmt->bindValue(':ddb', $ddb, PDO::PARAM_STR);
        $stmt->bindValue(':valor_beneficio', $valor_beneficio, $valor_beneficio === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':data_nascimento', $data_nascimento, PDO::PARAM_STR);
        $stmt->bindValue(':idade', $idade, $idade === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':codigo_especie', $codigo_especie, $codigo_especie === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':cidade', $cidade, $cidade === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':uf', $uf, $uf === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':lemit1', $lemit1, $lemit1 === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':lemit2', $lemit2, $lemit2 === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':lemit3', $lemit3, $lemit3 === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        
        $stmt->execute();
        
        $_SESSION['flash'] = 'Registro cadastrado com sucesso!';
    } catch (Exception $e) {
        $_SESSION['flash_error'] = 'Erro ao cadastrar: ' . $e->getMessage();
    }
}

header('Location: consulta.php');
exit;
