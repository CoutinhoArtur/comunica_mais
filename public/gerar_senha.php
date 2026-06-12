<?php
// =================================================================================
// ARQUIVO: public/gerar_senha.php (Atualizado)
// PROPÓSITO: Sincronizar e aplicar o Hash de criptografia para Artur e Dr. Roberto.
// =================================================================================

// 1. IMPORTAÇÃO DO BANCO DE DADOS
// Carrega o arquivo de configuração para abrir a comunicação PDO com o banco MySQL.
require_once '../config/db.php';

// Inicializa as variáveis de texto para armazenar as mensagens de feedback na tela.
$mensagem_artur = "";
$mensagem_medico = "";

try {
    // -------------------------------------------------------------------------
    // 2. ATUALIZAÇÃO DA CONTA DO ARTUR (ID: 2)
    // -------------------------------------------------------------------------
    // Define a senha desejada para o paciente Artur.
    $senha_artur_limpa = '123456';
    // Gera o Hash criptografado usando o algoritmo padrão do seu servidor atual.
    $hash_artur = password_hash($senha_artur_limpa, PASSWORD_DEFAULT);

    // Prepara a query SQL apontando para o ID 2 (conforme visto na imagem do banco).
    $sqlArtur = "UPDATE usuarios SET senha = ? WHERE id = 2 AND email = 'artur@gmail.com'";
    $stmtArtur = $pdo->prepare($sqlArtur);
    $stmtArtur->execute([$hash_artur]);
    
    // Armazena a resposta de sucesso para exibição visual.
    $mensagem_artur = "Conta do Artur (ID 2) atualizada com o Hash correto!";

    // -------------------------------------------------------------------------
    // 3. ATUALIZAÇÃO DA CONTA DO DR. ROBERTO (ID: 3)
    // -------------------------------------------------------------------------
    // Define a senha pretendida para o fonoaudiólogo Dr. Roberto.
    $senha_medico_limpa = 'medico123';
    // Gera o código criptografado seguro correspondente a 'medico123'.
    $hash_medico = password_hash($senha_medico_limpa, PASSWORD_DEFAULT);

    // Prepara a query SQL apontando exatamente para o ID 3 correto encontrado na imagem.
    $sqlMedico = "UPDATE usuarios SET senha = ? WHERE id = 3 AND email = 'roberto@comunicamais.com'";
    $stmtMedico = $pdo->prepare($sqlMedico);
    $stmtMedico->execute([$hash_medico]);
    
    // Armazena a resposta de sucesso para o médico.
    $mensagem_medico = "Conta do Dr. Roberto (ID 3) atualizada com o Hash correto!";

} catch (PDOException $e) {
    // Captura qualquer erro técnico caso as colunas ou tabelas falhem.
    $mensagem_artur = "Erro crítico de banco de dados: " . $e->getMessage();
}
?>
<!DOCTYPE html> <html lang="pt-pt"> <head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>Comunica+ | Sincronizador de Credenciais</title> <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">

    <div class="container"> <div class="row justify-content-center"> <div class="col-md-7 col-lg-6"> <div class="card p-4 shadow-sm border-0 bg-white">
                    
                    <h3 class="fw-bold mb-3 text-success">
                        <i class="fa-solid fa-gears me-2"></i>Sincronizador de Segurança V2
                    </h3>
                    
                    <p class="text-muted small">Os IDs foram mapeados de acordo com a leitura real da sua tabela de banco de dados.</p>
                    
                    <div class="alert alert-success py-2 small mb-2 d-flex align-items-center">
                        <i class="fa-solid fa-circle-check text-success me-2"></i>
                        <span><?= $mensagem_artur ?></span>
                    </div>

                    <div class="alert alert-success py-2 small mb-4 d-flex align-items-center">
                        <i class="fa-solid fa-circle-check text-success me-2"></i>
                        <span><?= $mensagem_medico ?></span>
                    </div>
                    
                    <div class="d-grid">
                        <a href="login.php" class="btn btn-success fw-bold btn-lg shadow-sm">
                            <i class="fa-solid fa-right-to-bracket me-2"></i>Ir para a Tela de Login
                        </a>
                    </div>
                    
                </div> </div> </div> </div> </body> </html>

