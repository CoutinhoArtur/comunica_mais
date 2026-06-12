<?php
// =================================================================================
// ARQUIVO: area_admin/painel.php (Refatorado / Auditoria Avançada)
// PROPÓSITO: Painel Administrativo com Métricas Globais e Detalhamento de Usuários.
// REGRA DE NEGÓCIO ATENDIDA: RN07 (Nível de Acesso Master e Auditoria Geral).
// =================================================================================

// ---------------------------------------------------------------------------------
// 1. CONTROLO DE ACESSO E SEGURANÇA (BACK-END)
// ---------------------------------------------------------------------------------
session_start();

// Bloqueio de segurança: Se não for administrador, expulsa o utilizador imediatamente
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    session_destroy();
    header("Location: ../public/login.php");
    exit;
}

// ---------------------------------------------------------------------------------
// 2. IMPORTAÇÃO DO BANCO DE DADOS E LEITURA DE MÉTRICAS
// ---------------------------------------------------------------------------------
require_once '../config/db.php';

$mensagem_erro = "";
$total_pacientes = 0;
$total_medicos = 0;
$total_consultas = 0;
$lista_usuarios = [];

// Variáveis para a Lógica de Detalhamento Individual (Via Parâmetro GET)
$usuario_selecionado = null;
$atividades_vinculadas = [];
$id_usuario_clicado = isset($_GET['ver_usuario']) ? intval($_GET['ver_usuario']) : 0;

