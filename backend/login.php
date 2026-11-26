<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_out(['message' => 'Operação não autorizada.'], 405);
}

$data = read_json();
$login = trim((string) ($data->login ?? ''));
$senha = (string) ($data->senha ?? '');

if ($login === '' || $senha === '') {
    json_out(['message' => 'Preencha usuário e senha.', 'fields' => [
        'login' => $login === '' ? 'Informe o usuário.' : null,
        'senha' => $senha === '' ? 'Informe a senha.' : null,
    ]], 400);
}

try {
    $stmt = $pdo->prepare('SELECT login, senha FROM tb_usuario WHERE login = :login');
    $stmt->execute(['login' => $login]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($senha, (string) $user['senha'])) {
        app_log('login recusado para ' . $login);
        json_out(['message' => 'Usuário ou senha inválidos.'], 401);
    }

    $token = bin2hex(random_bytes(32));
    $pdo->prepare('DELETE FROM tb_sessao WHERE login = :login AND expires_at <= NOW()')->execute(['login' => $user['login']]);
    $pdo->prepare('INSERT INTO tb_sessao (login, token_hash, expires_at) VALUES (:login, :hash, DATE_ADD(NOW(), INTERVAL 12 HOUR))')
        ->execute(['login' => $user['login'], 'hash' => hash('sha256', $token)]);

    json_out([
        'message' => 'Login realizado com sucesso.',
        'user' => $user['login'],
        'token' => $token,
    ]);
} catch (Throwable $e) {
    app_log('login ' . $e->getMessage());
    json_out(['message' => 'Falha inesperada do sistema.'], 500);
}
