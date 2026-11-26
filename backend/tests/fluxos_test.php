<?php
require __DIR__ . '/../config.php';

$base = getenv('API_BASE') ?: 'http://127.0.0.1:9090';
$login = 'qa_' . bin2hex(random_bytes(4));
$senha = 'segredo1';
$falhas = 0;

function chamar(string $metodo, string $url, ?string $token = null, ?array $corpo = null): array
{
    $ch = curl_init($url);
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $metodo,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $corpo !== null ? json_encode($corpo) : null,
    ]);
    $resposta = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = json_decode((string) $resposta, true);
    return ['status' => $status, 'json' => is_array($json) ? $json : [], 'raw' => (string) $resposta];
}

function espera(bool $condicao, string $mensagem, array $resposta = []): void
{
    global $falhas;
    if (!$condicao) {
        $falhas++;
        fwrite(STDERR, $mensagem . ' ' . json_encode($resposta, JSON_UNESCAPED_UNICODE) . PHP_EOL);
    }
}

$antes = (int) $pdo->query('SELECT COUNT(*) FROM tb_carro')->fetchColumn();
$placasAntes = $pdo->query("SELECT placa FROM tb_carro WHERE login_usuario = 'admin' ORDER BY id_carro")->fetchAll(PDO::FETCH_COLUMN);

$semToken = chamar('GET', $base . '/carros.php', null, null);
espera($semToken['status'] === 401, 'sem token deve ser 401', $semToken);

$spoof = chamar('GET', $base . '/carros.php');
$spoofReq = curl_init($base . '/carros.php');
curl_setopt_array($spoofReq, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['X-User-Login: admin'],
]);
curl_exec($spoofReq);
$spoofStatus = (int) curl_getinfo($spoofReq, CURLINFO_HTTP_CODE);
curl_close($spoofReq);
espera($spoofStatus === 401, 'cabeçalho X-User-Login não autentica', ['status' => $spoofStatus]);

$cadastro = chamar('POST', $base . '/usuarios.php', null, ['login' => $login, 'senha' => $senha]);
espera($cadastro['status'] === 200, 'cadastro de usuário', $cadastro);
$curto = chamar('POST', $base . '/usuarios.php', null, ['login' => $login . 'b', 'senha' => '123']);
espera($curto['status'] === 400, 'senha curta', $curto);

$auth = chamar('POST', $base . '/login.php', null, ['login' => $login, 'senha' => $senha]);
espera($auth['status'] === 200 && !empty($auth['json']['token']), 'login', $auth);
$token = $auth['json']['token'] ?? '';

$marca = chamar('POST', $base . '/auxiliares.php?acao=marca', $token, ['nome' => 'toyota']);
espera($marca['status'] === 200, 'cria marca', $marca);
$duplicada = chamar('POST', $base . '/auxiliares.php?acao=marca', $token, ['nome' => 'Toyota']);
espera($duplicada['status'] === 409, 'marca duplicada', $duplicada);
$marcas = chamar('GET', $base . '/auxiliares.php?acao=marcas', $token);
$idMarca = $marcas['json'][0]['id_marca'] ?? 0;
$modelo = chamar('POST', $base . '/auxiliares.php?acao=modelo', $token, ['nome' => 'Corolla', 'id_marca' => $idMarca]);
espera($modelo['status'] === 200, 'cria modelo', $modelo);
$modelos = chamar('GET', $base . '/auxiliares.php?acao=modelos', $token);
$idModelo = $modelos['json'][0]['id_modelo'] ?? 0;
$cores = chamar('GET', $base . '/auxiliares.php?acao=cores', $token);
$idCor = $cores['json'][0]['id_cor'] ?? 0;
$acessorio = chamar('POST', $base . '/auxiliares.php?acao=acessorio', $token, ['nome' => 'Teto solar']);
espera($acessorio['status'] === 200, 'cria acessório', $acessorio);
$acessorios = chamar('GET', $base . '/auxiliares.php?acao=acessorios', $token);
$idAcessorio = $acessorios['json'][0]['id_acessorio'] ?? 0;

$baseCarro = [
    'placa' => 'QAE1A23',
    'chassi' => 'QAETESTE12345',
    'id_cor' => $idCor,
    'ano_fabricacao' => 2026,
    'ano_modelo' => 2027,
    'valor' => 90000,
    'quilometragem' => 0,
    'situacao' => 'D',
    'id_modelo' => $idModelo,
    'acessorios' => [$idAcessorio],
    'versao' => 'XEi',
    'categoria' => 'Sedan',
    'combustivel' => 'Flex',
    'transmissao' => 'Automático',
    'motorizacao' => '2.0',
    'portas' => 4,
    'passageiros' => 5,
    'condicao' => 'novo',
    'conservacao' => 'otima',
    'historico' => 'Zero-quilômetro de test drive.',
];

$invalido = chamar('POST', $base . '/carros.php', $token, array_merge($baseCarro, [
    'ano_modelo' => 2028,
    'quilometragem' => -5,
    'condicao' => 'usado',
]));
espera($invalido['status'] === 400, 'rejeita ano e km inválidos', $invalido);
espera(isset($invalido['json']['fields']['ano_modelo']), 'campo ano-modelo', $invalido);
espera(isset($invalido['json']['fields']['quilometragem']), 'campo quilometragem', $invalido);

$criado = chamar('POST', $base . '/carros.php', $token, $baseCarro);
espera($criado['status'] === 200, 'cadastra 2026/2027 zero km', $criado);
$idCarro = $criado['json']['carro']['id_carro'] ?? 0;

