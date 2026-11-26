<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';

$login = require_user($pdo);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        json_out(obter_regras($pdo, $login));
    }

    if ($method === 'PUT') {
        $data = read_json();
        $regras = [
            'novo_km_max' => $data->novo_km_max ?? null,
            'seminovo_km_max' => $data->seminovo_km_max ?? null,
            'velho_idade_min' => $data->velho_idade_min ?? null,
        ];
        $erros = validar_regras($regras);
        if ($erros) {
            json_out(['message' => 'Verifique os campos destacados.', 'fields' => $erros], 400);
        }
        $stmt = $pdo->prepare(
            'UPDATE tb_config_classificacao
             SET novo_km_max = :novo, seminovo_km_max = :semi, velho_idade_min = :velho
             WHERE login_usuario = :login'
        );
        $stmt->execute([
            'novo' => (int) $regras['novo_km_max'],
            'semi' => (int) $regras['seminovo_km_max'],
            'velho' => (int) $regras['velho_idade_min'],
            'login' => $login,
        ]);
        if ($stmt->rowCount() === 0) {
            obter_regras($pdo, $login);
            $stmt->execute([
                'novo' => (int) $regras['novo_km_max'],
                'semi' => (int) $regras['seminovo_km_max'],
                'velho' => (int) $regras['velho_idade_min'],
                'login' => $login,
            ]);
        }
        json_out(['message' => 'Regras de classificação atualizadas. Os veículos já cadastrados não foram reclassificados.']);
    }

    json_out(['message' => 'Operação não autorizada.'], 405);
} catch (Throwable $e) {
    app_log('configuracoes ' . $login . ' ' . $e->getMessage());
    json_out(['message' => 'Falha ao salvar informações.'], 500);
}
