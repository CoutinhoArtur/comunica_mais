<?php
// =================================================================================
// ARQUIVO: area_paciente/dashboard.php
// PROPÓSITO: Painel principal e restrito do paciente (Pós-Login).
// REGRA DE NEGÓCIO ATENDIDA: RN07 - Bloqueio de acesso para utilizadores não logados.
// =================================================================================

// ---------------------------------------------------------------------------------
// 1. CONTROLO DE ACESSO E SEGURANÇA (VERIFICAÇÃO DE SESSÃO)
// ---------------------------------------------------------------------------------
// Inicia a sessão para conseguir ler as variáveis gravadas no 'login.php'
session_start();

// Verifica se a variável de sessão 'usuario_id' NÃO está definida ou se o tipo NÃO é paciente.
// Se o utilizador tentar forçar a entrada digitando a URL direta no navegador, ele é barrado.
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'paciente') {
    // Destrói qualquer resto de sessão limpa que possa existir por segurança
    session_destroy();
    // Redireciona o invasor imediatamente de volta para a tela de login pública
    header("Location: ../public/login.php");
    exit; // Interrompe o script para que o código abaixo não seja executado
}

// ---------------------------------------------------------------------------------
// 2. CONEXÃO AO BANCO DE DADOS PARA BUSCAR CONSULTAS DO PACIENTE
// ---------------------------------------------------------------------------------
require_once '../config/db.php';

// Captura o ID do paciente que está logado na sessão atual
$id_paciente_logado = $_SESSION['usuario_id'];

