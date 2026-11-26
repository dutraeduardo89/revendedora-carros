<?php

if (PHP_SAPI !== 'cli' && basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'lib.php') {
    http_response_code(404);
    exit();
}

function app_log(string $message): void
{
    $dir = __DIR__ . '/logs';
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    $line = date('c') . ' ' . $message . PHP_EOL;
    file_put_contents($dir . '/app.log', $line, FILE_APPEND);
}

function json_out(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function read_json(): object
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        json_out(['message' => 'Não foi possível ler os dados enviados.'], 400);
    }
    $data = json_decode($raw);
    if (!is_object($data)) {
        json_out(['message' => 'Não foi possível ler os dados enviados.'], 400);
    }
    return $data;
}

function bearer_token(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if ($header === '' && function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }
    }
    if (preg_match('/Bearer\s+(\S+)/i', $header, $matches)) {
        return $matches[1];
    }
    return null;
}

function require_user(PDO $pdo): string
{
    $token = bearer_token();
    if ($token === null || $token === '') {
        json_out(['message' => 'Operação não autorizada. Faça login novamente.'], 401);
    }

    $stmt = $pdo->prepare(
        'SELECT login FROM tb_sessao WHERE token_hash = :hash AND expires_at > NOW() LIMIT 1'
    );
    $stmt->execute(['hash' => hash('sha256', $token)]);
    $login = $stmt->fetchColumn();
    if (!$login) {
        json_out(['message' => 'Operação não autorizada. Faça login novamente.'], 401);
    }
    return (string) $login;
}

function method_is(string $expected): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $expected) {
        json_out(['message' => 'Operação não autorizada.'], 405);
    }
}

function texto(object $data, string $field): string
{
    if (!isset($data->{$field}) || !is_scalar($data->{$field})) {
        return '';
    }
    return trim((string) $data->{$field});
}

function formatar_nome(string $nome): string
{
    $nome = preg_replace('/\s+/u', ' ', trim($nome)) ?? '';
    if ($nome === '') {
        return '';
    }
    if (mb_strtolower($nome, 'UTF-8') === $nome) {
        return mb_convert_case($nome, MB_CASE_TITLE, 'UTF-8');
    }
    return $nome;
}

function normalizar_documento(string $valor): string
{
    $limpo = preg_replace('/[^A-Za-z0-9]/', '', $valor) ?? '';
    return strtoupper($limpo);
}

function placa_formato_valido(string $placa): bool
{
    return (bool) preg_match('/^[A-Z]{3}[0-9]{4}$/', $placa)
        || (bool) preg_match('/^[A-Z]{3}[0-9][A-Z][0-9]{2}$/', $placa);
}

function catalogos(): array
{
    return [
        'categorias' => ['Hatch', 'Sedan', 'SUV', 'Picape', 'Coupé', 'Perua', 'Minivan', 'Conversível', 'Utilitário'],
        'combustiveis' => ['Gasolina', 'Etanol', 'Flex', 'Diesel', 'Elétrico', 'Híbrido', 'GNV'],
        'transmissoes' => ['Manual', 'Automático', 'Automatizado', 'CVT'],
        'condicoes' => ['novo', 'seminovo', 'usado', 'velho'],
        'conservacoes' => ['otima', 'boa', 'regular', 'ruim'],
    ];
}

function ano_catalogo_min(): int
{
    return (int) date('Y') - 40;
}

function ano_catalogo_max(): int
{
    return (int) date('Y') + 1;
}

function regras_padrao(): array
{
    return [
        'novo_km_max' => 0,
        'seminovo_km_max' => 30000,
        'velho_idade_min' => 15,
    ];
}

function obter_regras(PDO $pdo, string $login): array
{
    $stmt = $pdo->prepare(
        'SELECT novo_km_max, seminovo_km_max, velho_idade_min FROM tb_config_classificacao WHERE login_usuario = :login'
    );
    $stmt->execute(['login' => $login]);
    $row = $stmt->fetch();
    if (!$row) {
        $padrao = regras_padrao();
        $insert = $pdo->prepare(
            'INSERT INTO tb_config_classificacao (login_usuario, novo_km_max, seminovo_km_max, velho_idade_min)
             VALUES (:login, :novo, :semi, :velho)'
        );
        $insert->execute([
            'login' => $login,
            'novo' => $padrao['novo_km_max'],
            'semi' => $padrao['seminovo_km_max'],
            'velho' => $padrao['velho_idade_min'],
        ]);
        return $padrao;
    }
    return [
        'novo_km_max' => (int) $row['novo_km_max'],
        'seminovo_km_max' => (int) $row['seminovo_km_max'],
        'velho_idade_min' => (int) $row['velho_idade_min'],
    ];
}