try {
    // MÉTRICA 1: Conta quantos usuários possuem o tipo 'paciente'
    $sqlPacientes = "SELECT COUNT(*) AS total FROM usuarios WHERE tipo = 'paciente'";
    $total_pacientes = $pdo->query($sqlPacientes)->fetch(PDO::FETCH_OBJ)->total;

    // MÉTRICA 2: Conta quantos usuários possuem o tipo 'medico'
    $sqlMedicos = "SELECT COUNT(*) AS total FROM usuarios WHERE tipo = 'medico'";
    $total_medicos = $pdo->query($sqlMedicos)->fetch(PDO::FETCH_OBJ)->total;

    // MÉTRICA 3: Conta o volume total de agendamentos registrados no sistema
    $sqlConsultas = "SELECT COUNT(*) AS total FROM agendamentos";
    $total_consultas = $pdo->query($sqlConsultas)->fetch(PDO::FETCH_OBJ)->total;

    // LISTAGEM GERAL: Busca todos os usuários do sistema para exibição na tabela gerencial
    $sqlLista = "SELECT id, nome, email, tipo, data_criacao FROM usuarios ORDER BY tipo ASC, nome ASC";
    $lista_usuarios = $pdo->query($sqlLista)->fetchAll(PDO::FETCH_OBJ);

    // -------------------------------------------------------------------------
    // 3. NOVO BLOCO LÓGICO: DETALHAMENTO DO USUÁRIO SELECIONADO
    // -------------------------------------------------------------------------
    if ($id_usuario_clicado > 0) {
        // Passo A: Busca os dados básicos na tabela genérica de usuários
        $sqlUser = "SELECT id, nome, email, tipo, data_criacao FROM usuarios WHERE id = ?";
        $stmtUser = $pdo->prepare($sqlUser);
        $stmtUser->execute([$id_usuario_clicado]);
        $usuario_selecionado = $stmtUser->fetch(PDO::FETCH_OBJ);

        if ($usuario_selecionado) {
            // Passo B: Verifica o tipo do usuário clicado para carregar dados complementares específicos
            if ($usuario_selecionado->tipo === 'paciente') {
                // Se for paciente, busca dados da tabela 'pacientes' e suas consultas associadas
                $sqlPacData = "SELECT cpf_sus, telefone FROM pacientes WHERE id = ?";
                $stmtPacData = $pdo->prepare($sqlPacData);
                $stmtPacData->execute([$id_usuario_clicado]);
                $usuario_selecionado->meta = $stmtPacData->fetch(PDO::FETCH_OBJ);

                // Carrega o histórico de consultas deste paciente
                $sqlPacConsultas = "SELECT a.data_consulta, a.hora_consulta, a.status, u_med.nome AS nome_profissional
                                    FROM agendamentos a
                                    JOIN usuarios u_med ON a.id_medico = u_med.id
                                    WHERE a.id_paciente = ?
                                    ORDER BY a.data_consulta DESC";
                $stmtPacConsultas = $pdo->prepare($sqlPacConsultas);
                $stmtPacConsultas->execute([$id_usuario_clicado]);
                $atividades_vinculadas = $stmtPacConsultas->fetchAll(PDO::FETCH_OBJ);

            } elseif ($usuario_selecionado->tipo === 'medico') {
                // Se for médico, busca as consultas que estão na agenda sob a responsabilidade dele
                $sqlMedConsultas = "SELECT a.data_consulta, a.hora_consulta, a.status, u_pac.nome AS nome_paciente
                                    FROM agendamentos a
                                    JOIN usuarios u_pac ON a.id_paciente = u_pac.id
                                    WHERE a.id_medico = ?
                                    ORDER BY a.data_consulta ASC";
                $stmtMedConsultas = $pdo->prepare($sqlMedConsultas);
                $stmtMedConsultas->execute([$id_usuario_clicado]);
                $atividades_vinculadas = $stmtMedConsultas->fetchAll(PDO::FETCH_OBJ);
            }
        }
    }

} catch (PDOException $e) {
    $mensagem_erro = "Erro crítico ao carregar dados do painel: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunica+ | Administração & Auditoria</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --azul-admin: #1e3a8a;
            --azul-claro: #eff6ff;
            --cinza-texto: #4b5563;
        }
        body {
            background-color: #f3f4f6;
            font-family: 'Segoe UI', system-ui, sans-serif;
            color: var(--cinza-texto);
        }
        .navbar-admin { background-color: var(--azul-admin); }
        .card-indicador { border: none; border-radius: 12px; }
        .link-usuario {
            color: var(--azul-admin);
            text-decoration: none;
            font-weight: 600;
        }
        .link-usuario:hover {
            text-decoration: underline;
        }
        .card-auditoria {
            border: none;
            border-radius: 12px;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-admin shadow-sm">
        <div class="container">
            <span class="navbar-brand fw-bold"><i class="fa-solid fa-screwdriver-wrench me-2"></i>Comunica+ | Painel Administrativo Geral</span>
            <div class="collapse navbar-collapse" id="navbarNavAdmin">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link active" href="painel.php"><i class="fa-solid fa-chart-pie me-1"></i>Visão Geral</a></li>
                    <li class="nav-item ms-3">
                        <a class="btn btn-sm btn-outline-light px-3" href="../area_medico/logout_medico.php" onclick="return confirm('Deseja encerrar a sessão administrativa?');">
                            <i class="fa-solid fa-power-off me-1"></i>Sair
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold text-dark mb-1">Métricas de Desempenho Clínico</h2>
                <p class="text-muted mb-0">Controle de acessos, auditoria avançada de registros e fluxo operacional.</p>
            </div>
            <span class="badge bg-dark px-3 py-2 fs-6 rounded-pill">Modo Master</span>
        </div>

        <?php if (!empty($mensagem_erro)): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i> <?= $mensagem_erro ?></div>
        <?php endif; ?>

        <div class="row mb-5">
            <div class="col-md-4 mb-3">
                <div class="card card-indicador p-4 bg-white shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-uppercase tracking-wider text-muted small fw-bold">Pacientes</span>
                            <h3 class="fw-bold text-dark m-0 mt-1"><?= $total_pacientes ?></h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3">
                            <i class="fa-solid fa-hospital-user fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card card-indicador p-4 bg-white shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-uppercase tracking-wider text-muted small fw-bold">Profissionais</span>
                            <h3 class="fw-bold text-dark m-0 mt-1"><?= $total_medicos ?></h3>
                        </div>
                        <div class="bg-success bg-opacity-10 text-success p-3 rounded-3">
                            <i class="fa-solid fa-user-md fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card card-indicador p-4 bg-white shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-uppercase tracking-wider text-muted small fw-bold">Agendamentos Totais</span>
                            <h3 class="fw-bold text-dark m-0 mt-1"><?= $total_consultas ?></h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-3">
                            <i class="fa-solid fa-notes-medical fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            
            <div class="<?= $usuario_selecionado ? 'col-lg-7' : 'col-12' ?> mb-4">
                <div class="card border-0 shadow-sm rounded-3 bg-white p-4 h-100">
                    <h4 class="fw-bold text-dark mb-2"><i class="fa-solid fa-users-gear text-secondary me-2"></i>Gerenciamento de Contas</h4>
                    <p class="text-muted small mb-4">Clique no <strong>nome do usuário</strong> para auditar e extrair o dossiê detalhado do banco de dados.</p>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nome Completo</th>
                                    <th>Nível / Tipo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lista_usuarios as $usr): ?>
                                    <tr class="<?= $id_usuario_clicado == $usr->id ? 'table-primary' : '' ?>">
                                        <td class="font-monospace small">#<?= $usr->id ?></td>
                                        <td>
                                            <a href="painel.php?ver_usuario=<?= $usr->id ?>" class="link-usuario">
                                                <?= htmlspecialchars($usr->nome) ?>
                                            </a>
                                            <div class="text-muted small" style="font-size: 11px;"><?= htmlspecialchars($usr->email) ?></div>
                                        </td>
                                        <td>
                                            <?php if ($usr->tipo === 'admin'): ?>
                                                <span class="badge bg-dark rounded-pill">Admin</span>
                                            <?php elseif ($usr->tipo === 'medico'): ?>
                                                <span class="badge bg-success rounded-pill">Fono</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary rounded-pill">Paciente</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php if ($usuario_selecionado): ?>
                <div class="col-lg-5 mb-4">
                    <div class="card card-auditoria p-4 bg-white shadow-sm border border-primary border-opacity-25">
                        
                        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                            <h4 class="fw-bold m-0 text-primary"><i class="fa-solid fa-magnifying-glass-chart me-2"></i>Auditoria Master</h4>
                            <a href="painel.php" class="btn-close" aria-label="Fechar Painel"></a>
                        </div>

                        <div class="p-3 bg-light rounded-3 mb-4 small">
                            <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-address-card me-1"></i> Metadados do Cadastro</h6>
                            <p class="mb-1"><strong>Nome:</strong> <?= htmlspecialchars($usuario_selecionado->nome) ?></p>
                            <p class="mb-1"><strong>E-mail:</strong> <?= htmlspecialchars($usuario_selecionado->email) ?></p>
                            <p class="mb-1"><strong>Grupo de Permissão:</strong> <span class="text-uppercase fw-bold"><?= $usuario_selecionado->tipo ?></span></p>
                            <p class="mb-1"><strong>Criado em:</strong> <?= date('d/m/Y H:i', strtotime($usuario_selecionado->data_criacao)) ?></p>
                            
                            <?php if (isset($usuario_selecionado->meta) && !empty($usuario_selecionado->meta)): ?>
                                <hr class="my-2">
                                <p class="mb-1"><strong>CPF/SUS:</strong> <?= htmlspecialchars($usuario_selecionado->meta->cpf_sus) ?></p>
                                <p class="mb-0"><strong>Telefone Clínico:</strong> <?= htmlspecialchars($usuario_selecionado->meta->telefone) ?></p>
                            <?php endif; ?>
                        </div>

                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-network-wired me-1"></i> Atividades Relacionadas no Sistema</h6>
                        
                        <div style="max-height: 280px; overflow-y: auto;" class="pe-1">
                            <?php if ($usuario_selecionado->tipo === 'admin'): ?>
                                <div class="alert alert-secondary py-2 small">
                                    <i class="fa-solid fa-user-shield me-2"></i>Contas do tipo Administrador possuem controle absoluto e não geram prontuários de atendimento.
                                </div>
                            <?php elseif (empty($atividades_vinculadas)): ?>
                                <p class="text-muted small italic">Nenhuma consulta ou agendamento registrado para este perfil.</p>
                            <?php else: ?>
                                
                                <?php foreach ($atividades_vinculadas as $atv): ?>
                                    <div class="border-start border-2 border-primary ps-3 mb-3 pb-1 small">
                                        <div class="d-flex justify-content-between fw-bold text-dark">
                                            <span><?= date('d/m/Y', strtotime($atv->data_consulta)) ?> - <?= date('H:i', strtotime($atv->hora_consulta)) ?></span>
                                            <span class="badge bg-secondary" style="font-size: 9px;"><?= $atv->status ?></span>
                                        </div>
                                        
                                        <?php if ($usuario_selecionado->tipo === 'paciente'): ?>
                                            <span class="text-muted">Profissional Responsável: <?= htmlspecialchars($atv->nome_profissional) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Paciente Atendido: <?= htmlspecialchars($atv->nome_paciente) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>

                            <?php endif; ?>
                        </div> </div>
                </div>
            <?php endif; ?> </div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>