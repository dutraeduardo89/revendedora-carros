<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';

$login = 'admin';
$regras = obter_regras($pdo, $login);
$anoAtual = (int) date('Y');

function garantir_marca(PDO $pdo, string $login, string $nome): int
{
    $sel = $pdo->prepare('SELECT id_marca FROM tb_marca WHERE login_usuario = :login AND nome = :nome');
    $sel->execute(['login' => $login, 'nome' => $nome]);
    $id = $sel->fetchColumn();
    if ($id) {
        return (int) $id;
    }
    $pdo->prepare('INSERT INTO tb_marca (nome, login_usuario) VALUES (:nome, :login)')->execute(['nome' => $nome, 'login' => $login]);
    return (int) $pdo->lastInsertId();
}

function garantir_modelo(PDO $pdo, string $login, string $nome, int $idMarca): int
{
    $sel = $pdo->prepare('SELECT id_modelo FROM tb_modelo WHERE login_usuario = :login AND nome = :nome AND id_marca = :marca');
    $sel->execute(['login' => $login, 'nome' => $nome, 'marca' => $idMarca]);
    $id = $sel->fetchColumn();
    if ($id) {
        return (int) $id;
    }
    $pdo->prepare('INSERT INTO tb_modelo (nome, id_marca, login_usuario) VALUES (:nome, :marca, :login)')
        ->execute(['nome' => $nome, 'marca' => $idMarca, 'login' => $login]);
    return (int) $pdo->lastInsertId();
}

function garantir_nome(PDO $pdo, string $tabela, string $idCol, string $login, string $nome): int
{
    $sel = $pdo->prepare("SELECT {$idCol} FROM {$tabela} WHERE login_usuario = :login AND nome = :nome");
    $sel->execute(['login' => $login, 'nome' => $nome]);
    $id = $sel->fetchColumn();
    if ($id) {
        return (int) $id;
    }
    $pdo->prepare("INSERT INTO {$tabela} (nome, login_usuario) VALUES (:nome, :login)")->execute(['nome' => $nome, 'login' => $login]);
    return (int) $pdo->lastInsertId();
}

$marcas = [
    'Chevrolet' => ['Opala', 'Chevette', 'Onix', 'S10', 'Tracker'],
    'Volkswagen' => ['Fusca', 'Gol', 'Polo', 'T-Cross', 'Kombi'],
    'Hyundai' => ['HB20', 'Creta'],
    'Jeep' => ['Renegade', 'Compass'],
    'Renault' => ['Kwid', 'Duster'],
    'BMW' => ['320i'],
    'Nissan' => ['Kicks'],
    'Peugeot' => ['208'],
    'Honda' => ['Civic'],
    'Fiat' => ['Uno'],
    'Toyota' => ['Corolla'],
];
$idsModelo = [];
foreach ($marcas as $marca => $modelos) {
    $idMarca = garantir_marca($pdo, $login, $marca);
    foreach ($modelos as $modelo) {
        $idsModelo[$marca . '|' . $modelo] = garantir_modelo($pdo, $login, $modelo, $idMarca);
    }
}

$cores = [];
foreach (['Amarelo', 'Laranja', 'Vinho', 'Dourado', 'Branco', 'Preto', 'Prata', 'Cinza', 'Vermelho', 'Azul', 'Verde', 'Marrom', 'Bege', 'Grafite'] as $cor) {
    $cores[$cor] = garantir_nome($pdo, 'tb_cor', 'id_cor', $login, $cor);
}
$acessorios = [];
foreach (['Ar Condicionado', 'Direção hidráulica', 'Câmera de ré', 'Central multimídia', 'Piloto automático', 'Teto solar', 'Tração 4x4', 'Sensor de estacionamento'] as $nome) {
    $acessorios[$nome] = garantir_nome($pdo, 'tb_acessorio', 'id_acessorio', $login, $nome);
}