function validar_regras(array $regras): array
{
    $erros = [];
    foreach (['novo_km_max', 'seminovo_km_max', 'velho_idade_min'] as $campo) {
        if (!isset($regras[$campo]) || !is_numeric($regras[$campo]) || (int) $regras[$campo] != $regras[$campo]) {
            $erros[$campo] = 'Informe um número inteiro.';
        }
    }
    if ($erros) {
        return $erros;
    }
    $novo = (int) $regras['novo_km_max'];
    $semi = (int) $regras['seminovo_km_max'];
    $idade = (int) $regras['velho_idade_min'];
    if ($novo < 0 || $novo > 1000000) {
        $erros['novo_km_max'] = 'A faixa de novo deve ficar entre 0 e 1.000.000 km.';
    }
    if ($semi < $novo || $semi > 1000000) {
        $erros['seminovo_km_max'] = 'A faixa de seminovo deve ser maior ou igual à de novo e no máximo 1.000.000 km.';
    }
    if ($idade < 1 || $idade > 60) {
        $erros['velho_idade_min'] = 'A idade mínima de velho deve ficar entre 1 e 60 anos.';
    }
    return $erros;
}

function limite_passageiros(string $categoria): int
{
    if ($categoria === 'Utilitário') {
        return 20;
    }
    if ($categoria === 'Minivan') {
        return 15;
    }
    return 9;
}

function sugerir_condicao(int $km, int $anoFabricacao, string $conservacao, array $regras, ?int $anoAtual = null): string
{
    $anoAtual = $anoAtual ?? (int) date('Y');
    $idade = $anoAtual - $anoFabricacao;
    $conservacaoRuim = in_array($conservacao, ['regular', 'ruim'], true);
    $velhoPorIdade = $idade >= (int) $regras['velho_idade_min'];
    if ($velhoPorIdade && $conservacaoRuim) {
        return 'velho';
    }
    if (!$conservacaoRuim && !$velhoPorIdade && $km <= (int) $regras['novo_km_max']) {
        return 'novo';
    }
    if (!$conservacaoRuim && $km <= (int) $regras['seminovo_km_max']) {
        return 'seminovo';
    }
    return 'usado';
}

function motivo_condicao(string $condicao, int $km, int $anoFabricacao, string $conservacao, array $regras, ?int $anoAtual = null): string
{
    $anoAtual = $anoAtual ?? (int) date('Y');
    $idade = $anoAtual - $anoFabricacao;
    $kmTexto = number_format($km, 0, ',', '.') . ' km';
    if ($condicao === 'velho') {
        return "Idade de {$idade} anos e conservação baixa. A classificação velho não depende apenas da quilometragem.";
    }
    if ($condicao === 'novo') {
        return "{$kmTexto} dentro da faixa de novo e conservação boa.";
    }
    if ($condicao === 'seminovo') {
        return "{$kmTexto} dentro da faixa de seminovo (acima de {$regras['novo_km_max']} km e até {$regras['seminovo_km_max']} km) e conservação boa.";
    }
    if (in_array($conservacao, ['regular', 'ruim'], true)) {
        return 'A conservação não permite classificar como novo ou seminovo, e a idade ainda não caracteriza velho.';
    }
    return "{$kmTexto} acima da faixa de seminovo ({$regras['seminovo_km_max']} km).";
}

