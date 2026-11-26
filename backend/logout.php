<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_out(['message' => 'Operação não autorizada.'], 405);
}

$token = bearer_token();
if ($token) {
    $pdo->prepare('DELETE FROM tb_sessao WHERE token_hash = :hash')->execute(['hash' => hash('sha256', $token)]);
}

json_out(['message' => 'Sessão encerrada.']);
