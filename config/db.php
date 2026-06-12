<?php
// Configurações do Banco
$host = 'localhost';
$dbname = 'comunica_mais';
$username = 'root';
$password = 'Senai@118'; // Coloque sua senha do MySQL aqui

try {
    // Criação da conexão com charset UTF8 para evitar erros de acentuação
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Configura o PDO para lançar exceções em caso de erro (importante para o Dev)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Define o modo de busca padrão para objetos, facilitando o acesso aos dados
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

} catch (PDOException $e) {
    // Em caso de erro, interrompe e mostra a mensagem (RN: segurança em ambiente de teste)
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>