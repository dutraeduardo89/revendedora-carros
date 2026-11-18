<?php
require 'config.php';
$login = $_SERVER['HTTP_X_USER_LOGIN'] ?? 'admin';

$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM tb_carro WHERE login_usuario = :login");
$stmt->execute(['login' => $login]);
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) AS disponiveis FROM tb_carro WHERE situacao = 'D' AND login_usuario = :login");
$stmt->execute(['login' => $login]);
$disponiveis = $stmt->fetch(PDO::FETCH_ASSOC)['disponiveis'];

$stmt = $pdo->prepare("SELECT COUNT(*) AS vendidos FROM tb_carro WHERE situacao = 'V' AND login_usuario = :login");
$stmt->execute(['login' => $login]);
$vendidos = $stmt->fetch(PDO::FETCH_ASSOC)['vendidos'];

echo json_encode(["total" => $total, "disponiveis" => $disponiveis, "vendidos" => $vendidos]);
?>
