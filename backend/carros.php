<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';

$login = require_user($pdo);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = isset($_GET['id']) && $_GET['id'] !== '' ? (int) $_GET['id'] : null;

function carro_resposta(array $row): array
{
    $nomes = $row['acessorios_nomes'] !== null && $row['acessorios_nomes'] !== ''
        ? explode('||', (string) $row['acessorios_nomes'])
        : [];
    $ids = $row['acessorios_ids'] !== null && $row['acessorios_ids'] !== ''
        ? array_map('intval', explode(',', (string) $row['acessorios_ids']))
        : [];

    return [
        'id_carro' => (int) $row['id_carro'],
        'placa' => $row['placa'],
        'chassi' => $row['chassi'],
        'cor' => $row['cor'],
        'id_cor' => $row['id_cor'] !== null ? (int) $row['id_cor'] : null,
        'ano' => (int) $row['ano'],
        'ano_fabricacao' => (int) $row['ano'],
        'ano_modelo' => (int) $row['ano_modelo'],
        'versao' => $row['versao'],
        'categoria' => $row['categoria'],
        'combustivel' => $row['combustivel'],
        'transmissao' => $row['transmissao'],
        'motorizacao' => $row['motorizacao'],
        'portas' => $row['portas'] !== null ? (int) $row['portas'] : null,
        'passageiros' => $row['passageiros'] !== null ? (int) $row['passageiros'] : null,
        'condicao' => $row['condicao'],
        'conservacao' => $row['conservacao'],
        'historico' => $row['historico'],
        'valor' => (float) $row['valor'],
        'quilometragem' => (int) $row['quilometragem'],
        'situacao' => $row['situacao'],
        'id_modelo' => (int) $row['id_modelo'],
        'modelo' => $row['modelo'],
        'id_marca' => (int) $row['id_marca'],
        'marca' => $row['marca'],
        'acessorios' => $nomes,
        'acessorios_ids' => $ids,
        'criado_em' => $row['criado_em'],
        'editado_em' => $row['editado_em'],
    ];
}

