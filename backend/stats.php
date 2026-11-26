<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    json_out(['message' => 'Operação não autorizada.'], 405);
}

$login = require_user($pdo);

try {
    $stmt = $pdo->prepare(
        "SELECT
            COUNT(*) AS total,
        SUM(situacao = 'D') AS disponiveis,
        SUM(situacao = 'R') AS reservados,
        SUM(situacao = 'V') AS vendidos,
            SUM(condicao = 'novo') AS novo,
            SUM(condicao = 'seminovo') AS seminovo,
            SUM(condicao = 'usado') AS usado,
            SUM(condicao = 'velho') AS velho,
            SUM(condicao IS NULL) AS indefinida
         FROM tb_carro
         WHERE login_usuario = :login"
    );
    $stmt->execute(['login' => $login]);
    $row = $stmt->fetch() ?: [];
    json_out([
        'total' => (int) ($row['total'] ?? 0),
        'disponiveis' => (int) ($row['disponiveis'] ?? 0),
        'reservados' => (int) ($row['reservados'] ?? 0),
        'vendidos' => (int) ($row['vendidos'] ?? 0),
        'por_condicao' => [
            'novo' => (int) ($row['novo'] ?? 0),
            'seminovo' => (int) ($row['seminovo'] ?? 0),
            'usado' => (int) ($row['usado'] ?? 0),
            'velho' => (int) ($row['velho'] ?? 0),
            'indefinida' => (int) ($row['indefinida'] ?? 0),
        ],
    ]);
} catch (Throwable $e) {
    app_log('stats ' . $login . ' ' . $e->getMessage());
    json_out(['message' => 'Falha ao carregar dados.'], 500);
}
