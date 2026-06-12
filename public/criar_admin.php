<?php
// =================================================================================
// ARQUIVO: public/criar_admin.php
// PROPÓSITO: Criar um utilizador Administrador com senha criptografada para testes.
// =================================================================================

// 1. IMPORTAÇÃO DO BANCO DE DADOS
// Carrega o arquivo de configuração para abrir a comunicação PDO com o banco MySQL.
require_once '../config/db.php';

// Inicializa a variável de texto para armazenar a mensagem de feedback na tela.
$mensagem = "";

try {
    // Define as credenciais que serão usadas para entrar no Painel Administrativo
    $nome_admin  = "Administrador Geral";
    $email_admin = "admin@comunicamais.com";
    $senha_limpa = "admin123"; // Esta será a senha digitada na tela de login
    $tipo_admin  = "admin";

    // Gera o Hash criptografado seguro para a senha 'admin123'
    $senha_cripto = password_hash($senha_limpa, PASSWORD_DEFAULT);

    // PASSO DE SEGURANÇA: Verifica se este e-mail já não foi cadastrado antes
    $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmtCheck->execute([$email_admin]);
    
    if ($stmtCheck->fetch()) {
        $mensagem = "O usuário administrador já existe no banco de dados! Pode ir direto para o login.";
    } else {
        // Prepara o comando SQL de inserção na tabela 'usuarios'
        $sql = "INSERT INTO usuarios (nome, email, senha, tipo, data_criacao) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome_admin, $email_admin, $senha_cripto, $tipo_admin]);

        $mensagem = "Usuário Administrador criado com sucesso de forma segura!";
    }

} catch (PDOException $e) {
    // Captura qualquer erro técnico caso a tabela ou colunas falhem.
    $mensagem = "Erro crítico de banco de dados: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunica+ | Gerador de Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                
                <div class="card p-4 shadow-sm border-0 bg-white text-center">
                    <h3 class="fw-bold mb-3 text-primary">
                        <i class="fa-solid fa-user-shield me-2"></i>Gerador de Acesso Master
                    </h3>
                    
                    <div class="alert alert-info py-3 small mb-4">
                        <i class="fa-solid fa-circle-info me-2"></i>
                        <strong>Status:</strong> <?= $mensagem ?>
                    </div>

                    <div class="text-start bg-light p-3 rounded mb-4 small">
                        <p class="mb-1"><strong>E-mail criado:</strong> <code>admin@comunicamais.com</code></p>
                        <p class="mb-0"><strong>Senha padrão:</strong> <code>admin123</code></p>
                    </div>
                    
                    <div class="d-grid">
                        <a href="login.php" class="btn btn-primary fw-bold shadow-sm">
                            <i class="fa-solid fa-right-to-bracket me-2"></i>Ir para a Tela de Login
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>