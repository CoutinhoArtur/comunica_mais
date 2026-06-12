<?php
// =================================================================================
// ARQUIVO: area_paciente/agendar.php (Refatorado)
// PROPÓSITO: Agendamento clínico mostrando APENAS horários disponíveis.
// REGRAS ATENDIDAS: RN01 (Evita choque exibindo só o que está livre) e RN02 (Comercial).
// =================================================================================

// 1. CONTROLO DE ACESSO (SEGURANÇA)
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'paciente') {
    session_destroy();
    header("Location: ../public/login.php");
    exit;
}

// 2. IMPORTAÇÃO DO BANCO DE DADOS
require_once '../config/db.php';

$id_paciente = $_SESSION['usuario_id'];
$mensagem_erro = "";
$mensagem_sucesso = "";

// Variáveis de controle do formulário em duas etapas
$id_medico_selecionado = isset($_POST['medico']) ? intval($_POST['medico']) : 0;
$data_selecionada = isset($_POST['data_consulta']) ? $_POST['data_consulta'] : "";
$horarios_livres = [];

// 3. BUSCA OS FONOAUDIÓLOGOS PARA O PRIMEIRO CAMPO
try {
    $sqlMedicos = "SELECT f.id, u.nome, f.especialidade FROM fonoaudiologos f JOIN usuarios u ON f.id = u.id";
    $medicos = $pdo->query($sqlMedicos)->fetchAll();
} catch (PDOException $e) {
    $mensagem_erro = "Erro ao carregar profissionais.";
}

// 4. LÓGICA COGNITIVA: SE O MÉDICO E A DATA FORAM ESCOLHIDOS, CALCULA OS HORÁRIOS LIVRES
if ($id_medico_selecionado > 0 && !empty($data_selecionada)) {
    try {
        // Passo A: Definir todos os horários possíveis da clínica (RN02: 08:00 às 18:00, de hora em hora)
        // Para simplificar o MVP, vamos trabalhar com consultas de 1 em 1 hora.
        $agenda_da_clinica = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];

        // Passo B: Buscar no banco quais desses horários já estão OCUPADOS para este médico nesta data (RN01)
        $sqlOcupados = "SELECT hora_consulta FROM agendamentos 
                        WHERE id_medico = ? AND data_consulta = ? AND status != 'Cancelada'";
        $stmtOcupados = $pdo->prepare($sqlOcupados);
        $stmtOcupados->execute([$id_medico_selecionado, $data_selecionada]);
        
        // 'fetchAll(PDO::FETCH_COLUMN)' extrai os dados direto como um array simples de strings (ex: ['14:00:00'])
        $dados_ocupados = $stmtOcupados->fetchAll(PDO::FETCH_COLUMN);

        // Limpa os segundos (:00) vindos do MySQL para ficar igual ao array da clínica (ex: '14:00:00' vira '14:00')
        $horarios_ocupados = array_map(function($hora) {
            return date('H:i', strtotime($hora));
        }, $dados_ocupados);

        // Passo C: Filtrar os horários. O horário só entra na lista se NÃO estiver no array de ocupados
        foreach ($agenda_da_clinica as $hora_opcao) {
            if (!in_array($hora_opcao, $horarios_ocupados)) {
                $horarios_livres[] = $hora_opcao; // Adiciona ao array de opções disponíveis
            }
        }
    } catch (PDOException $e) {
        $mensagem_erro = "Erro ao calcular horários livres.";
    }
}

