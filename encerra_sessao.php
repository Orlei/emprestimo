<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Defina o tempo limite em segundos (Ex: 60 minutos = 3600 segundos)
$tempo_limite = 3600; 

// Se o usuário não está logado, manda para o login
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Verifica se existe o registro do último clique
if (isset($_SESSION['ultimo_clique'])) {
    $tempo_inativo = time() - $_SESSION['ultimo_clique'];
    
    // Se o tempo inativo for maior que o limite, desloga
    if ($tempo_inativo > $tempo_limite) {
        session_unset();
        session_destroy();
        header("Location: login.php?erro=sessao_expirada");
        exit;
    }
}

// Atualiza o timestamp do último clique para o momento atual
$_SESSION['ultimo_clique'] = time();

// Guarda o tempo restante na sessão para o JavaScript ler se necessário
$_SESSION['tempo_limite_total'] = $tempo_limite;
?>