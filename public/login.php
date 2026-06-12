<?php
// =================================================================================
// ARQUIVO: public/login.php
// PROPÓSITO: Interface visual e controle de autenticação de utilizadores.
// REGRA DE NEGÓCIO ATENDIDA: RN04 - Sigilo absoluto de prontuário e controle de acesso.
// EXPLICAÇÃO PARA A BANCA: Este arquivo é a porta de entrada segura do sistema. Ele
// implementa o conceito de Controle de Acesso Baseado em Perfis (RBAC), garantindo que
// cada tipo de usuário veja apenas os dados que lhe são permitidos.
// =================================================================================

// ---------------------------------------------------------------------------------
// 1. GERENCIAMENTO DE SESSÃO (STATE MANAGEMENT)
// ---------------------------------------------------------------------------------
// O session_start() inicializa o array global $_SESSION. Na arquitetura HTTP (que é stateless),
// a sessão é o mecanismo que permite persistir o estado de autenticação do usuário
// enquanto ele navega entre as páginas, utilizando um ID de sessão transmitido por cookies.
session_start();

// ---------------------------------------------------------------------------------
// 2. CAMADA DE CONFIGURAÇÃO E INFRAESTRUTURA
// ---------------------------------------------------------------------------------
// Inclusão segura da conexão com o banco de dados. O 'require_once' garante que o arquivo
// db.php seja carregado apenas uma vez, interrompendo a execução se houver falhas, 
// o que previne erros de redundância ou telas em branco (White Screen of Death).
require_once '../config/db.php';

// Inicialização da variável de controle de estado de erro na View.
$mensagem_erro = "";