try {
    // Prepara a consulta SQL para buscar os agendamentos específicos DESTE paciente.
    // Fazemos um JOIN com a tabela 'usuarios' através do ID do médico para exibir o NOME do fonoaudiólogo,
    // e ordenamos pelas consultas mais recentes.
    $sqlConsultas = "SELECT a.id, a.data_consulta, a.hora_consulta, a.status, u.nome AS nome_medico 
                     FROM agendamentos a
                     JOIN usuarios u ON a.id_medico = u.id
                     WHERE a.id_paciente = ?
                     ORDER BY a.data_consulta DESC, a.hora_consulta DESC";
    
    $stmt = $pdo->prepare($sqlConsultas);
    $stmt->execute([$id_paciente_logado]);
    
    // Armazena todas as consultas encontradas num array de objetos
    $consultas = $stmt->fetchAll();

} catch (PDOException $e) {
    $erro_banco = "Não foi possível carregar os seus agendamentos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunica+ | Painel do Paciente</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --verde-escuro: #1b4d3e;
            --verde-medio: #2c7a5f;
            --verde-claro: #f4f9f4;
        }
        body {
            background-color: #f8f9fa; /* Fundo cinza claro para destacar os blocos brancos (UX) */
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        /* Estilização da Barra de Navegação Interna */
        .navbar-interna {
            background-color: var(--verde-escuro);
        }
        /* Cartão de Boas-Vindas Personalizado */
        .card-welcome {
            background: linear-gradient(135deg, var(--verde-escuro), var(--verde-medio));
            color: #ffffff;
            border: none;
            border-radius: 12px;
        }
        /* Badges de Status Personalizadas para as Consultas */
        .badge-agendada { background-color: #ffc107; color: #000; }     /* Amarelo */
        .badge-confirmada { background-color: #198754; color: #fff; }   /* Verde */
        .badge-atendimento { background-color: #0dcaf0; color: #000; }  /* Azul */
        .badge-finalizada { background-color: #6c757d; color: #fff; }   /* Cinza */
        .badge-cancelada { background-color: #dc3545; color: #fff; }    /* Vermelho */
        
        .btn-acao {
            background-color: var(--verde-medio);
            color: white;
            font-weight: 600;
        }
        .btn-acao:hover {
            background-color: var(--verde-escuro);
            color: white;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-interna shadow-sm">
        <div class="container">
            <span class="navbar-brand fw-bold"><i class="fa-solid fa-waveform-lines me-2"></i>Comunica+</span>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavInterno">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavInterno">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link active" href="#"><i class="fa-solid fa-house me-1"></i>Meu Painel</a></li>
                    <li class="nav-item"><a class="nav-link" href="agendar.php"><i class="fa-solid fa-calendar-plus me-1"></i>Novo Agendamento</a></li>
                    <li class="nav-item"><a class="nav-link" href="exercicios.php"><i class="fa-solid fa-music me-1"></i>Meus Exercícios</a></li>
                    <li class="nav-item ms-3">
                        <a class="btn btn-sm btn-outline-danger px-3" href="logout.php" onclick="return confirmarSaida(event);"> <i class="fa-solid fa-right-from-bracket me-1"></i>Sair</a>
    
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        
        <div class="card card-welcome p-4 mb-4 shadow-sm">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="fw-bold mb-1">Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?>!</h2>
                    <p class="mb-0 opacity-75">Seja bem-vindo à sua central de fonoaudiologia. Aqui pode gerir as suas marcações e rever as suas atividades vocais.</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="agendar.php" class="btn btn-light text-success fw-bold px-4 py-2 shadow-sm"><i class="fa-solid fa-calendar-check me-2"></i>Marcar Consulta</a>
                </div>
            </div>
        </div>

        <div class="row">
            
            <div class="col-lg-8 mb-4">
                <div class="card p-4 bg-white border-0 shadow-sm h-100">
                    <h4 class="fw-bold mb-3" style="color: var(--verde-escuro);"><i class="fa-solid fa-clock-history me-2"></i>A minha Agenda de Consultas</h4>
                    
                    <?php if (isset($erro_banco)): ?>
                        <div class="alert alert-danger"><?= $erro_banco ?></div>
                    <?php elseif (empty($consultas)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fa-solid fa-calendar-xmark fs-1 mb-2 text-opacity-25"></i>
                            <p class="mb-0">Ainda não tem nenhuma consulta marcada no sistema.</p>
                            <a href="agendar.php" class="text-success small fw-bold">Marque a sua primeira consulta agora</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fonoaudiólogo(a)</th>
                                        <th>Data</th>
                                        <th>Horário</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($consultas as $consulta): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= htmlspecialchars($consulta->nome_medico) ?></td>
                                            <td><?= date('d/m/Y', strtotime($consulta->data_consulta)) ?></td>
                                            <td><?= date('H:i', strtotime($consulta->hora_consulta)) ?></td>
                                            <td>
                                                <?php 
                                                    $classe_badge = "badge-agendada";
                                                    if ($consulta->status === 'Confirmada') $classe_badge = "badge-confirmada";
                                                    elseif ($consulta->status === 'Em Atendimento') $classe_badge = "badge-atendimento";
                                                    elseif ($consulta->status === 'Finalizada') $classe_badge = "badge-finalizada";
                                                    elseif ($consulta->status === 'Cancelada') $classe_badge = "badge-cancelada";
                                                ?>
                                                <span class="badge <?= $classe_badge ?> p-2 px-3 rounded-pill"><?= $consulta->status ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="card p-4 bg-white border-0 shadow-sm h-100 d-flex flex-column justify-content-between">
                    <div>
                        <h4 class="fw-bold mb-3" style="color: var(--verde-escuro);"><i class="fa-solid fa-music me-2"></i>Treino Vocal</h4>
                        <p class="text-muted small">Aceda à lista de exercícios personalizados que os terapeutas da Comunica+ prescreveram para praticar em casa e melhorar a sua performance musical.</p>
                    </div>
                    <div class="d-grid mt-3">
                        <a href="exercicios.php" class="btn btn-acao p-2 py-2 shadow-sm"><i class="fa-solid fa-sliders me-2"></i>Ver Meus Exercícios</a>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
function confirmarSaida(event) {
    // block o comportamento padrão do link (que seria ir direto para o logout.php)
    event.preventDefault();
    
    // Abre uma caixa de diálogo nativa do navegador pedindo a confirmação
    let confirmar = confirm("Tem certeza que deseja encerrar sua sessão no Comunica+?");
    
    // Se o usuário clicar em "OK", o JavaScript redireciona para o arquivo de logout
    if (confirmar) {
        window.location.href = "logout.php";
    }
    // Se clicar em "Cancelar", a função não faz nada e o paciente continua no painel
}
</script>
</body>
</html>