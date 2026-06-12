<?php
// =================================================================================
// ARQUIVO: area_paciente/exercicios.php
// PROPÓSITO: Listar APENAS os exercícios prescritos para o paciente logado.
// =================================================================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Controle de acesso
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'paciente') {
    session_destroy();
    header("Location: ../public/login.php");
    exit;
}

require_once '../config/db.php';
$id_paciente_logado = $_SESSION['usuario_id'];
$exercicios_passados = [];
$erro_banco = null;

try {
    // Busca os exercícios vinculados cruzando as tabelas e trazendo o nome do médico
    $sql = "SELECT e.titulo, e.descricao, e.categoria, e.repeticoes, e.duracao, u.nome AS nome_medico
            FROM prescricoes p
            JOIN exercicios e ON p.id_exercicio = e.id
            JOIN usuarios u ON p.id_medico = u.id
            WHERE p.id_paciente = ?
            ORDER BY p.data_prescricao DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_paciente_logado]);
    $exercicios_passados = $stmt->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $e) {
    $erro_banco = "Erro ao carregar seus exercícios personalizados: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunica+ | Meus Exercícios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --verde-escuro: #1b4d3e;
            --verde-medio: #2c7a5f;
            --verde-claro: #f4f9f4;
        }
        body { background-color: #f8f9fa; font-family: 'Segoe UI', system-ui, sans-serif; }
        .navbar-interna { background-color: var(--verde-escuro); }
        .card-exercicio {
            border: none;
            border-radius: 12px;
            border-left: 5px solid var(--verde-medio) !important;
            box-shadow: 0 4px 10px rgba(0,0,0,0.04);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-interna shadow-sm">
        <div class="container">
            <span class="navbar-brand fw-bold"><i class="fa-solid fa-waveform-lines me-2"></i>Comunica+</span>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fa-solid fa-house me-1"></i>Meu Painel</a></li>
                    <li class="nav-item"><a class="nav-link active" href="exercicios.php"><i class="fa-solid fa-music me-1"></i>Meus Exercícios</a></li>
                    <li class="nav-item ms-3">
                        <a class="btn btn-sm btn-outline-danger px-3" href="logout.php"><i class="fa-solid fa-right-from-bracket me-1"></i>Sair</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-5 border-bottom pb-3">
            <div>
                <h2 class="fw-bold text-dark m-0"><i class="fa-solid fa-microphone-lines text-success me-2"></i>Sua Prescrição Fonoaudiológica</h2>
                <p class="text-muted m-0 mt-1">Exercícios selecionados especialmente pelos terapeutas para você.</p>
            </div>
            <a href="dashboard.php" class="btn btn-sm btn-outline-secondary px-3"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a>
        </div>

        <?php if ($erro_banco): ?>
            <div class="alert alert-danger"><?= $erro_banco ?></div>
        <?php endif; ?>

        <div class="row">
            <?php if (empty($exercicios_passados)): ?>
                <div class="col-12 text-center py-5 text-muted">
                    <div class="bg-white p-5 rounded shadow-sm border">
                        <i class="fa-solid fa-notes-medical fs-1 text-success opacity-50 mb-3"></i>
                        <h5>Nenhum exercício prescrito ainda</h5>
                        <p class="small mb-0">O seu fonoaudiólogo passará atividades personalizadas em breve.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($exercicios_passados as $ex): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card card-exercicio p-4 bg-white h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 small fw-semibold"><?= htmlspecialchars($ex->categoria) ?></span>
                                    <small class="text-muted"><i class="fa-solid fa-user-md me-1"></i>Dr(a). <?= htmlspecialchars($ex->nome_medico) ?></small>
                                </div>
                                <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($ex->titulo) ?></h5>
                                <p class="text-secondary small" style="text-align: justify;"><?= nl2br(htmlspecialchars($ex->descricao)) ?></p>
                            </div>

                            <div class="mt-4 pt-3 border-top bg-light p-3 rounded-3 border">
                                <div class="row text-center">
                                    <div class="col-6 border-end">
                                        <span class="text-muted d-block small" style="font-size: 11px;">Repetições</span>
                                        <strong class="text-dark small"><?= htmlspecialchars($ex->repeticoes) ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block small" style="font-size: 11px;">Duração</span>
                                        <strong class="text-dark small"><?= htmlspecialchars($ex->duracao) ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>