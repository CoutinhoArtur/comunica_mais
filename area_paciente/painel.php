<?php
// =================================================================================
// ARQUIVO: area_paciente/painel.php
// PROPÓSITO: Painel do Paciente com Status de Consultas e Guia de Exercícios Vocais.
// REGRA DE NEGÓCIO ATENDIDA: RN04 (Acompanhamento) e Módulo de Reabilitação.
// =================================================================================

// ---------------------------------------------------------------------------------
// 1. CONTROLO DE ACESSO E SEGURANÇA (BACK-END)
// ---------------------------------------------------------------------------------
// Inicia a sessão para capturar os dados do paciente logado
session_start();

// Bloqueio de segurança: Se não for paciente, expulsa o utilizador imediatamente
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'paciente') {
    session_destroy();
    header("Location: ../public/login.php");
    exit;
}

// ---------------------------------------------------------------------------------
// 2. IMPORTAÇÃO DO BANCO DE DADOS E COLETA DE INFORMAÇÕES
// ---------------------------------------------------------------------------------
require_once '../config/db.php';

$id_paciente_logado = $_SESSION['usuario_id'];
$nome_paciente = $_SESSION['usuario_nome'];
$mensagem_erro = "";

$minhas_consultas = [];
$exercicios_clinicos = [];

