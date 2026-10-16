<?php
require_once 'conexao.php';

// Limpa todas as variaveis de sessao e destroi a sessao
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redireciona para a tela de autenticacao
header('Location: login.php');
exit;
?>