function validar_veiculo(PDO $pdo, object $data, string $login, ?int $idAtual = null): array
{
    $erros = [];
    $catalogo = catalogos();
    $regras = obter_regras($pdo, $login);

    $placa = normalizar_documento(texto($data, 'placa'));
    $chassi = normalizar_documento(texto($data, 'chassi'));
    if ($placa === '') {
        $erros['placa'] = 'Informe a placa.';
    }
    if ($chassi === '') {
        $erros['chassi'] = 'Informe o chassi.';
    }

    $placaAtual = null;
    $chassiAtual = null;
    $anoOriginal = null;
    if ($idAtual !== null) {
        $stmt = $pdo->prepare('SELECT placa, chassi, ano FROM tb_carro WHERE id_carro = :id AND login_usuario = :login');
        $stmt->execute(['id' => $idAtual, 'login' => $login]);
        $atual = $stmt->fetch();
        if (!$atual) {
            return ['_form' => 'Registro não encontrado.'];
        }
        $placaAtual = normalizar_documento((string) $atual['placa']);
        $chassiAtual = normalizar_documento((string) $atual['chassi']);
        $anoOriginal = (int) $atual['ano'];
    }

    if ($placa !== '' && !placa_formato_valido($placa) && $placa !== $placaAtual) {
        $erros['placa'] = 'Use o padrão antigo (ABC1234) ou o Mercosul (ABC1D23).';
    }
    if ($chassi !== '' && $chassi !== $chassiAtual && (strlen($chassi) < 5 || strlen($chassi) > 30)) {
        $erros['chassi'] = 'O chassi deve ter entre 5 e 30 caracteres, sem símbolos.';
    }

    $ignorarId = $idAtual ?? 0;
    if ($placa !== '') {
        $stmt = $pdo->prepare('SELECT id_carro FROM tb_carro WHERE placa = :placa AND id_carro <> :id LIMIT 1');
        $stmt->execute(['placa' => $placa, 'id' => $ignorarId]);
        if ($stmt->fetch()) {
            $erros['placa'] = 'Esta placa já está cadastrada.';
        }
    }
    if ($chassi !== '') {
        $stmt = $pdo->prepare('SELECT id_carro FROM tb_carro WHERE chassi = :chassi AND id_carro <> :id LIMIT 1');
        $stmt->execute(['chassi' => $chassi, 'id' => $ignorarId]);
        if ($stmt->fetch()) {
            $erros['chassi'] = 'Este chassi já está cadastrado.';
        }
    }

    $idCor = $data->id_cor ?? null;
    if (!is_numeric($idCor) || (int) $idCor <= 0) {
        $erros['cor'] = 'Selecione uma cor.';
    } else {
        $stmt = $pdo->prepare('SELECT nome FROM tb_cor WHERE id_cor = :id AND login_usuario = :login');
        $stmt->execute(['id' => (int) $idCor, 'login' => $login]);
        $nomeCor = $stmt->fetchColumn();
        if (!$nomeCor) {
            $erros['cor'] = 'A cor selecionada não foi encontrada na sua conta.';
        }
    }

    $anoFab = $data->ano_fabricacao ?? ($data->ano ?? null);
    $anoMod = $data->ano_modelo ?? null;
    if (!is_numeric($anoFab) || (int) $anoFab != $anoFab) {
        $erros['ano_fabricacao'] = 'Informe o ano de fabricação.';
    }
    if (!is_numeric($anoMod) || (int) $anoMod != $anoMod) {
        $erros['ano_modelo'] = 'Informe o ano-modelo.';
    }
    if (!isset($erros['ano_fabricacao']) && !isset($erros['ano_modelo'])) {
        $anoFab = (int) $anoFab;
        $anoMod = (int) $anoMod;
        $min = ano_catalogo_min();
        $max = ano_catalogo_max();
        $foraDoCatalogo = $anoFab < $min || $anoFab > $max;
        $mantemAnoOriginal = $anoOriginal !== null && $anoFab === $anoOriginal;
        if ($foraDoCatalogo && !$mantemAnoOriginal) {
            $erros['ano_fabricacao'] = "O ano de fabricação deve estar entre {$min} e {$max}.";
        }
        if ($anoMod !== $anoFab && $anoMod !== $anoFab + 1) {
            $erros['ano_modelo'] = 'O ano-modelo deve ser igual ao de fabricação ou o ano seguinte.';
        } elseif ($anoMod > $max && !($mantemAnoOriginal && $anoMod <= $anoFab + 1)) {
            $erros['ano_modelo'] = "O ano-modelo não pode passar de {$max}.";
        }
    }

    $idModelo = $data->id_modelo ?? null;
    if (!is_numeric($idModelo) || (int) $idModelo <= 0) {
        $erros['id_modelo'] = 'Selecione o modelo.';
    } else {
        $stmt = $pdo->prepare('SELECT id_modelo FROM tb_modelo WHERE id_modelo = :id AND login_usuario = :login');
        $stmt->execute(['id' => (int) $idModelo, 'login' => $login]);
        if (!$stmt->fetch()) {
            $erros['id_modelo'] = 'O modelo selecionado não foi encontrado na sua conta.';
        }
    }

    $categoria = texto($data, 'categoria');
    if (!in_array($categoria, $catalogo['categorias'], true)) {
        $erros['categoria'] = 'Selecione a categoria do veículo.';
    }
    $combustivel = texto($data, 'combustivel');
    if (!in_array($combustivel, $catalogo['combustiveis'], true)) {
        $erros['combustivel'] = 'Selecione o tipo de combustível.';
    }
    $transmissao = texto($data, 'transmissao');
    if (!in_array($transmissao, $catalogo['transmissoes'], true)) {
        $erros['transmissao'] = 'Selecione o tipo de transmissão.';
    }

    $versao = texto($data, 'versao');
    if (mb_strlen($versao) > 80) {
        $erros['versao'] = 'A versão deve ter no máximo 80 caracteres.';
    }
    $motorizacao = texto($data, 'motorizacao');
    if ($motorizacao === '') {
        $erros['motorizacao'] = 'Informe a motorização.';
    } elseif (mb_strlen($motorizacao) > 40) {
        $erros['motorizacao'] = 'A motorização deve ter no máximo 40 caracteres.';
    }

    $portas = $data->portas ?? null;
    if (!is_numeric($portas) || (int) $portas != $portas || (int) $portas < 2 || (int) $portas > 6) {
        $erros['portas'] = 'Informe o número de portas entre 2 e 6.';
    }
    $passageiros = $data->passageiros ?? null;
    $teto = isset($erros['categoria']) ? 9 : limite_passageiros($categoria);
    if (!is_numeric($passageiros) || (int) $passageiros != $passageiros || (int) $passageiros < 1 || (int) $passageiros > $teto) {
        $erros['passageiros'] = "Informe a quantidade de passageiros entre 1 e {$teto}.";
    }

    $km = $data->quilometragem ?? null;
    if ($km === '' || $km === null || !is_numeric($km) || (int) $km != $km) {
        $erros['quilometragem'] = 'Informe a quilometragem em quilômetros, sem casas decimais.';
    } elseif ((int) $km < 0) {
        $erros['quilometragem'] = 'A quilometragem não pode ser negativa. Use 0 para zero-quilômetro.';
    } elseif ((int) $km > 2000000) {
        $erros['quilometragem'] = 'A quilometragem informada passa do limite aceito (2.000.000 km).';
    }

    $valor = $data->valor ?? null;
    if ($valor === '' || $valor === null || !is_numeric($valor)) {
        $erros['valor'] = 'Informe o valor do veículo.';
    } elseif ((float) $valor <= 0) {
        $erros['valor'] = 'O valor deve ser maior que zero.';
    } elseif ((float) $valor > 99999999.99) {
        $erros['valor'] = 'O valor passa do limite aceito.';
    }

    $situacao = texto($data, 'situacao');
    if (!in_array($situacao, ['D', 'V', 'R'], true)) {
        $erros['situacao'] = 'Selecione disponível, reservado ou vendido.';
    }

    $conservacao = texto($data, 'conservacao');
    if (!in_array($conservacao, $catalogo['conservacoes'], true)) {
        $erros['conservacao'] = 'Selecione a conservação.';
    }

    $condicao = texto($data, 'condicao');
    if (!in_array($condicao, $catalogo['condicoes'], true)) {
        $erros['condicao'] = 'Selecione a classificação do veículo.';
    } elseif (!isset($erros['quilometragem']) && !isset($erros['ano_fabricacao']) && !isset($erros['conservacao'])) {
        $esperada = sugerir_condicao((int) $km, (int) $anoFab, $conservacao, $regras);
        if ($condicao !== $esperada) {
            $erros['condicao'] = 'A classificação não combina com a quilometragem, a idade e a conservação. A regra atual indica: ' . $esperada . '.';
        }
    }

    $historico = texto($data, 'historico');
    if (mb_strlen($historico) > 500) {
        $erros['historico'] = 'O histórico deve ter no máximo 500 caracteres.';
    }

    $acessorios = $data->acessorios ?? [];
    if ($acessorios === null) {
        $acessorios = [];
    }
    if (!is_array($acessorios)) {
        $erros['acessorios'] = 'A lista de acessórios é inválida.';
    } else {
        $ids = [];
        foreach ($acessorios as $idAcessorio) {
            if (!is_numeric($idAcessorio) || (int) $idAcessorio <= 0) {
                $erros['acessorios'] = 'Há um acessório inválido na seleção.';
                break;
            }
            $ids[] = (int) $idAcessorio;
        }
        $ids = array_values(array_unique($ids));
        if (!isset($erros['acessorios']) && $ids) {
            $marcadores = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_acessorio WHERE login_usuario = ? AND id_acessorio IN ($marcadores)");
            $stmt->execute(array_merge([$login], $ids));
            if ((int) $stmt->fetchColumn() !== count($ids)) {
                $erros['acessorios'] = 'Um ou mais acessórios não pertencem à sua conta.';
            }
        }
    }

    return $erros;
}

function nome_cor(PDO $pdo, int $idCor, string $login): string
{
    $stmt = $pdo->prepare('SELECT nome FROM tb_cor WHERE id_cor = :id AND login_usuario = :login');
    $stmt->execute(['id' => $idCor, 'login' => $login]);
    return (string) $stmt->fetchColumn();
}