// ---------------------------------------------------------------------------------
// 3. PROCESSAMENTO DE REQUISIÇÃO (CONTROLADOR)
// ---------------------------------------------------------------------------------
// Filtro de segurança para garantir que o script só processe dados enviados via POST,
// evitando que requisições do tipo GET (que expõem dados na URL) manipulem o fluxo.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // TRATAMENTO DE INPUTS: O trim() limpa espaços vazios acidentais no início e fim do texto.
    // Isso evita falhas de login caso o usuário copie e cole o e-mail com um espaço extra.
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    // VALIDAÇÃO EM CAMADA DE BACK-END: Mesmo que o HTML5 use o atributo 'required',
    // a validação no servidor é indispensável para bloquear requisições maliciosas feitas via Postman ou cURL.
    if (empty($email) || empty($senha)) {
        $mensagem_erro = "Por favor, preencha o e-mail e a senha.";
    } else {
        try {
            // -------------------------------------------------------------------------
            // 4. SEGURANÇA E PREVENÇÃO DE ATAQUES (SQL INJECTION)
            // -------------------------------------------------------------------------
            // Defesa contra SQL Injection através de Prepared Statements (Consultas Preparadas).
            // O caractere '?' atua como um placeholder. O driver PDO trata o dado estritamente 
            // como uma string literária, impedindo que comandos maliciosos injetados no campo
            // de texto alterem a estrutura lógica da nossa consulta SQL.
            $stmt = $pdo->prepare("SELECT id, nome, email, senha, tipo FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            
            // O fetch() extrai o registro retornado do banco. Como configuramos o PDO para retornar
            // objetos, acessamos os campos usando a sintaxe de seta (->), mantendo o código limpo.
            $usuario = $stmt->fetch();

            // -------------------------------------------------------------------------
            // 5. CRIPTOGRAFIA E AUTENTICAÇÃO SEGURA
            // -------------------------------------------------------------------------
            // As senhas não são salvas em texto limpo no banco. Usamos a função password_verify(),
            // que é o padrão da indústria (algoritmo Bcrypt). Ela extrai o salt embutido no hash do banco 
            // e valida a assinatura da senha digitada de forma segura contra ataques de timing.
            if ($usuario && password_verify($senha, $usuario->senha)) {
                
                // ---------------------------------------------------------------------
                // 6. SESSÃO DO USUÁRIO (PAYLOAD DE AUTENTICAÇÃO)
                // ---------------------------------------------------------------------
                // Armazenamos dados mínimos e não sensíveis na sessão do servidor para 
                // identificar o usuário nas páginas internas de forma performática.
                $_SESSION['usuario_id']   = $usuario->id;
                $_SESSION['usuario_nome'] = $usuario->nome;
                $_SESSION['usuario_tipo'] = $usuario->tipo; // Define as permissões ('paciente', 'medico', 'admin')

                // ---------------------------------------------------------------------
                // 7. ARQUITETURA DE REDIRECIONAMENTO (RN04)
                // ---------------------------------------------------------------------
                // Abordagem SoC (Separação de Conceitos). O sistema identifica o papel (role) 
                // do usuário no ecossistema e despacha o fluxo de navegação para o diretório isolado correspondente.
                if ($usuario->tipo === 'paciente') {
                    header("Location: ../area_paciente/dashboard.php");
                    exit; // O 'exit' bloqueia o carregamento de qualquer código residual após o redirecionamento.
                } elseif ($usuario->tipo === 'medico') {
                    header("Location: ../area_medico/agenda.php");
                    exit;
                } else {
                    header("Location: ../area_admin/painel.php");
                    exit;
                }

            } else {
                // DIRETRIZ DE SEGURANÇA OWASP: Mensagem genérica de erro. 
                // Nunca diga "Senha incorreta" ou "E-mail não cadastrado". Mensagens específicas 
                // auxiliam cibercriminosos em ataques de engenharia social para enumeração de usuários válidos.
                $mensagem_erro = "E-mail ou senha incorretos. Tente novamente.";
            }

        } catch (PDOException $e) {
            // TRATAMENTO DE EXCEÇÕES: Captura falhas críticas de infraestrutura (ex: banco de dados offline)
            // sem expor a Stack Trace original do servidor ao usuário, mitigando vazamento de informações do sistema.
            $mensagem_erro = "Erro no servidor: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunica+ | Aceder à Conta</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* -------------------------------------------------------------------------
           DESIGN SYSTEM CONSTANTS (VARIÁVEIS CSS / TOKENS DE DESIGN)
           -------------------------------------------------------------------------
           Aplicação da identidade visual Clean UI baseada no briefing do projeto: 
           tons de verde voltados à saúde e fonoaudiologia combinados com cinza neutro. */
        :root {
            --verde-escuro: #12382c;    /* Tom de contraste corporativo e alta legibilidade (Acessibilidade) */
            --verde-medio: #1b4d3e;     /* Cor de destaque da marca e ações principais */
            --verde-destaque: #2c7a5f;  /* Cor secundária aplicada em interações de hover e links */
            --cinza-fundo: #f8faf9;     /* Fundo suave que reduz a fadiga visual do usuário */
        }
        
        body {
            /* Degradê linear suave para dar profundidade estética e aspecto Premium/Clean UI */
            background: linear-gradient(135deg, var(--cinza-fundo) 0%, #e2ede8 100%);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #333333;
        }

        .card-login {
            border: none;
            border-radius: 16px; /* Bordas arredondadas que conferem um design moderno e amigável */
            box-shadow: 0 10px 30px rgba(18, 56, 44, 0.06); /* Sombra difusa (Soft Shadow) para simular elevação (Z-index) */
            border-top: 5px solid var(--verde-medio); /* Linha de reforço visual com a cor da marca */
            transition: transform 0.3s ease;
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

        /* CUSTOMIZAÇÃO DE INPUTS (CLEAN DESIGN RESTYLING)
           Remoção do aspecto industrial do Bootstrap nativo para uma estética minimalista. */
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

        /* MICROINTERAÇÕES (FEEDBACK VISUAL AO USUÁRIO)
           Estilo aplicado via JavaScript quando o input recebe foco do teclado/clique */
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
            transform: translateY(-1px); /* Sutil elevação ao passar o mouse, enriquecendo a affordance do botão */
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

        .container-cadastro-link {
            background-color: #fafdff;
            border-top: 1px solid #edf2f7;
            padding: 20px;
            border-bottom-left-radius: 16px;
            border-bottom-right-radius: 16px;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-10 col-sm-8 col-md-6 col-lg-4">
                
                <div class="text-center mb-4">
                    <a href="index.php" class="text-decoration-none brand-logo fs-2">
                        <i class="fa-solid fa-wave-square me-2"></i>Comunica+
                    </a>
                </div>

                <div class="card card-login bg-white overflow-hidden">
                    <div class="p-4 pt-4 pb-2">
                        <h4 class="fw-bold mb-1" style="color: var(--verde-escuro);">Bem-vindo de volta</h4>
                        <p class="text-muted small mb-4">Insira as suas credenciais para aceder à plataforma.</p>

                        <?php if (!empty($mensagem_erro)): ?>
                            <div class="alert alert-danger alert-dismissible fade show border-0 small mb-4" role="alert" style="background-color: #fff5f5; color: #c53030;">
                                <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($mensagem_erro) ?>
                                <button type="button" class="btn-close small" data-bs-dismiss="alert" aria-label="Close" style="font-size: 0.75rem;"></button>
                            </div>
                        <?php endif; ?>

                        <form action="login.php" method="POST">
                            
                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">E-mail Corporativo ou Pessoal</label>
                                <div class="input-group id-group">
                                    <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="nome@exemplo.com" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="senha" class="form-label fw-semibold">Palavra-passe</label>
                                <div class="input-group id-group">
                                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                    <input type="password" class="form-control" id="senha" name="senha" placeholder="••••••••" required>
                                </div>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-verde shadow-sm">Entrar na Plataforma</button>
                            </div>
                        </form>
                    </div>

                    <div class="container-cadastro-link text-center">
                        <span class="text-muted small">Novo na Comunica+?</span>
                        <a href="cadastro.php" class="text-link small ms-1">Criar Conta</a>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="index.php" class="text-muted text-decoration-none small transition"><i class="fa-solid fa-chevron-left me-1" style="font-size: 0.8rem;"></i> Voltar para a Home</a>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // DOM INTERACTION: Adiciona uma classe de foco ao container do grupo de input (.input-group) 
        // sempre que o usuário clicar no campo. Isso permite customizar a borda inteira do componente 
        // (incluindo o ícone), proporcionando uma experiência visual fluida e moderna (Clean UI).
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