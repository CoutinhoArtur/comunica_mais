<?php
// =================================================================================
// ARQUIVO: public/cadastro.php
// PROPÓSITO: Interface visual e lógica de inserção para novos cadastros de pacientes.
// REGRA DE NEGÓCIO ATENDIDA: RN03 - Unicidade de Prontuário via CPF (Restrição Física).
// EXPLICAÇÃO PARA A BANCA: Este arquivo demonstra o tratamento de tabelas relacionadas
// (Usuários e Pacientes) através de uma abordagem atômica e segura. Ele implementa o 
// conceito de Transações Relacionais para garantir a consistência do banco de dados.
// =================================================================================

// ---------------------------------------------------------------------------------
// 1. CAMADA DE CONFIGURAÇÃO E INFRAESTRUTURA
// ---------------------------------------------------------------------------------
// Conexão segura via PDO. O uso de 'require_once' previne falhas de carregamento duplicado.
require_once '../config/db.php';

// ---------------------------------------------------------------------------------
// 2. INICIALIZAÇÃO DE VARIÁVEIS DE CONTROLE VISUAL (ESTADOS DA VIEW)
// ---------------------------------------------------------------------------------
// Inicializadas vazias para evitar que os componentes de alerta (Bootstrap) 
// sejam renderizados incorretamente na primeira carga da página (requisição GET).
$mensagem_erro = "";
$mensagem_sucesso = "";