$frota = [
    ['FUS1A66', 'FUSCA1966001', 'Volkswagen', 'Fusca', '1600', 1966, 1966, 87000, 'ruim', 28000, 'V', 'Hatch', 'Gasolina', 'Manual', '1.6', 2, 4, 'Amarelo', ['Ar Condicionado']],
    ['OPA1B78', 'OPALA1978001', 'Chevrolet', 'Opala', 'Comodoro', 1978, 1978, 142000, 'regular', 45000, 'D', 'Sedan', 'Gasolina', 'Manual', '4.1', 4, 5, 'Grafite', ['Direção hidráulica']],
    ['CHV1C84', 'CHEVET1984001', 'Chevrolet', 'Chevette', 'SL', 1984, 1984, 98000, 'ruim', 18000, 'R', 'Hatch', 'Gasolina', 'Manual', '1.6', 2, 4, 'Bege', []],
    ['KOM1D72', 'KOMBI1972001', 'Volkswagen', 'Kombi', 'Clipper', 1972, 1972, 210000, 'ruim', 36000, 'D', 'Minivan', 'Gasolina', 'Manual', '1.6', 3, 9, 'Verde', []],
    ['GOL1E96', 'GOL199600001', 'Volkswagen', 'Gol', 'CLI', 1996, 1996, 176000, 'boa', 22000, 'D', 'Hatch', 'Gasolina', 'Manual', '1.8', 4, 5, 'Prata', ['Ar Condicionado']],
    ['UNO1F01', 'UNO200100001', 'Fiat', 'Uno', 'Mille', 2001, 2001, 64000, 'regular', 15000, 'V', 'Hatch', 'Gasolina', 'Manual', '1.0', 2, 5, 'Vermelho', []],
    ['CIV1G08', 'CIVIC2008001', 'Honda', 'Civic', 'LXS', 2008, 2008, 22000, 'otima', 42000, 'D', 'Sedan', 'Flex', 'Manual', '1.8', 4, 5, 'Preto', ['Ar Condicionado', 'Direção hidráulica']],
    ['COR1H14', 'COROLL2014001', 'Toyota', 'Corolla', 'XEi', 2014, 2014, 41000, 'boa', 78000, 'D', 'Sedan', 'Flex', 'Automático', '2.0', 4, 5, 'Branco', ['Central multimídia']],
    ['HBT1J19', 'HB2020190001', 'Hyundai', 'HB20', 'Vision', 2019, 2019, 18000, 'otima', 69000, 'R', 'Hatch', 'Flex', 'Manual', '1.0', 4, 5, 'Prata', ['Câmera de ré']],
    ['ONX1K24', 'ONIX20240001', 'Chevrolet', 'Onix', 'Premier', 2024, 2024, 0, 'otima', 82000, 'D', 'Hatch', 'Flex', 'Automático', '1.0 Turbo', 4, 5, 'Branco', ['Central multimídia', 'Câmera de ré']],
    ['CPS1L22', 'COMPAS2022001', 'Jeep', 'Compass', 'Longitude', 2022, 2022, 27000, 'boa', 149000, 'D', 'SUV', 'Flex', 'Automático', '1.3 Turbo', 4, 5, 'Cinza', ['Teto solar', 'Piloto automático']],
    ['KWD1M20', 'KWID20200001', 'Renault', 'Kwid', 'Zen', 2020, 2020, 52000, 'boa', 48000, 'V', 'Hatch', 'Flex', 'Manual', '1.0', 4, 5, 'Branco', ['Ar Condicionado']],
    ['S101N16', 'S10PIC2016001', 'Chevrolet', 'S10', 'LT', 2016, 2016, 110000, 'ruim', 98000, 'D', 'Picape', 'Diesel', 'Automático', '2.8', 4, 5, 'Preto', ['Tração 4x4']],
    ['CRT1P25', 'CRETA2025001', 'Hyundai', 'Creta', 'Limited', 2025, 2025, 8000, 'boa', 132000, 'D', 'SUV', 'Flex', 'Automático', '1.0 Turbo', 4, 5, 'Grafite', ['Piloto automático']],
    ['BMW1Q17', 'BMW320I201701', 'BMW', '320i', 'Sport', 2017, 2017, 35000, 'boa', 168000, 'R', 'Sedan', 'Gasolina', 'Automático', '2.0 Turbo', 4, 5, 'Azul', ['Teto solar', 'Central multimídia']],
    ['TCR1R26', 'TCROSS2026001', 'Volkswagen', 'T-Cross', 'Highline', 2026, 2026, 0, 'otima', 142000, 'D', 'SUV', 'Flex', 'Automático', '1.0 Turbo', 4, 5, 'Branco', ['Câmera de ré', 'Piloto automático']],
    ['RNG1S21', 'RENEGA2021001', 'Jeep', 'Renegade', 'Sport', 2021, 2021, 19000, 'otima', 99000, 'D', 'SUV', 'Flex', 'Automático', '1.3 Turbo', 4, 5, 'Laranja', ['Tração 4x4']],
    ['POL1T23', 'POLO20230001', 'Volkswagen', 'Polo', 'Comfortline', 2023, 2023, 12000, 'boa', 89000, 'D', 'Hatch', 'Flex', 'Automático', '1.0 Turbo', 4, 5, 'Vermelho', ['Central multimídia']],
    ['TRK1U27', 'TRACK2027001', 'Chevrolet', 'Tracker', 'Premier', 2027, 2027, 0, 'boa', 155000, 'D', 'SUV', 'Flex', 'Automático', '1.2 Turbo', 4, 5, 'Prata', ['Teto solar']],
    ['DST1V13', 'DUSTER2013001', 'Renault', 'Duster', 'Dynamique', 2013, 2013, 88000, 'regular', 52000, 'D', 'SUV', 'Flex', 'Manual', '1.6', 4, 5, 'Marrom', ['Sensor de estacionamento']],
    ['KIC1W23', 'KICKS2023001', 'Nissan', 'Kicks', 'Advance', 2023, 2024, 9000, 'otima', 118000, 'D', 'SUV', 'Flex', 'CVT', '1.6', 4, 5, 'Vinho', ['Câmera de ré']],
    ['P208X22', 'PEUGE2022001', 'Peugeot', '208', 'Allure', 2022, 2022, 15000, 'boa', 79000, 'R', 'Hatch', 'Flex', 'Automático', '1.0 Turbo', 4, 5, 'Dourado', ['Central multimídia']],
];