function buscar_carros(PDO $pdo, string $login, ?int $id): array
{
    $sql = "SELECT c.*, m.nome AS modelo, mar.nome AS marca, mar.id_marca,
                   DATE_FORMAT(c.created_at, '%d/%m/%Y %H:%i') AS criado_em,
                   DATE_FORMAT(c.updated_at, '%d/%m/%Y %H:%i') AS editado_em,
                   GROUP_CONCAT(a.nome ORDER BY a.nome SEPARATOR '||') AS acessorios_nomes,
                   GROUP_CONCAT(a.id_acessorio ORDER BY a.nome) AS acessorios_ids
            FROM tb_carro c
            INNER JOIN tb_modelo m ON c.id_modelo = m.id_modelo
            INNER JOIN tb_marca mar ON m.id_marca = mar.id_marca
            LEFT JOIN tb_carro_acessorio ca ON ca.id_carro = c.id_carro
            LEFT JOIN tb_acessorio a ON a.id_acessorio = ca.id_acessorio AND a.login_usuario = c.login_usuario
            WHERE c.login_usuario = :login";
    $params = ['login' => $login];
    if ($id !== null) {
        $sql .= ' AND c.id_carro = :id';
        $params['id'] = $id;
    }
    $sql .= ' GROUP BY c.id_carro, m.nome, mar.nome, mar.id_marca ORDER BY c.id_carro DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function salvar_acessorios(PDO $pdo, int $idCarro, array $ids): void
{
    $pdo->prepare('DELETE FROM tb_carro_acessorio WHERE id_carro = :id')->execute(['id' => $idCarro]);
    if (!$ids) {
        return;
    }
    $stmt = $pdo->prepare('INSERT INTO tb_carro_acessorio (id_carro, id_acessorio) VALUES (:carro, :acessorio)');
    foreach ($ids as $idAcessorio) {
        $stmt->execute(['carro' => $idCarro, 'acessorio' => $idAcessorio]);
    }
}

function ids_acessorios(object $data): array
{
    $lista = is_array($data->acessorios ?? null) ? $data->acessorios : [];
    $ids = [];
    foreach ($lista as $idAcessorio) {
        $ids[] = (int) $idAcessorio;
    }
    return array_values(array_unique($ids));
}

try {
    if ($method === 'GET') {
        $rows = buscar_carros($pdo, $login, $id);
        if ($id !== null && !$rows) {
            json_out(['message' => 'Registro não encontrado.'], 404);
        }
        $carros = array_map('carro_resposta', $rows);
        echo json_encode($id !== null ? $carros[0] : $carros, JSON_UNESCAPED_UNICODE);
        exit();
    }

    if ($method === 'PUT' && (($_GET['acao'] ?? '') === 'situacao')) {
        $data = read_json();
        $idAtual = (int) ($data->id_carro ?? 0);
        $situacao = texto($data, 'situacao');
        if (!in_array($situacao, ['D', 'V', 'R'], true)) {
            json_out(['message' => 'Selecione disponível, reservado ou vendido.'], 400);
        }
        $stmt = $pdo->prepare('UPDATE tb_carro SET situacao = :situacao WHERE id_carro = :id AND login_usuario = :login');
        $stmt->execute(['situacao' => $situacao, 'id' => $idAtual, 'login' => $login]);
        if ($stmt->rowCount() === 0) {
            $existe = $pdo->prepare('SELECT situacao FROM tb_carro WHERE id_carro = :id AND login_usuario = :login');
            $existe->execute(['id' => $idAtual, 'login' => $login]);
            if ($existe->fetchColumn() === false) {
                json_out(['message' => 'Registro não encontrado.'], 404);
            }
        }
        json_out(['message' => 'Situação atualizada.', 'situacao' => $situacao]);
    }

    if ($method === 'POST' || $method === 'PUT') {
        $data = read_json();
        $idAtual = $method === 'PUT' ? (int) ($data->id_carro ?? 0) : null;
        if ($method === 'PUT' && $idAtual <= 0) {
            json_out(['message' => 'Registro não encontrado.'], 404);
        }
        $erros = validar_veiculo($pdo, $data, $login, $idAtual);
        if (isset($erros['_form'])) {
            $codigo = $erros['_form'] === 'Registro não encontrado.' ? 404 : 400;
            json_out(['message' => $erros['_form']], $codigo);
        }
        if ($erros) {
            json_out(['message' => 'Verifique os campos destacados.', 'fields' => $erros], 400);
        }

        $anoFab = (int) ($data->ano_fabricacao ?? $data->ano);
        $campos = [
            'placa' => normalizar_documento(texto($data, 'placa')),
            'chassi' => normalizar_documento(texto($data, 'chassi')),
            'cor' => nome_cor($pdo, (int) $data->id_cor, $login),
            'id_cor' => (int) $data->id_cor,
            'ano' => $anoFab,
            'ano_modelo' => (int) $data->ano_modelo,
            'versao' => texto($data, 'versao') !== '' ? texto($data, 'versao') : null,
            'categoria' => texto($data, 'categoria'),
            'combustivel' => texto($data, 'combustivel'),
            'transmissao' => texto($data, 'transmissao'),
            'motorizacao' => texto($data, 'motorizacao'),
            'portas' => (int) $data->portas,
            'passageiros' => (int) $data->passageiros,
            'condicao' => texto($data, 'condicao'),
            'conservacao' => texto($data, 'conservacao'),
            'historico' => texto($data, 'historico') !== '' ? texto($data, 'historico') : null,
            'valor' => number_format((float) $data->valor, 2, '.', ''),
            'km' => (int) $data->quilometragem,
            'situacao' => texto($data, 'situacao'),
            'modelo' => (int) $data->id_modelo,
            'login' => $login,
        ];

        $pdo->beginTransaction();
        if ($method === 'POST') {
            $stmt = $pdo->prepare(
                'INSERT INTO tb_carro
                (placa, chassi, cor, id_cor, ano, ano_modelo, versao, categoria, combustivel, transmissao,
                 motorizacao, portas, passageiros, condicao, conservacao, historico, valor, quilometragem,
                 situacao, id_modelo, login_usuario)
                 VALUES
                (:placa, :chassi, :cor, :id_cor, :ano, :ano_modelo, :versao, :categoria, :combustivel, :transmissao,
                 :motorizacao, :portas, :passageiros, :condicao, :conservacao, :historico, :valor, :km,
                 :situacao, :modelo, :login)'
            );
            $stmt->execute($campos);
            $idCarro = (int) $pdo->lastInsertId();
            salvar_acessorios($pdo, $idCarro, ids_acessorios($data));
            $pdo->commit();
            $salvo = buscar_carros($pdo, $login, $idCarro);
            json_out(['message' => 'Veículo cadastrado com sucesso.', 'carro' => carro_resposta($salvo[0])]);
        }

        $campos['id'] = $idAtual;
        $stmt = $pdo->prepare(
            'UPDATE tb_carro SET
                placa = :placa, chassi = :chassi, cor = :cor, id_cor = :id_cor, ano = :ano, ano_modelo = :ano_modelo,
                versao = :versao, categoria = :categoria, combustivel = :combustivel, transmissao = :transmissao,
                motorizacao = :motorizacao, portas = :portas, passageiros = :passageiros, condicao = :condicao,
                conservacao = :conservacao, historico = :historico, valor = :valor, quilometragem = :km,
                situacao = :situacao, id_modelo = :modelo
             WHERE id_carro = :id AND login_usuario = :login'
        );
        $stmt->execute($campos);
        salvar_acessorios($pdo, (int) $idAtual, ids_acessorios($data));
        $pdo->commit();
        $salvo = buscar_carros($pdo, $login, (int) $idAtual);
        json_out(['message' => 'Veículo atualizado com sucesso.', 'carro' => carro_resposta($salvo[0])]);
    }

    if ($method === 'DELETE') {
        if ($id === null || $id <= 0) {
            json_out(['message' => 'Registro não encontrado.'], 404);
        }
        $stmt = $pdo->prepare('DELETE FROM tb_carro WHERE id_carro = :id AND login_usuario = :login');
        $stmt->execute(['id' => $id, 'login' => $login]);
        if ($stmt->rowCount() === 0) {
            json_out(['message' => 'Registro não encontrado.'], 404);
        }
        json_out(['message' => 'Veículo excluído com sucesso.']);
    }

    json_out(['message' => 'Operação não autorizada.'], 405);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    app_log('carros ' . $login . ' ' . $e->getCode() . ' ' . $e->getMessage());
    if ((string) $e->getCode() === '23000') {
        json_out(['message' => 'Placa ou chassi já cadastrados no sistema.'], 409);
    }
    json_out(['message' => 'Falha ao salvar informações.'], 500);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    app_log('carros ' . $login . ' ' . $e->getMessage());
    json_out(['message' => 'Falha inesperada do sistema.'], 500);
}
