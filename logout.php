<?php
// Inicia a sessão para que o PHP saiba qual sessão deve ser destruída
session_start();

// Limpa todas as variáveis de sessão
$_SESSION = array();

// Se desejar destruir completamente a sessão, apaga também o cookie de sessão.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destrói a sessão no servidor
session_destroy();

// Redireciona de volta para a tela de login
header("Location: login.php");
exit;
?>