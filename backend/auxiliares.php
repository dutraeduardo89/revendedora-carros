<?php
require 'config.php';
$acao = $_GET['acao'] ?? '';

if ($acao == 'marcas') {
    $stmt = $pdo->query("SELECT * FROM tb_marca");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} elseif ($acao == 'modelos') {
    $stmt = $pdo->query("SELECT * FROM tb_modelo");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} elseif ($acao == 'acessorios') {
    $stmt = $pdo->query("SELECT * FROM tb_acessorio");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
?>
