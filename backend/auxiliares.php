<?php
require 'config.php';
$method = $_SERVER['REQUEST_METHOD'];
$acao = $_GET['acao'] ?? '';
$id = $_GET['id'] ?? null;
$login = $_SERVER['HTTP_X_USER_LOGIN'] ?? 'admin'; // Pega o usuário logado

if ($method == 'GET') {
    if ($acao == 'marcas') {
        $stmt = $pdo->prepare("SELECT * FROM tb_marca WHERE login_usuario = :login ORDER BY nome");
        $stmt->execute(['login' => $login]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } elseif ($acao == 'modelos') {
        $stmt = $pdo->prepare("SELECT m.*, ma.nome AS marca_nome FROM tb_modelo m INNER JOIN tb_marca ma ON m.id_marca = ma.id_marca WHERE m.login_usuario = :login ORDER BY ma.nome, m.nome");
        $stmt->execute(['login' => $login]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } elseif ($acao == 'acessorios') {
        $stmt = $pdo->prepare("SELECT * FROM tb_acessorio WHERE login_usuario = :login ORDER BY nome");
        $stmt->execute(['login' => $login]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    exit;
}

if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    $nomeFormatado = ucwords(strtolower(trim($data->nome)));
    try {
        if ($acao == 'marca') {
            $stmt = $pdo->prepare("INSERT INTO tb_marca (nome, login_usuario) VALUES (:nome, :login)");
            $stmt->execute(['nome' => $nomeFormatado, 'login' => $login]);
        } elseif ($acao == 'modelo') {
            $stmt = $pdo->prepare("INSERT INTO tb_modelo (nome, id_marca, login_usuario) VALUES (:nome, :id_marca, :login)");
            $stmt->execute(['nome' => $nomeFormatado, 'id_marca' => $data->id_marca, 'login' => $login]);
        } elseif ($acao == 'acessorio') {
            $stmt = $pdo->prepare("INSERT INTO tb_acessorio (nome, login_usuario) VALUES (:nome, :login)");
            $stmt->execute(['nome' => $nomeFormatado, 'login' => $login]);
        }
        echo json_encode(["message" => "Cadastrado com sucesso!"]);
    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode(["message" => "Este nome já existe na sua conta."]);
    }
    exit;
}

if ($method == 'DELETE') {
    try {
        if ($acao == 'marca') $stmt = $pdo->prepare("DELETE FROM tb_marca WHERE id_marca=:id AND login_usuario=:login");
        elseif ($acao == 'modelo') $stmt = $pdo->prepare("DELETE FROM tb_modelo WHERE id_modelo=:id AND login_usuario=:login");
        elseif ($acao == 'acessorio') $stmt = $pdo->prepare("DELETE FROM tb_acessorio WHERE id_acessorio=:id AND login_usuario=:login");
        $stmt->execute(['id' => $id, 'login' => $login]);
        echo json_encode(["message" => "Excluído com sucesso!"]);
    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode(["message" => "Erro ao excluir."]);
    }
    exit;
}
?>