$existe = $pdo->prepare('SELECT id_carro FROM tb_carro WHERE placa = :placa OR chassi = :chassi');
$insert = $pdo->prepare(
    'INSERT INTO tb_carro
    (placa, chassi, cor, id_cor, ano, ano_modelo, versao, categoria, combustivel, transmissao,
     motorizacao, portas, passageiros, condicao, conservacao, valor, quilometragem, situacao, id_modelo, login_usuario)
     VALUES
    (:placa, :chassi, :cor, :id_cor, :ano, :ano_modelo, :versao, :categoria, :combustivel, :transmissao,
     :motorizacao, :portas, :passageiros, :condicao, :conservacao, :valor, :km, :situacao, :modelo, :login)'
);
$liga = $pdo->prepare('INSERT IGNORE INTO tb_carro_acessorio (id_carro, id_acessorio) VALUES (:carro, :acessorio)');

$novos = 0;
foreach ($frota as $item) {
    [$placa, $chassi, $marca, $modelo, $versao, $ano, $anoModelo, $km, $conservacao, $valor, $situacao, $categoria, $combustivel, $transmissao, $motor, $portas, $passageiros, $cor, $listaAcessorios] = $item;
    $existe->execute(['placa' => $placa, 'chassi' => $chassi]);
    if ($existe->fetchColumn()) {
        continue;
    }
    if (!isset($idsModelo[$marca . '|' . $modelo])) {
        fwrite(STDERR, "modelo ausente: {$marca} {$modelo}\n");
        continue;
    }
    $condicao = sugerir_condicao($km, $ano, $conservacao, $regras, $anoAtual);
    $insert->execute([
        'placa' => $placa,
        'chassi' => $chassi,
        'cor' => $cor,
        'id_cor' => $cores[$cor],
        'ano' => $ano,
        'ano_modelo' => $anoModelo,
        'versao' => $versao,
        'categoria' => $categoria,
        'combustivel' => $combustivel,
        'transmissao' => $transmissao,
        'motorizacao' => $motor,
        'portas' => $portas,
        'passageiros' => $passageiros,
        'condicao' => $condicao,
        'conservacao' => $conservacao,
        'valor' => number_format($valor, 2, '.', ''),
        'km' => $km,
        'situacao' => $situacao,
        'modelo' => $idsModelo[$marca . '|' . $modelo],
        'login' => $login,
    ]);
    $idCarro = (int) $pdo->lastInsertId();
    foreach ($listaAcessorios as $nome) {
        if (isset($acessorios[$nome])) {
            $liga->execute(['carro' => $idCarro, 'acessorio' => $acessorios[$nome]]);
        }
    }
    $novos++;
}

echo "veículos novos: {$novos}\n";
echo "total admin: " . $pdo->query("SELECT COUNT(*) FROM tb_carro WHERE login_usuario = 'admin'")->fetchColumn() . "\n";
