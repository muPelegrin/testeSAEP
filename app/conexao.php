<?php
// Arquivo de conexao com o banco de dados saep_db
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$usuario = 'root';
$senha = '';
$banco = 'saep_db';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$banco;charset=utf8mb4", $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao conectar com o banco de dados: " . $e->getMessage());
}
?>