try {
    // CONSULTA A: Busca todos os agendamentos deste paciente específico
    // Traz o nome do médico que vai atendê-lo fazendo um JOIN com a tabela de usuários
    $sqlConsultas = "SELECT a.data_consulta, a.hora_consulta, a.status, a.observacoes, u_med.nome AS nome_medico
                     FROM agendamentos a
                     JOIN usuarios u_med ON a.id_medico = u_med.id
                     WHERE a.id_paciente = ?
                     ORDER BY a.data_consulta ASC, a.hora_consulta ASC";
    
    $stmtCons = $pdo->prepare($sqlConsultas);
    $stmtCons->execute([$id_paciente_logado]);
    $minhas_consultas = $stmtCons->fetchAll(PDO::FETCH_OBJ);

    // CONSULTA B: Lista de Exercícios Vocais para Reabilitação
    // Como ainda não criámos uma tabela dinâmica de prescrição, vamos listar os exercícios
    // oficiais da clínica Comunica+ para o paciente consultar e praticar em casa.
    $sqlExercicios = "SELECT id, titulo, descricao, categoria, repeticoes, duracao FROM exercicios ORDER BY categoria ASC";
    $exercicios_clinicos = $pdo->query($sqlExercicios)->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $e) {
    $mensagem_erro = "Erro ao carregar dados do seu painel: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunica+ | Espaço do Paciente</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --azul-paciente: #0284c7;
            --azul-escuro: #0c4a6e;
            --azul-fundo: #f0f9ff;
        }
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .navbar-paciente { background-color: var(--azul-escuro); }
        .card-paciente {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .badge-status { font-size: 11px; padding: 5px 10px; border-radius: 20px; }
        .card-exercicio {
            border-left: 4px solid var(--azul-paciente) !important;
            transition: transform 0.2s ease;
        }
        .card-exercicio:hover {
            transform: scale(1.01); /* Leve feedback visual de zoom */
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-paciente shadow-sm">
        <div class="container">
            <span class="navbar-brand fw-bold"><i class="fa-solid fa-house-user me-2"></i>Comunica+ | Área do Paciente</span>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item text-white border-end pe-3 me-3 d-none d-md-block">
                        <i class="fa-solid fa-circle-user me-1 text-info"></i>Olá, <strong><?= htmlspecialchars($nome_paciente) ?></strong>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-outline-light px-3" href="../area_medico/logout_medico.php" onclick="return confirm('Deseja sair da sua conta?');">
                            <i class="fa-solid fa-arrow-right-from-bracket me-1"></i>Sair
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        
        <div class="mb-4">
            <h2 class="fw-bold text-dark">Central de Acompanhamento Vocal</h2>
            <p class="text-muted mb-0">Consulte os horários das suas sessões e siga o cronograma de treinos abaixo.</p>
        </div>

        <?php if (!empty($mensagem_erro)): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-exclamation-triangle me-2"></i> <?= $mensagem_erro ?></div>
        <?php endif; ?>

        <div class="row">
            
            <div class="col-lg-5 mb-4">
                <div class="card card-paciente p-4 bg-white h-100">
                    <h4 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-calendar-check text-info me-2"></i>Minhas Consultas</h4>
                    
                    <?php if (empty($minhas_consultas)): ?>
                        <div class="text-center text-muted py-5 bg-light rounded-3">
                            <i class="fa-solid fa-calendar-xmark fs-2 mb-2 opacity-50"></i>
                            <p class="mb-0 small">Você não possui sessões agendadas.</p>
                        </div>
                    <?php else: ?>
                        <div class="pe-1" style="max-height: 500px; overflow-y: auto;">
                            <?php foreach ($minhas_consultas as $consulta): ?>
                                <div class="p-3 rounded-3 mb-3 border bg-light bg-opacity-50">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold text-dark"><i class="fa-regular fa-clock me-1 text-muted"></i><?= date('H:i', strtotime($consulta->hora_consulta)) ?> - <?= date('d/m/Y', strtotime($consulta->data_consulta)) ?></span>
                                        
                                        <?php 
                                            $cor_badge = "bg-warning text-dark";
                                            if ($consulta->status === 'Confirmada') $cor_badge = "bg-success text-white";
                                            elseif ($consulta->status === 'Cancelada') $cor_badge = "bg-danger text-white";
                                        ?>
                                        <span class="badge badge-status <?= $cor_badge ?>"><?= $consulta->status ?></span>
                                    </div>
                                    <p class="mb-1 small"><strong>Profissional:</strong> <?= htmlspecialchars($consulta->nome_medico) ?></p>
                                    
                                    <div class="bg-white p-2 rounded border-start border-3 border-info mt-2 small text-muted">
                                        <i class="fa-solid fa-comment-dots me-1 text-info"></i>
                                        <strong>Minhas Notas:</strong> <?= !empty($consulta->observacoes) ? htmlspecialchars($consulta->observacoes) : 'Nenhuma nota registrada.' ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-7 mb-4">
                <div class="card card-paciente p-4 bg-white h-100">
                    <h4 class="fw-bold mb-1 text-dark"><i class="fa-solid fa-microphone-lines text-primary me-2"></i>Academia da Voz</h4>
                    <p class="text-muted small mb-4">Siga as instruções abaixo diariamente para melhorar a sua performance vocal.</p>

                    <?php if (empty($exercicios_clinicos)): ?>
                        <div class="alert alert-info py-3 small">
                            <i class="fa-solid fa-circle-info me-2"></i>Nenhum exercício cadastrado no sistema neste momento.
                        </div>
                    <?php else: ?>
                        <?php foreach ($exercicios_clinicos as $ex): ?>
                            <div class="card card-paciente card-exercicio p-3 mb-3 bg-white border border-light">
                                <div class="d-flex justify-content-between align-items-start border-bottom pb-2 mb-2">
                                    <div>
                                        <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($ex->titulo) ?></h5>
                                        <span class="badge bg-secondary rounded-pill mt-1" style="font-size: 10px;"><?= htmlspecialchars($ex->categoria) ?></span>
                                    </div>
                                    <div class="text-end small bg-light p-2 rounded border">
                                        <div class="fw-bold text-primary"><i class="fa-solid fa-rotate me-1"></i><?= htmlspecialchars($ex->repeticoes) ?></div>
                                        <div class="text-muted font-monospace" style="font-size: 11px;"><i class="fa-regular fa-clock me-1"></i><?= htmlspecialchars($ex->duracao) ?></div>
                                    </div>
                                </div>
                                <p class="mb-0 small text-secondary" style="text-align: justify;"><?= nl2br(htmlspecialchars($ex->descricao)) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>