// ---------------------------------------------------------------------------------
// 3. VERIFICAÇÃO DO MÉTODO DE REQUISIÇÃO (CONTROLADOR)
// ---------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // -----------------------------------------------------------------------------
    // 4. SANITIZAÇÃO DE DADOS (DEFESA CONTRA COMPROMETIMENTO DE DADOS / XSS)
    // -----------------------------------------------------------------------------
    // O uso de 'filter_input' aplica uma camada preliminar de segurança, limpando tags
    // HTML e caracteres especiais dos campos de texto, mitigando ataques de Cross-Site Scripting (XSS).
    $nome  = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $senha = $_POST['senha']; 
    $cpf   = filter_input(INPUT_POST, 'cpf', FILTER_SANITIZE_SPECIAL_CHARS);
    $tel   = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_SPECIAL_CHARS);

    // -----------------------------------------------------------------------------
    // 5. VALIDAÇÃO DE INTEGRIDADE NO BACK-END
    // -----------------------------------------------------------------------------
    // Garante que mesmo que o usuário desative os atributos 'required' do HTML5 via console,
    // o servidor interceptará a requisição e impedirá a inserção de dados nulos.
    if (!$nome || !$email || empty($senha) || empty($cpf)) {
        $mensagem_erro = "Por favor, preencha todos os campos obrigatórios corretamente.";
    } else {
        
        try {
            // -------------------------------------------------------------------------
            // 6. INTEGRIDADE REFERENCIAL: TRANSAÇÕES SQL (ACID)
            // -------------------------------------------------------------------------
            // CONCEITO CHAVE PARA A BANCA: Ativamos o '$pdo->beginTransaction()'. Como o nosso
            // modelo de dados divide o cadastro em duas tabelas (Tabela Pai 'usuarios' e Tabela
            // Filha 'pacientes'), a transação garante a propriedade da Atomicidade (Princípio ACID).
            // Ou ambas as tabelas recebem os dados com sucesso, ou nada é salvo.
            $pdo->beginTransaction();

            // -------------------------------------------------------------------------
            // 7. REGRA DE NEGÓCIO: UNICIDADE DE CREDENCIAIS (E-MAIL)
            // -------------------------------------------------------------------------
            $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmtCheck->execute([$email]);
            if ($stmtCheck->fetch()) {
                throw new Exception("Este endereço de e-mail já está registado no sistema.");
            }

            // -------------------------------------------------------------------------
            // 8. REGRA DE NEGÓCIO ATENDIDA: RN03 - UNICIDADE DE CPF/SUS
            // -------------------------------------------------------------------------
            // Consulta preparatória para garantir que nenhum prontuário duplicado seja gerado.
            $stmtCpfCheck = $pdo->prepare("SELECT id FROM pacientes WHERE cpf_sus = ?");
            $stmtCpfCheck->execute([$cpf]);
            if ($stmtCpfCheck->fetch()) {
                throw new Exception("Este CPF/SUS já está vinculado a um prontuário ativo (RN03).");
            }

            // -------------------------------------------------------------------------
            // 9. CRIPTOGRAFIA DE SENHAS (SECURITY BY DESIGN)
            // -------------------------------------------------------------------------
            // Aplicação da função password_hash com o algoritmo padrão atual da comunidade (Bcrypt).
            // Gera uma cadeia irreversível de caracteres (hash), protegendo o segredo do usuário.
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

            // -------------------------------------------------------------------------
            // 10. PASSO A: INSERÇÃO NA TABELA PAI ('usuarios')
            // -------------------------------------------------------------------------
            // Define o perfil padrão da conta criada nesta rota pública estritamente como 'paciente'.
            $sqlUser = "INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'paciente')";
            $stmtUser = $pdo->prepare($sqlUser);
            $stmtUser->execute([$nome, $email, $senhaHash]);
            
            // CAPTURA DE CHAVE ESTRANGEIRA (FK): O método 'lastInsertId()' captura o ID gerado de
            // forma autoincrementável pelo MySQL para o usuário atual, permitindo a vinculação imediata.
            $novo_usuario_id = $pdo->lastInsertId();

            // -------------------------------------------------------------------------
            // 11. PASSO B: INSERÇÃO NA TABELA FILHA ('pacientes')
            // -------------------------------------------------------------------------
            // Mapeamento exato das colunas físicas do banco de dados (id relacional, cpf_sus e telefone).
            $sqlPac = "INSERT INTO pacientes (id, cpf_sus, telefone) VALUES (?, ?, ?)";
            $stmtPac = $pdo->prepare($sqlPac);
            $stmtPac->execute([$novo_usuario_id, $cpf, $tel]);

            // -------------------------------------------------------------------------
            // 12. PERSISTÊNCIA DEFINITIVA (COMMIT)
            // -------------------------------------------------------------------------
            // Se o fluxo chegou até aqui sem disparar nenhuma exceção, o 'commit' consolida
            // as operações de escrita em ambas as tabelas simultaneamente no motor InnoDB.
            $pdo->commit();
            
            $mensagem_sucesso = "Registo realizado com sucesso! O seu prontuário clínico Comunica+ foi criado.";
            
        } catch (Exception $e) {
            // -------------------------------------------------------------------------
            // 13. TRATAMENTO DE EXCEÇÃO E REVERSÃO (ROLLBACK)
            // -------------------------------------------------------------------------
            // Se o Passo A funcionar, mas o Passo B falhar (ou houver duplicidade), o 'rollBack()'
            // entra em ação limpando qualquer alteração feita na sessão, impedindo que dados órfãos 
            // (um usuário sem registro correspondente na tabela de pacientes) poluam o banco.
            $pdo->rollBack();
            $mensagem_erro = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunica+ | Criar Conta de Paciente</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* -------------------------------------------------------------------------
           DESIGN SYSTEM CONSTANTS (VARIÁVEIS CSS / TOKENS DE DESIGN)
           ------------------------------------------------------------------------- */
        :root {
            --verde-escuro: #12382c;
            --verde-medio: #1b4d3e;
            --verde-destaque: #2c7a5f;
            --cinza-fundo: #f8faf9;
        }
        
        body {
            background: linear-gradient(135deg, var(--cinza-fundo) 0%, #e2ede8 100%);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #333333;
        }

        .card-cadastro {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(18, 56, 44, 0.06);
            border-top: 5px solid var(--verde-medio);
        }

        .brand-logo {
            color: var(--verde-escuro);
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .brand-logo i {
            color: var(--verde-destaque);
        }

        .form-label {
            color: #4a5568;
            font-size: 0.9rem;
        }

        /* CUSTOMIZAÇÃO DE INPUTS (CLEAN DESIGN RESTYLING) */
        .input-group-text {
            background-color: #ffffff;
            border-right: none;
            color: #a0aec0;
            border-radius: 10px 0 0 10px;
            padding-left: 15px;
            transition: all 0.3s ease;
        }

        .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
            padding: 11px 14px;
            font-size: 0.95rem;
            color: #2d3748;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #dee2e6;
            box-shadow: none;
            background-color: #ffffff;
        }

        /* MICROINTERAÇÕES (FEEDBACK VISUAL AO USUÁRIO) */
        .input-group-focus .input-group-text {
            border-color: var(--verde-destaque);
            color: var(--verde-destaque);
        }
        .input-group-focus .form-control {
            border-color: var(--verde-destaque);
        }

        .btn-verde {
            background-color: var(--verde-medio);
            color: #ffffff;
            font-weight: 600;
            font-size: 1rem;
            padding: 12px;
            border-radius: 10px;
            border: none;
            transition: all 0.2s ease-in-out;
        }

        .btn-verde:hover {
            background-color: var(--verde-escuro);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(27, 77, 62, 0.2);
        }

        .text-link {
            color: var(--verde-destaque);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .text-link:hover {
            color: var(--verde-escuro);
            text-decoration: underline;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 py-5">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-11 col-sm-9 col-md-7 col-lg-5">
                
                <div class="text-center mb-4">
                    <a href="index.php" class="text-decoration-none brand-logo fs-2">
                        <i class="fa-solid fa-wave-square me-2"></i>Comunica+
                    </a>
                </div>

                <div class="card card-cadastro bg-white p-4">
                    <h4 class="fw-bold mb-1" style="color: var(--verde-escuro);">Criar Nova Conta</h4>
                    <p class="text-muted small mb-4">Cadastre-se para acompanhar seus treinos fonoaudiológicos e consultas.</p>

                    <?php if (!empty($mensagem_erro)): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-0 small mb-4" role="alert" style="background-color: #fff5f5; color: #c53030;">
                            <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($mensagem_erro) ?>
                            <button type="button" class="btn-close small" data-bs-dismiss="alert" aria-label="Close" style="font-size: 0.75rem;"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($mensagem_sucesso)): ?>
                        <div class="alert alert-success border-0 small mb-4 p-3" role="alert" style="background-color: #f0fff4; color: #22543d;">
                            <div class="fw-bold mb-1"><i class="fa-solid fa-circle-check me-2"></i> Sucesso!</div>
                            <div><?= htmlspecialchars($mensagem_sucesso) ?></div>
                            <hr class="my-2" style="border-color: #c6f6d5;">
                            <a href="login.php" class="fw-bold text-decoration-none" style="color: var(--verde-medio);">Acessar a tela de login <i class="fa-solid fa-arrow-right ms-1" style="font-size: 0.8rem;"></i></a>
                        </div>
                    <?php endif; ?>

                    <form action="cadastro.php" method="POST" autocomplete="off">
                        
                        <div class="mb-3">
                            <label for="nome" class="form-label fw-semibold">Nome Completo *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                                <input type="text" class="form-control" id="nome" name="nome" placeholder="Ex: João Silva" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Endereço de E-mail *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="nome@exemplo.com" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="cpf" class="form-label fw-semibold">CPF ou Cartão SUS *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-id-card"></i></span>
                                <input type="text" class="form-control" id="cpf" name="cpf" placeholder="000.000.000-00" required>
                            </div>
                            <div class="form-text text-muted" style="font-size: 0.75rem;">Garante a unicidade do seu prontuário clínico.</div>
                        </div>

                        <div class="mb-3">
                            <label for="telefone" class="form-label fw-semibold">Telemóvel / Telefone</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                                <input type="text" class="form-control" id="telefone" name="telefone" placeholder="(11) 99999-9999">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="senha" class="form-label fw-semibold">Palavra-passe (Senha) *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" class="form-control" id="senha" name="senha" placeholder="Mínimo 6 caracteres" required minlength="6">
                            </div>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-verde shadow-sm">Finalizar Registo</button>
                        </div>

                        <div class="text-center mt-3">
                            <span class="text-muted small">Já tem uma conta?</span> 
                            <a href="login.php" class="text-link small fw-bold ms-1">Faça Login</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // DOM INTERACTION: Adiciona e remove efeitos visuais de borda nos containers de input agrupados (.input-group)
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.classList.add('input-group-focus');
            });
            input.addEventListener('blur', () => {
                input.parentElement.classList.remove('input-group-focus');
            });
        });
    </script>
</body>
</html>