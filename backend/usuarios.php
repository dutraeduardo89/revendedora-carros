<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_out(['message' => 'Operação não autorizada.'], 405);
}

$data = read_json();
$login = trim((string) ($data->login ?? ''));
$senha = (string) ($data->senha ?? '');
$erros = [];

if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $login)) {
    $erros['login'] = 'O usuário deve ter de 3 a 50 caracteres e usar apenas letras, números, ponto, hífen ou sublinhado.';
}
if (strlen($senha) < 6 || strlen($senha) > 72) {
    $erros['senha'] = 'A senha deve ter entre 6 e 72 caracteres.';
}
if ($erros) {
    json_out(['message' => 'Verifique os campos destacados.', 'fields' => $erros], 400);
}

try {
    $stmt = $pdo->prepare('SELECT login FROM tb_usuario WHERE login = :login');
    $stmt->execute(['login' => $login]);
    if ($stmt->fetch()) {
        json_out(['message' => 'Este nome de usuário já está em uso.', 'fields' => ['login' => 'Este nome de usuário já está em uso.']], 409);
    }

    $pdo->beginTransaction();
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $pdo->prepare('INSERT INTO tb_usuario (login, senha) VALUES (:login, :senha)')->execute(['login' => $login, 'senha' => $hash]);
    $padrao = regras_padrao();
    $pdo->prepare(
        'INSERT INTO tb_config_classificacao (login_usuario, novo_km_max, seminovo_km_max, velho_idade_min)
         VALUES (:login, :novo, :semi, :velho)'
    )->execute([
        'login' => $login,
        'novo' => $padrao['novo_km_max'],
        'semi' => $padrao['seminovo_km_max'],
        'velho' => $padrao['velho_idade_min'],
    ]);
    $paleta = ['Branco', 'Preto', 'Prata', 'Cinza', 'Vermelho', 'Azul', 'Verde', 'Marrom', 'Bege'];
    $cor = $pdo->prepare('INSERT INTO tb_cor (nome, login_usuario) VALUES (:nome, :login)');
    foreach ($paleta as $nome) {
        $cor->execute(['nome' => $nome, 'login' => $login]);
    }
    $pdo->commit();
    json_out(['message' => 'Usuário cadastrado com sucesso.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    app_log('usuarios ' . $e->getMessage());
    json_out(['message' => 'Falha ao salvar informações.'], 500);
}
