<?php
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
$id_medico_logado = $_SESSION['usuario_id'];
$consultas = [];
$msg_status = "";

if (isset($_GET['sucesso'])) $msg_status = "<div class='alert alert-success shadow-sm'><i class='fa-solid fa-circle-check me-2'></i>Consulta finalizada e exercício enviado!</div>";
if (isset($_GET['erro'])) $msg_status = "<div class='alert alert-danger shadow-sm'><i class='fa-solid fa-circle-exclamation me-2'></i>Erro: " . htmlspecialchars($_GET['erro']) . "</div>";

try {
    $sql = "SELECT a.id AS id_agendamento, a.data_consulta, a.hora_consulta, a.status, u.id AS id_paciente, u.nome AS nome_paciente 
            FROM agendamentos a
            JOIN usuarios u ON a.id_paciente = u.id
            WHERE a.id_medico = ?
            ORDER BY a.data_consulta DESC, a.hora_consulta DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_medico_logado]);
    $consultas = $stmt->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    $msg_status = "<div class='alert alert-danger'>Erro no banco de dados: " . $e->getMessage() . "</div>";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunica+ | Agenda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --verde-escuro: #1b4d3e; }
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .navbar-medico { background-color: var(--verde-escuro); }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-medico shadow-sm">
        <div class="container">
            <span class="navbar-brand fw-bold"><i class="fa-solid fa-user-md me-2"></i>Comunica+ | Médico</span>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="btn btn-sm btn-outline-danger px-3" href="logout_medico.php">Sair</a></li>
            </ul>
        </div>
    </nav>

    <div class="container my-5">
        <div class="mb-4">
            <h2 class="fw-bold text-dark"><i class="fa-solid fa-calendar-days text-success me-2"></i>Agenda de Atendimentos</h2>
            <p class="text-muted">Inicie novos atendimentos ou consulte prontuários e evoluções anteriores.</p>
        </div>

        <?= $msg_status ?>

        <div class="card p-4 bg-white border-0 shadow-sm">
            <?php if (empty($consultas)): ?>
                <p class="text-muted text-center my-4">Nenhuma consulta agendada para você no momento.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Paciente</th>
                                <th>Data</th>
                                <th>Horário</th>
                                <th>Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($consultas as $c): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($c->nome_paciente) ?></td>
                                    <td><?= date('d/m/Y', strtotime($c->data_consulta)) ?></td>
                                    <td><?= date('H:i', strtotime($c->hora_consulta)) ?></td>
                                    <td>
                                        <span class="badge <?= $c->status === 'Realizada' ? 'bg-success' : 'bg-secondary' ?> rounded-pill p-2 px-3">
                                            <?= htmlspecialchars($c->status) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-2">
                                            <?php if ($c->status === 'Realizada'): ?>
                                                <button class="btn btn-sm btn-outline-secondary px-2" disabled><i class="fa-solid fa-check"></i> Finalizado</button>
                                            <?php else: ?>
                                                <a href="consulta.php?id=<?= $c->id_agendamento ?>" class="btn btn-sm btn-primary px-3 fw-semibold shadow-sm">
                                                    <i class="fa-solid fa-stethoscope me-1"></i> Atender
                                                </a>
                                            <?php endif; ?>

                                            <a href="historico_paciente.php?id_paciente=<?= $c->id_paciente ?>" class="btn btn-sm btn-outline-info px-3 fw-semibold">
                                                <i class="fa-solid fa-clock-rotate-left"></i> Histórico
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>