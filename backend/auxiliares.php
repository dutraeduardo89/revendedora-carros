<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';

$login = require_user($pdo);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$acao = $_GET['acao'] ?? '';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

function responder_lista(PDO $pdo, string $acao, string $login): void
{
    if ($acao === 'marcas') {
        $stmt = $pdo->prepare('SELECT id_marca, nome FROM tb_marca WHERE login_usuario = :login ORDER BY nome');
    } elseif ($acao === 'modelos') {
        $stmt = $pdo->prepare(
            'SELECT m.id_modelo, m.nome, m.id_marca, ma.nome AS marca_nome
             FROM tb_modelo m
             INNER JOIN tb_marca ma ON m.id_marca = ma.id_marca
             WHERE m.login_usuario = :login
             ORDER BY ma.nome, m.nome'
        );
    } elseif ($acao === 'acessorios') {
        $stmt = $pdo->prepare('SELECT id_acessorio, nome FROM tb_acessorio WHERE login_usuario = :login ORDER BY nome');
    } elseif ($acao === 'cores') {
        $stmt = $pdo->prepare('SELECT id_cor, nome FROM tb_cor WHERE login_usuario = :login ORDER BY nome');
    } else {
        json_out(['message' => 'Não foi possível carregar os dados.'], 400);
    }
    $stmt->execute(['login' => $login]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        foreach ($row as $chave => $valor) {
            if ($chave !== 'nome' && $chave !== 'marca_nome' && $valor !== null) {
                $row[$chave] = (int) $valor;
            }
        }
    }
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    exit();
}

function validar_nome(string $nome): ?string
{
    if ($nome === '') {
        return 'Informe o nome.';
    }
    if (mb_strlen($nome) < 2 || mb_strlen($nome) > 50) {
        return 'O nome deve ter entre 2 e 50 caracteres.';
    }
    return null;
}

