<?php
// =================================================================================
// ARQUIVO: area_medico/consulta.php
// PROPÓSITO: Prontuário eletrônico unificado com Histórico de Consultas Anteriores.
// =================================================================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'medico') {
    session_destroy();
    header("Location: ../public/login.php");
    exit;
}

require_once '../config/db.php';
$id_agendamento = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$id_agendamento) {
    header("Location: agenda.php");
    exit;
}

$historico_consultas = [];

try {
    // 1. Busca os dados consolidados do paciente e do agendamento atual
    $sqlConsulta = "SELECT a.id AS id_agendamento, a.data_consulta, a.hora_consulta, 
                           u.id AS id_paciente, u.nome AS nome_paciente, u.email AS email_paciente
                    FROM agendamentos a
                    JOIN usuarios u ON a.id_paciente = u.id
                    WHERE a.id = ?";
    
    $stmt = $pdo->prepare($sqlConsulta);
    $stmt->execute([$id_agendamento]);
    $dadosAtendimento = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$dadosAtendimento) {
        header("Location: agenda.php");
        exit;
    }

    // 2. Coleta os exercícios de fonoaudiologia da tabela base para exibir no SELECT
    $exercicios = $pdo->query("SELECT id, titulo, categoria FROM exercicios ORDER BY categoria ASC")->fetchAll(PDO::FETCH_OBJ);

    // 3. NOVA QUERY: Busca o histórico completo de consultas passadas DESTE paciente
    // Traz o relatório clínico e o nome do exercício prescrito na época (se houver)
    // 3. Busca o histórico completo de consultas passadas DESTE paciente (Corrigido para sql_mode=only_full_group_by)
    $sqlHistorico = "SELECT a.data_consulta, a.hora_consulta, a.observacoes, e.titulo AS exercicio_prescrito, e.categoria AS exercicio_categoria
                     FROM agendamentos a
                     LEFT JOIN prescricoes p ON p.id_paciente = a.id_paciente AND DATE(p.data_prescricao) = a.data_consulta
                     LEFT JOIN exercicios e ON p.id_exercicio = e.id
                     WHERE a.id_paciente = ? AND a.status = 'Realizada' AND a.id != ?
                     ORDER BY a.data_consulta DESC, a.hora_consulta DESC";
                     
    $stmtHist = $pdo->prepare($sqlHistorico);
    $stmtHist->execute([$dadosAtendimento->id_paciente, $id_agendamento]);
    $historico_consultas = $stmtHist->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $e) {
    die("Erro no carregamento clínico: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunica+ | Prontuário</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --verde-escuro: #1b4d3e; --verde-medio: #2c7a5f; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .navbar-medico { background-color: var(--verde-escuro); }
        .card-prontuario { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .timeline-item { border-left: 3px solid var(--verde-medio); padding-left: 20px; position: relative; }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 4px;
            width: 13px;
            height: 13px;
            background-color: var(--verde-medio);
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-medico shadow-sm">
        <div class="container">
            <span class="navbar-brand fw-bold"><i class="fa-solid fa-stethoscope me-2"></i>Prontuário de Atendimento Clínico</span>
            <a href="agenda.php" class="btn btn-sm btn-outline-light">Voltar à Agenda</a>
        </div>
    </nav>

    <div class="container my-5">
        <form action="finalizar_consulta.php" method="POST">
            <input type="hidden" name="id_agendamento" value="<?= $dadosAtendimento->id_agendamento ?>">
            <input type="hidden" name="id_paciente" value="<?= $dadosAtendimento->id_paciente ?>">

            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card card-prontuario p-4 bg-white sticky-top" style="top: 20px; z-index: 10;">
                        <div class="text-center mb-4">
                            <h5 class="fw-bold text-dark m-0"><?= htmlspecialchars($dadosAtendimento->nome_paciente) ?></h5>
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 mt-1 small">Paciente Identificado</span>
                        </div>
                        <div class="mb-3 border-bottom pb-2">
                            <small class="text-muted d-block">ID Cadastral:</small>
                            <strong class="text-dark">#<?= $dadosAtendimento->id_paciente ?></strong>
                        </div>
                        <div class="mb-3 border-bottom pb-2">
                            <small class="text-muted d-block">E-mail de Contato:</small>
                            <strong class="text-dark text-break"><?= htmlspecialchars($dadosAtendimento->email_paciente) ?></strong>
                        </div>
                        <div>
                            <small class="text-muted d-block">Data da Sessão Atual:</small>
                            <strong class="text-dark"><?= date('d/m/Y', strtotime($dadosAtendimento->data_consulta)) ?> às <?= date('H:i', strtotime($dadosAtendimento->hora_consulta)) ?></strong>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8 mb-4">
                    
                    <div class="card card-prontuario p-4 bg-white mb-4">
                        <h4 class="fw-bold text-dark mb-4"><i class="fa-solid fa-file-waveform text-success me-2"></i>Consulta Atual</h4>
                        
                        <div class="mb-4">
                            <label for="observacoes" class="form-label fw-bold text-secondary">Anotações Clínicas e Evolução:</label>
                            <textarea name="observacoes" id="observacoes" rows="5" class="form-control" placeholder="Descreva os sintomas coletados e o andamento clínico nesta consulta..." required></textarea>
                        </div>

                        <hr class="my-4">

                        <div class="mb-3">
                            <label for="id_exercicio" class="form-label fw-bold text-secondary"><i class="fa-solid fa-music text-success me-1"></i>Prescrever Exercício Home Care:</label>
                            <select name="id_exercicio" id="id_exercicio" class="form-select" required>
                                <option value="">-- Selecione uma técnica vocal cadastrada --</option>
                                <?php foreach ($exercicios as $ex): ?>
                                    <option value="<?= $ex->id ?>">[<?= htmlspecialchars($ex->categoria) ?>] <?= htmlspecialchars($ex->titulo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                            <a href="agenda.php" class="btn btn-light btn-sm px-4">Voltar</a>
                            <button type="submit" class="btn btn-success btn-sm px-4 fw-bold shadow-sm">Concluir Consulta e Liberar Treino</button>
                        </div>
                    </div>

                    <div class="card card-prontuario p-4 bg-white">
                        <h4 class="fw-bold text-dark mb-4"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>Histórico do Paciente</h4>
                        
                        <?php if (empty($historico_consultas)): ?>
                            <p class="text-muted small my-2"><i class="fa-solid fa-info-circle me-1"></i>Este paciente não possui consultas finalizadas anteriores no sistema.</p>
                        <?php else: ?>
                            <div class="pe-2" style="max-height: 450px; overflow-y: auto;">
                                <?php foreach ($historico_consultas as $hist): ?>
                                    <div class="timeline-item mb-4 pb-2">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong class="text-dark"><i class="fa-solid fa-calendar-day me-1 text-secondary"></i> Sessão de <?= date('d/m/Y', strtotime($hist->data_consulta)) ?></strong>
                                            <span class="badge bg-light text-dark border small"><?= date('H:i', strtotime($hist->hora_consulta)) ?></span>
                                        </div>
                                        
                                        <p class="text-secondary small bg-light p-3 rounded-3 border mb-2" style="text-align: justify; white-space: pre-wrap;"><?= htmlspecialchars($hist->observacoes) ?></p>
                                        
                                        <?php if ($hist->exercicio_prescrito): ?>
                                            <div class="text-muted card p-2 bg-white border border-dashed d-inline-block w-100" style="font-size: 12px;">
                                                <i class="fa-solid fa-music text-success me-1"></i> <strong>Exercício Indicado:</strong> 
                                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 me-1">[<?= htmlspecialchars($hist->exercicio_categoria) ?>]</span>
                                                <?= htmlspecialchars($hist->exercicio_prescrito) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </form>
    </div>
</body>
</html>