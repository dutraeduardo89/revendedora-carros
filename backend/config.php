<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
// Adicionado o X-User-Login na lista de permitidos
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-User-Login"); 

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = 'localhost';
$dbname = 'bd_revendedoracarros';
$user = 'admin_carros';
$pass = 'Admin@123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Erro de conexão com o banco: " . $e->getMessage()]);
    exit();
}
?>
