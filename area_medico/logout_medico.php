<?php
// =================================================================================
// ARQUIVO: area_medico/logout_medico.php
// PROPÓSITO: Destruir a sessão do médico e redirecionar para a tela pública.
// =================================================================================

session_start();
$_SESSION = array();

// Destrói os cookies de sessão do navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Envia o médico de volta para a tela de login que fica na pasta 'public'
header("Location: ../public/login.php");
exit;
?>