// 5. PROCESSAMENTO DO SALVAMENTO FINAL (QUANDO CLICA EM "CONFIRMAR AGENDAMENTO")
// O campo 'confirmar_final' indica que o usuário passou da etapa de verificação e escolheu a hora
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_final'])) {
    $hora_escolhida = filter_input(INPUT_POST, 'hora_consulta', FILTER_SANITIZE_SPECIAL_CHARS);
    $obs            = filter_input(INPUT_POST, 'observacoes', FILTER_SANITIZE_SPECIAL_CHARS);

    if (empty($hora_escolhida)) {
        $mensagem_erro = "Por favor, selecione um horário disponível.";
    } else {
        try {
            // Inserção direta e segura. Não precisamos re-validar choque aqui pois a lista já veio tratada
            $sqlInsert = "INSERT INTO agendamentos (id_paciente, id_medico, data_consulta, hora_consulta, status, observacoes) 
                          VALUES (?, ?, ?, ?, 'Agendada', ?)";
            $stmtInsert = $pdo->prepare($sqlInsert);
            $stmtInsert->execute([$id_paciente, $id_medico_selecionado, $data_selecionada, $hora_escolhida, $obs]);

            $mensagem_sucesso = "Sua consulta foi agendada com sucesso!";
            
            // Limpa as variáveis para resetar o formulário na tela
            $id_medico_selecionado = 0;
            $data_selecionada = "";
            $horarios_livres = [];
        } catch (PDOException $e) {
            $mensagem_erro = "Erro ao salvar o agendamento: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunica+ | Agendar Consulta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --verde-escuro: #1b4d3e; --verde-medio: #2c7a5f; --verde-claro: #f4f9f4; }
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .navbar-interna { background-color: var(--verde-escuro); }
        .card-agendar { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .btn-verde { background-color: var(--verde-medio); color: white; font-weight: 600; }
        .btn-verde:hover { background-color: var(--verde-escuro); color: white; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-interna shadow-sm">
        <div class="container">
            <span class="navbar-brand fw-bold"><i class="fa-solid fa-waveform-lines me-2"></i>Comunica+</span>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Meu Painel</a></li>
                    <li class="nav-item ms-3"><a class="btn btn-sm btn-outline-danger px-3" href="logout.php">Sair</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card card-agendar p-4 bg-white">
                    <h3 class="fw-bold mb-4 text-center" style="color: var(--verde-escuro);"><i class="fa-solid fa-calendar-check me-2"></i>Marcar Consulta</h3>

                    <?php if (!empty($mensagem_erro)): ?>
                        <div class="alert alert-danger"><i class="fa-solid fa-circle-xmark me-2"></i> <?= $mensagem_erro ?></div>
                    <?php endif; ?>

                    <?php if (!empty($mensagem_sucesso)): ?>
                        <div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i> <?= $mensagem_sucesso ?><br><a href="dashboard.php" class="alert-link">Voltar ao Painel</a></div>
                    <?php endif; ?>

                    <form action="agendar.php" method="POST">
                        
                        <div class="mb-3">
                            <label for="medico" class="form-label fw-semibold">Fonoaudiólogo(a) *</label>
                            <select class="form-select" id="medico" name="medico" required onchange="this.form.submit()">
                                <option value="" selected disabled>Selecione...</option>
                                <?php foreach ($medicos as $medico): ?>
                                    <option value="<?= $medico->id ?>" <?= $id_medico_selecionado == $medico->id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($medico->nome) ?> (<?= htmlspecialchars($medico->especialidade) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="data_consulta" class="form-label fw-semibold">Data da Consulta *</label>
                            <input type="date" class="form-control" id="data_consulta" name="data_consulta" required min="<?= date('Y-m-d') ?>" value="<?= $data_selecionada ?>" onchange="this.form.submit()">
                        </div>

                        <?php if ($id_medico_selecionado > 0 && !empty($data_selecionada)): ?>
                            <div class="mb-3 border p-3 rounded bg-light">
                                <label for="hora_consulta" class="form-label fw-semibold text-success"><i class="fa-solid fa-clock me-1"></i> Horários Disponíveis para esta Data *</label>
                                
                                <?php if (empty($horarios_livres)): ?>
                                    <p class="text-danger small mb-0">Infelizmente este profissional não tem horários livres nesta data.</p>
                                <?php else: ?>
                                    <select class="form-select" id="hora_consulta" name="hora_consulta" required>
                                        <option value="" selected disabled>Escolha um horário livre...</option>
                                        <?php foreach ($horarios_livres as $hora_livre): ?>
                                            <option value="<?= $hora_livre ?>"><?= $hora_livre ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>

                            <div class="mb-4">
                                <label for="observacoes" class="form-label fw-semibold">Sintomas Vocais / Notas (Opcional)</label>
                                <textarea class="form-control" id="observacoes" name="observacoes" rows="2"></textarea>
                            </div>

                            <input type="hidden" name="confirmar_final" value="1">
                            <div class="d-grid">
                                <button type="submit" class="btn btn-verde btn-lg shadow-sm">Confirmar Agendamento</button>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info py-2 small text-center"><i class="fa-solid fa-circle-info me-1"></i> Selecione o médico e a data para carregar os horários livres.</div>
                        <?php endif; ?>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>