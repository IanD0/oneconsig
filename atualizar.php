<?php
session_start();
require __DIR__ . '/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cpf_original = trim($_POST['cpf_original'] ?? '');
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
            "UPDATE entrantes SET 
                CPF = :cpf,
                BENEFICIO = :beneficio,
                NOME = :nome,
                DDB = :ddb,
                VALOR_BENEFICIO = :valor_beneficio,
                DATA_NASCIMENTO = :data_nascimento,
                IDADE = :idade,
                CODIGO_ESPECIE = :codigo_especie,
                CIDADE = :cidade,
                UF = :uf,
                LEMIT1 = :lemit1,
                LEMIT2 = :lemit2,
                LEMIT3 = :lemit3
            WHERE CPF = :cpf_original"
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
        $stmt->bindValue(':cpf_original', $cpf_original, PDO::PARAM_STR);
        
        $stmt->execute();
        
        $_SESSION['flash'] = 'Registro atualizado com sucesso!';
    } catch (Exception $e) {
        $_SESSION['flash_error'] = 'Erro ao atualizar: ' . $e->getMessage();
    }
}

header('Location: consulta.php');
exit;
