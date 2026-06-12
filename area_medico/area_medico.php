<?php
// =================================================================================
// ARQUIVO: area_medico/prescrever.php
// PROPÓSITO: Processar e salvar o vínculo do exercício no banco de dados.
// =================================================================================
session_start();
require_once '../config/db.php';

// Bloqueio de segurança externa
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'medico') {
    header("Location: ../public/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_medico = $_SESSION['usuario_id'];
    $id_paciente = filter_input(INPUT_POST, 'id_paciente', FILTER_SANITIZE_NUMBER_INT);
    $id_exercicio = filter_input(INPUT_POST, 'id_exercicio', FILTER_SANITIZE_NUMBER_INT);

    if ($id_paciente && $id_exercicio) {
        try {
            // Insere o vínculo na tabela intermediária
            $sql = "INSERT INTO prescricoes (id_paciente, id_medico, id_exercicio) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_paciente, $id_medico, $id_exercicio]);
            
            // Sucesso! Retorna informando o código 1
            header("Location: agenda.php?sucesso=1");
            exit;
        } catch (PDOException $e) {
            // Erro! Retorna com a mensagem descritiva
            header("Location: agenda.php?erro=" . urlencode($e->getMessage()));
            exit;
        }
    }
}

// Se tentarem acessar o arquivo direto, joga de volta pra agenda
header("Location: agenda.php");
exit;