$copia = chamar('POST', $base . '/carros.php', $token, array_merge($baseCarro, ['chassi' => 'OUTROCHASSI99']));
espera($copia['status'] === 409 || isset($copia['json']['fields']['placa']), 'placa duplicada', $copia);

$alheio = chamar('PUT', $base . '/carros.php', $token, array_merge($baseCarro, ['id_carro' => 4]));
espera($alheio['status'] === 404, 'não edita veículo de outra conta', $alheio);
$apagaAlheio = chamar('DELETE', $base . '/carros.php?id=4', $token);
espera($apagaAlheio['status'] === 404, 'não exclui veículo de outra conta', $apagaAlheio);

$acessorioAlheio = chamar('POST', $base . '/carros.php', $token, array_merge($baseCarro, [
    'placa' => 'QAE2B34',
    'chassi' => 'QAETESTE99999',
    'acessorios' => [1],
]));
espera($acessorioAlheio['status'] === 400, 'não vincula acessório de outra conta', $acessorioAlheio);

$velho = chamar('POST', $base . '/carros.php', $token, array_merge($baseCarro, [
    'placa' => 'QAE3C45',
    'chassi' => 'VELHOCHASSI001',
    'ano_fabricacao' => 2008,
    'ano_modelo' => 2008,
    'quilometragem' => 1500,
    'condicao' => 'novo',
    'conservacao' => 'ruim',
    'valor' => 12000,
]));
espera($velho['status'] === 400 && isset($velho['json']['fields']['condicao']), 'não aceita novo para carro velho', $velho);
$velhoOk = chamar('POST', $base . '/carros.php', $token, array_merge($baseCarro, [
    'placa' => 'QAE3C45',
    'chassi' => 'VELHOCHASSI001',
    'ano_fabricacao' => 2008,
    'ano_modelo' => 2008,
    'quilometragem' => 1500,
    'condicao' => 'velho',
    'conservacao' => 'ruim',
    'valor' => 12000,
]));
espera($velhoOk['status'] === 200, 'cadastra velho', $velhoOk);

$lista = chamar('GET', $base . '/carros.php', $token);
espera($lista['status'] === 200 && count($lista['json']) === 2, 'lista só os carros da conta', $lista);
$um = chamar('GET', $base . '/carros.php?id=' . $idCarro, $token);
espera(($um['json']['ano_fabricacao'] ?? null) === 2026 && ($um['json']['ano_modelo'] ?? null) === 2027, 'anos independentes', $um);

$editado = chamar('PUT', $base . '/carros.php', $token, array_merge($baseCarro, [
    'id_carro' => $idCarro,
    'quilometragem' => 12000,
    'condicao' => 'seminovo',
    'versao' => 'Altis',
]));
espera($editado['status'] === 200 && ($editado['json']['carro']['condicao'] ?? '') === 'seminovo', 'atualiza seminovo', $editado);

$reservado = chamar('PUT', $base . '/carros.php?acao=situacao', $token, ['id_carro' => $idCarro, 'situacao' => 'R']);
espera($reservado['status'] === 200 && ($reservado['json']['situacao'] ?? '') === 'R', 'reserva veículo', $reservado);
$situacaoRuim = chamar('PUT', $base . '/carros.php?acao=situacao', $token, ['id_carro' => $idCarro, 'situacao' => 'X']);
espera($situacaoRuim['status'] === 400, 'situação inválida', $situacaoRuim);
$situacaoAlheia = chamar('PUT', $base . '/carros.php?acao=situacao', $token, ['id_carro' => 4, 'situacao' => 'V']);
espera($situacaoAlheia['status'] === 404, 'não altera situação de outra conta', $situacaoAlheia);

$regraRuim = chamar('PUT', $base . '/configuracoes.php', $token, ['novo_km_max' => 40000, 'seminovo_km_max' => 1000, 'velho_idade_min' => 15]);
espera($regraRuim['status'] === 400, 'faixa de seminovo inválida', $regraRuim);
$stats = chamar('GET', $base . '/stats.php', $token);
espera($stats['status'] === 200 && ($stats['json']['total'] ?? -1) === 2, 'estatísticas da conta', $stats);

$senhaRuim = chamar('POST', $base . '/senha.php', $token, ['senha_atual' => 'errada1', 'nova_senha' => 'outrasenha']);
espera($senhaRuim['status'] === 400, 'senha atual incorreta não é 401', $senhaRuim);
$aindaLogado = chamar('GET', $base . '/stats.php', $token);
espera($aindaLogado['status'] === 200, 'sessão permanece após senha incorreta', $aindaLogado);

$apagar = chamar('DELETE', $base . '/carros.php?id=' . $idCarro, $token);
espera($apagar['status'] === 200, 'exclui o próprio veículo', $apagar);
$ausente = chamar('GET', $base . '/carros.php?id=' . $idCarro, $token);
espera($ausente['status'] === 404, 'veículo excluído não é encontrado', $ausente);

$pdo->prepare('DELETE FROM tb_usuario WHERE login = :login')->execute(['login' => $login]);
$depois = (int) $pdo->query('SELECT COUNT(*) FROM tb_carro')->fetchColumn();
$placasDepois = $pdo->query("SELECT placa FROM tb_carro WHERE login_usuario = 'admin' ORDER BY id_carro")->fetchAll(PDO::FETCH_COLUMN);
espera($depois === $antes, 'estoque anterior preservado', ['antes' => $antes, 'depois' => $depois]);
espera($placasAntes === $placasDepois, 'placas do admin preservadas', ['antes' => $placasAntes, 'depois' => $placasDepois]);

if ($falhas > 0) {
    fwrite(STDERR, "{$falhas} falha(s) nos fluxos.\n");
    exit(1);
}
echo "fluxos ok\n";
