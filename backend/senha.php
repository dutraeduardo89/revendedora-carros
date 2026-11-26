<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_out(['message' => 'Operação não autorizada.'], 405);
}

$login = require_user($pdo);
$data = read_json();
$atual = (string) ($data->senha_atual ?? '');
$nova = (string) ($data->nova_senha ?? '');
$erros = [];

if ($atual === '') {
    $erros['senha_atual'] = 'Informe a senha atual.';
}
if (strlen($nova) < 6 || strlen($nova) > 72) {
    $erros['nova_senha'] = 'A nova senha deve ter entre 6 e 72 caracteres.';
}
if ($atual !== '' && $atual === $nova) {
    $erros['nova_senha'] = 'A nova senha deve ser diferente da senha atual.';
}
if ($erros) {
    json_out(['message' => 'Verifique os campos destacados.', 'fields' => $erros], 400);
}

try {
    $stmt = $pdo->prepare('SELECT senha FROM tb_usuario WHERE login = :login');
    $stmt->execute(['login' => $login]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($atual, (string) $user['senha'])) {
        json_out(['message' => 'A senha atual está incorreta.', 'fields' => ['senha_atual' => 'A senha atual está incorreta.']], 400);
    }

    $hash = password_hash($nova, PASSWORD_DEFAULT);
    $pdo->prepare('UPDATE tb_usuario SET senha = :senha WHERE login = :login')->execute(['senha' => $hash, 'login' => $login]);
    $pdo->prepare('DELETE FROM tb_sessao WHERE login = :login')->execute(['login' => $login]);
    json_out(['message' => 'Senha atualizada com sucesso. Entre novamente.']);
} catch (Throwable $e) {
    app_log('senha ' . $login . ' ' . $e->getMessage());
    json_out(['message' => 'Falha ao salvar informações.'], 500);
}
