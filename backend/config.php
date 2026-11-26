<?php
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'bd_revendedoracarros';
$user = getenv('DB_USER') ?: 'admin_carros';
$pass = getenv('DB_PASS') ?: 'Admin@123';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    global $host, $dbname, $user, $pass;

    try {
        $pdo = new PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (PDOException $e) {
        if (PHP_SAPI !== 'cli') {
            http_response_code(500);
            echo json_encode(['message' => 'Erro de conexão com o servidor.'], JSON_UNESCAPED_UNICODE);
        } else {
            fwrite(STDERR, "Falha de conexão com o banco.\n");
        }
        exit(1);
    }

    return $pdo;
}

if (PHP_SAPI !== 'cli') {
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

$pdo = db();
