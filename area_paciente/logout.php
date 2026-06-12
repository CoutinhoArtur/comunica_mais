<?php
// =================================================================================
// ARQUIVO: area_paciente/logout.php
// PROPÓSITO: Encerrar a sessão de forma segura e limpar os dados do navegador.
// REGRA DE NEGÓCIO ATENDIDA: RN07 - Sigilo e Segurança da Informação.
// =================================================================================

// 1. LOCALIZA A SESSÃO ATUAL
// Para destruir uma sessão, o PHP precisa primeiro localizá-la no servidor.
session_start();

// 2. LIMPA AS VARIÁVEIS DE SESSÃO
// Remove todos os dados gravados na memória temporária da sessão atual ($_SESSION).
$_SESSION = array();

// 3. DESTROI O COOKIE DA SESSÃO NO NAVEGADOR
// Se o sistema usar cookies para guardar o ID da sessão, limpa o cookie diretamente no PC do usuário.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. DESTROI A SESSÃO NO SERVIDOR
// Elimina permanentemente o arquivo de sessão criado no servidor.
session_destroy();

// 5. REDIRECIONAMENTO FINAL
// Envia o usuário de volta para a tela de login pública com total segurança.
header("Location: ../public/login.php");
exit; // Interrompe qualquer execução residual do script
?>