try {
    if ($method === 'GET') {
        responder_lista($pdo, $acao, $login);
    }

    if ($method === 'POST' || $method === 'PUT') {
        $data = read_json();
        $nome = formatar_nome(texto($data, 'nome'));
        $erroNome = validar_nome($nome);
        if ($erroNome !== null) {
            json_out(['message' => $erroNome, 'fields' => ['nome' => $erroNome]], 400);
        }

        if ($acao === 'marca') {
            if ($method === 'POST') {
                $stmt = $pdo->prepare('INSERT INTO tb_marca (nome, login_usuario) VALUES (:nome, :login)');
                $stmt->execute(['nome' => $nome, 'login' => $login]);
            } else {
                $stmt = $pdo->prepare('UPDATE tb_marca SET nome = :nome WHERE id_marca = :id AND login_usuario = :login');
                $stmt->execute(['nome' => $nome, 'id' => $id, 'login' => $login]);
                if ($stmt->rowCount() === 0) {
                    $existe = $pdo->prepare('SELECT id_marca FROM tb_marca WHERE id_marca = :id AND login_usuario = :login');
                    $existe->execute(['id' => $id, 'login' => $login]);
                    if (!$existe->fetch()) {
                        json_out(['message' => 'Registro não encontrado.'], 404);
                    }
                }
            }
        } elseif ($acao === 'modelo') {
            $idMarca = (int) ($data->id_marca ?? 0);
            $marca = $pdo->prepare('SELECT id_marca FROM tb_marca WHERE id_marca = :id AND login_usuario = :login');
            $marca->execute(['id' => $idMarca, 'login' => $login]);
            if (!$marca->fetch()) {
                json_out(['message' => 'Selecione uma marca da sua conta.', 'fields' => ['id_marca' => 'Selecione uma marca da sua conta.']], 400);
            }
            if ($method === 'POST') {
                $stmt = $pdo->prepare('INSERT INTO tb_modelo (nome, id_marca, login_usuario) VALUES (:nome, :marca, :login)');
                $stmt->execute(['nome' => $nome, 'marca' => $idMarca, 'login' => $login]);
            } else {
                $stmt = $pdo->prepare('UPDATE tb_modelo SET nome = :nome, id_marca = :marca WHERE id_modelo = :id AND login_usuario = :login');
                $stmt->execute(['nome' => $nome, 'marca' => $idMarca, 'id' => $id, 'login' => $login]);
                if ($stmt->rowCount() === 0) {
                    $existe = $pdo->prepare('SELECT id_modelo FROM tb_modelo WHERE id_modelo = :id AND login_usuario = :login');
                    $existe->execute(['id' => $id, 'login' => $login]);
                    if (!$existe->fetch()) {
                        json_out(['message' => 'Registro não encontrado.'], 404);
                    }
                }
            }
        } elseif ($acao === 'acessorio') {
            if ($method === 'POST') {
                $stmt = $pdo->prepare('INSERT INTO tb_acessorio (nome, login_usuario) VALUES (:nome, :login)');
                $stmt->execute(['nome' => $nome, 'login' => $login]);
            } else {
                $stmt = $pdo->prepare('UPDATE tb_acessorio SET nome = :nome WHERE id_acessorio = :id AND login_usuario = :login');
                $stmt->execute(['nome' => $nome, 'id' => $id, 'login' => $login]);
                if ($stmt->rowCount() === 0) {
                    $existe = $pdo->prepare('SELECT id_acessorio FROM tb_acessorio WHERE id_acessorio = :id AND login_usuario = :login');
                    $existe->execute(['id' => $id, 'login' => $login]);
                    if (!$existe->fetch()) {
                        json_out(['message' => 'Registro não encontrado.'], 404);
                    }
                }
            }
        } elseif ($acao === 'cor') {
            if (mb_strlen($nome) > 40) {
                json_out(['message' => 'O nome da cor deve ter no máximo 40 caracteres.', 'fields' => ['nome' => 'O nome da cor deve ter no máximo 40 caracteres.']], 400);
            }
            if ($method === 'POST') {
                $stmt = $pdo->prepare('INSERT INTO tb_cor (nome, login_usuario) VALUES (:nome, :login)');
                $stmt->execute(['nome' => $nome, 'login' => $login]);
            } else {
                $stmt = $pdo->prepare('UPDATE tb_cor SET nome = :nome WHERE id_cor = :id AND login_usuario = :login');
                $stmt->execute(['nome' => $nome, 'id' => $id, 'login' => $login]);
                if ($stmt->rowCount() === 0) {
                    $existe = $pdo->prepare('SELECT id_cor FROM tb_cor WHERE id_cor = :id AND login_usuario = :login');
                    $existe->execute(['id' => $id, 'login' => $login]);
                    if (!$existe->fetch()) {
                        json_out(['message' => 'Registro não encontrado.'], 404);
                    }
                }
                $pdo->prepare('UPDATE tb_carro SET cor = :nome WHERE id_cor = :id AND login_usuario = :login')
                    ->execute(['nome' => $nome, 'id' => $id, 'login' => $login]);
            }
        } else {
            json_out(['message' => 'Não foi possível salvar as informações.'], 400);
        }

        json_out(['message' => $method === 'POST' ? 'Cadastro realizado com sucesso.' : 'Cadastro atualizado com sucesso.']);
    }

    if ($method === 'DELETE') {
        if ($id <= 0) {
            json_out(['message' => 'Registro não encontrado.'], 404);
        }
        if ($acao === 'marca') {
            $stmt = $pdo->prepare('DELETE FROM tb_marca WHERE id_marca = :id AND login_usuario = :login');
        } elseif ($acao === 'modelo') {
            $stmt = $pdo->prepare('DELETE FROM tb_modelo WHERE id_modelo = :id AND login_usuario = :login');
        } elseif ($acao === 'acessorio') {
            $stmt = $pdo->prepare('DELETE FROM tb_acessorio WHERE id_acessorio = :id AND login_usuario = :login');
        } elseif ($acao === 'cor') {
            $uso = $pdo->prepare('SELECT COUNT(*) FROM tb_carro WHERE id_cor = :id AND login_usuario = :login');
            $uso->execute(['id' => $id, 'login' => $login]);
            if ((int) $uso->fetchColumn() > 0) {
                json_out(['message' => 'Esta cor está vinculada a veículos e não pode ser excluída.'], 409);
            }
            $stmt = $pdo->prepare('DELETE FROM tb_cor WHERE id_cor = :id AND login_usuario = :login');
        } else {
            json_out(['message' => 'Não foi possível excluir.'], 400);
        }
        $stmt->execute(['id' => $id, 'login' => $login]);
        if ($stmt->rowCount() === 0) {
            json_out(['message' => 'Registro não encontrado.'], 404);
        }
        json_out(['message' => 'Cadastro excluído com sucesso.']);
    }

    json_out(['message' => 'Operação não autorizada.'], 405);
} catch (PDOException $e) {
    app_log('auxiliares ' . $login . ' ' . $e->getCode() . ' ' . $e->getMessage());
    if ((string) $e->getCode() === '23000') {
        if (str_contains($e->getMessage(), 'fk_carro') || str_contains($e->getMessage(), 'tb_carro')) {
            json_out(['message' => 'Este cadastro está vinculado a veículos e não pode ser excluído.'], 409);
        }
        json_out(['message' => 'Esta informação já está cadastrada na sua conta.'], 409);
    }
    json_out(['message' => 'Falha ao salvar informações.'], 500);
}
