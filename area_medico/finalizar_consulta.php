<?php
// =================================================================================
// ARQUIVO: area_medico/finalizar_consulta.php
// PROPÓSITO: Processamento simultâneo do Prontuário.
// =================================================================================
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'medico') {
    header("Location: ../public/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_medico = $_SESSION['usuario_id'];
    $id_agendamento = filter_input(INPUT_POST, 'id_agendamento', FILTER_SANITIZE_NUMBER_INT);
    $id_paciente = filter_input(INPUT_POST, 'id_paciente', FILTER_SANITIZE_NUMBER_INT);
    $id_exercicio = filter_input(INPUT_POST, 'id_exercicio', FILTER_SANITIZE_NUMBER_INT);
    $observacoes = filter_input(INPUT_POST, 'observacoes', FILTER_DEFAULT);

    if ($id_agendamento && $id_paciente && $id_exercicio) {
        try {
            $pdo->beginTransaction();

            // 1. Vincula o exercício selecionado ao paciente na tabela prescricoes
            $sqlPrescricao = "INSERT INTO prescricoes (id_paciente, id_medico, id_exercicio) VALUES (?, ?, ?)";
            $stmtPres = $pdo->prepare($sqlPrescricao);
            $stmtPres->execute([$id_paciente, $id_medico, $id_exercicio]);

            // 2. Atualiza os dados da tabela de agendamentos informando o status correto
            $sqlAgendamento = "UPDATE agendamentos SET status = 'Realizada', observacoes = ? WHERE id = ?";
            $stmtAgenda = $pdo->prepare($sqlAgendamento);
            $stmtAgenda->execute([$observacoes, $id_agendamento]);

            $pdo->commit();
            header("Location: agenda.php?sucesso=1");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            header("Location: agenda.php?erro=" . urlencode($e->getMessage()));
            exit;
        }
    }
}
header("Location: agenda.php");
exit;