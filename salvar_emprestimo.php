<?php
// salvar_emprestimo.php

// 1. Controle de sessão e segurança
require_once 'encerra_sessao.php';

// ========================================================
// 2. CONEXÃO COM O BANCO DE DADOS (Usando seu padrão PDO)
// ========================================================
$host    = "127.0.0.1";
$banco   = "atp";
$usuario = "root";
$senha   = "7!5JJTBpIoZb.5t!";

try {
    $dsn = "mysql:host=$host;dbname=$banco;charset=utf8mb4";
    $pdo = new PDO($dsn, $usuario, $senha, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("Erro na conexão com o banco local: " . $e->getMessage());
}
// ========================================================


// 3. Verifica se o formulário foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Captura os dados enviados ocultamente pelo JavaScript do autocomplete
    $origem = filter_input(INPUT_POST, 'origem_cadastro', FILTER_UNSAFE_RAW);
    $login  = filter_input(INPUT_POST, 'beneficiario_login', FILTER_UNSAFE_RAW);
    $nome   = filter_input(INPUT_POST, 'beneficiario_nome', FILTER_UNSAFE_RAW);
    $email  = filter_input(INPUT_POST, 'beneficiario_email', FILTER_VALIDATE_EMAIL);
    $setor  = filter_input(INPUT_POST, 'beneficiario_setor', FILTER_UNSAFE_RAW);
    
    // Captura os dados do item que está sendo emprestado
    $item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
    $operador_logado = $_SESSION['usuario_login'] ?? 'sistema'; 

    // Validação básica de segurança
    if (!$login || !$item_id) {
        header("Location: emprestimos.php?status=erro&msg=Dados+incompletos");
        exit;
    }

    // Inicia uma Transação no Banco via PDO (Segurança: se algo falhar, desfaz tudo)
    $pdo->beginTransaction();

    try {
        // ==========================================
        // PASSO 1: CASO "JUST-IN-TIME" (LDAP)
        // Se a pessoa veio do LDAP, cadastra ela localmente automático
        // ==========================================
        if ($origem === 'ldap') {
            $sql_func = "INSERT INTO funcionarios (login, nome, email, setor) VALUES (?, ?, ?, ?) 
                         ON DUPLICATE KEY UPDATE nome = ?, email = ?, setor = ?";
            $stmt_func = $pdo->prepare($sql_func);
            $stmt_func->execute([$login, $nome, $email, $setor, $nome, $email, $setor]);
        }

        // ==========================================
        // PASSO 2: REGISTRAR O EMPRÉSTIMO
        // ==========================================
        $sql_emp = "INSERT INTO emprestimos (id_item, login_funcionario, login_operador, data_emprestimo, status) 
                    VALUES (?, ?, ?, NOW(), 'pendente')";
        $stmt_emp = $pdo->prepare($sql_emp);
        $stmt_emp->execute([$item_id, $login, $operador_logado]);

        // ==========================================
        // PASSO 3: ATUALIZAR O STATUS DO ITEM
        // ==========================================
        $sql_item = "UPDATE itens SET status = 'emprestado' WHERE id = ?";
        $stmt_item = $pdo->prepare($sql_item);
        $stmt_item->execute([$item_id]);

        // Se tudo deu certo, confirma todas as operações acima no banco
        $pdo->commit();

        // Redireciona de volta com aviso de sucesso
        header("Location: emprestimos.php?status=sucesso&msg=Emprestimo+registrado+com+sucesso");
        exit;

    } catch (Exception $e) {
        // Desfaz tudo se der erro para não salvar dados corrompidos ou pela metade
        $pdo->rollBack();
        header("Location: emprestimos.php?status=erro&msg=" . urlencode($e->getMessage()));
        exit;
    }

} else {
    header("Location: emprestimos.php");
    exit;
}