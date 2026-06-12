<?php
// =================================================================================
// ARQUIVO: area_medico/historico_paciente.php
// PROPÓSITO: Visualização limpa e completa do histórico clínico de um paciente.
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
$id_paciente = filter_input(INPUT_GET, 'id_paciente', FILTER_SANITIZE_NUMBER_INT);

if (!$id_paciente) {
    header("Location: agenda.php");
    exit;
}

try {
    // 1. Puxa os dados básicos do paciente consultado
    $stmtPac = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = ?");
    $stmtPac->execute([$id_paciente]);
    $paciente = $stmtPac->fetch(PDO::FETCH_OBJ);

    if (!$paciente) {
        header("Location: agenda.php");
        exit;
    }

    // 2. Busca todas as consultas já finalizadas desse paciente
   // 2. Busca todas as consultas já finalizadas desse paciente (Corrigido para sql_mode=only_full_group_by)
    $sqlHistorico = "SELECT a.data_consulta, a.hora_consulta, a.observacoes, e.titulo AS exercicio_prescrito, e.categoria AS exercicio_categoria
                     FROM agendamentos a
                     LEFT JOIN prescricoes p ON p.id_paciente = a.id_paciente AND DATE(p.data_prescricao) = a.data_consulta
                     LEFT JOIN exercicios e ON p.id_exercicio = e.id
                     WHERE a.id_paciente = ? AND a.status = 'Realizada'
                     ORDER BY a.data_consulta DESC, a.hora_consulta DESC";
                     
    $stmtHist = $pdo->prepare($sqlHistorico);
    $stmtHist->execute([$id_paciente]);
    $historico = $stmtHist->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $e) {
    die("Erro ao carregar prontuário histórico: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunica+ | Prontuário Histórico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --verde-escuro: #1b4d3e; --verde-medio: #2c7a5f; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .navbar-medico { background-color: var(--verde-escuro); }
        .card-timeline { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .timeline-container { border-left: 3px solid var(--verde-medio); padding-left: 25px; position: relative; margin-left: 10px; }
        .timeline-dot {
            position: absolute;
            left: -10px;
            top: 5px;
            width: 17px;
            height: 17px;
            background-color: var(--verde-medio);
            border: 3px solid #f4f7f6;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-medico shadow-sm">
        <div class="container">
            <span class="navbar-brand fw-bold"><i class="fa-solid fa-clock-rotate-left me-2"></i>Histórico do Paciente</span>
            <a href="agenda.php" class="btn btn-sm btn-outline-light"><i class="fa-solid fa-arrow-left me-1"></i>Voltar à Agenda</a>
        </div>
    </nav>

    <div class="container my-5" style="max-width: 900px;">
        
        <div class="card card-timeline p-4 bg-white mb-5 border-top border-4 border-success">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <span class="text-uppercase tracking-wider text-muted small fw-bold">Prontuário Clínico Integrado</span>
                    <h3 class="fw-bold text-dark m-0 mt-1"><?= htmlspecialchars($paciente->nome) ?></h3>
                    <p class="text-secondary m-0 mt-1 small"><i class="fa-solid fa-envelope me-1"></i> <?= htmlspecialchars($paciente->email) ?></p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <span class="badge bg-success p-2 px-3 rounded-pill fs-6">
                        <?= count($historico) ?> Sessão(ões)
                    </span>
                </div>
            </div>
        </div>

        <h4 class="fw-bold text-dark mb-4"><i class="fa-solid fa-stream text-success me-2"></i>Evolução Temporal</h4>

        <?php if (empty($historico)): ?>
            <div class="card card-timeline p-5 text-center bg-white border">
                <i class="fa-solid fa-folder-open text-muted fs-1 mb-3 opacity-50"></i>
                <h5 class="text-muted m-0">Nenhum registro clínico antigo encontrado.</h5>
                <p class="text-muted small m-0 mt-1">As anotações aparecerão aqui assim que a primeira consulta for finalizada.</p>
            </div>
        <?php else: ?>
            <div class="position-relative">
                <?php foreach ($historico as $item): ?>
                    <div class="timeline-container mb-5">
                        <div class="timeline-dot"></div>
                        
                        <div class="card card-timeline p-4 bg-white border">
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                <span class="fw-bold text-dark fs-5">
                                    <i class="fa-solid fa-calendar-day me-2 text-secondary"></i>Atendimento em <?= date('d/m/Y', strtotime($item->data_consulta)) ?>
                                </span>
                                <span class="badge bg-light text-dark border px-3 py-2"><i class="fa-solid fa-clock me-1 text-muted"></i><?= date('H:i', strtotime($item->hora_consulta)) ?></span>
                            </div>

                            <div class="mb-3">
                                <strong class="text-secondary d-block small mb-2">Evolução e Parecer Fonoaudiológico:</strong>
                                <p class="text-dark bg-light p-3 rounded-3 border m-0" style="text-align: justify; white-space: pre-wrap; font-size: 14px; line-height: 1.6;"><?= htmlspecialchars($item->observacoes) ?></p>
                            </div>

                            <?php if ($item->exercicio_prescrito): ?>
                                <div class="bg-success bg-opacity-10 border border-success border-opacity-20 rounded-3 p-3 mt-3">
                                    <div class="d-flex align-items-center text-success small fw-bold mb-1">
                                        <i class="fa-solid fa-music me-2"></i>Treino de Suporte Prescrito:
                                    </div>
                                    <span class="badge bg-success text-white px-2 py-1 rounded me-1 small" style="font-size: 11px;"><?= htmlspecialchars($item->exercicio_categoria) ?></span>
                                    <span class="text-dark fw-semibold small"><?= htmlspecialchars($item->exercicio_prescrito) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>