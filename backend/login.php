<?php
require 'config.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->login) && !empty($data->senha)) {
    $stmt = $pdo->prepare("SELECT * FROM tb_usuario WHERE login = :login AND senha = :senha");
    $stmt->execute(['login' => $data->login, 'senha' => $data->senha]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(["message" => "Login realizado com sucesso", "user" => $user['login']]);
    } else {
        http_response_code(401);
        echo json_encode(["message" => "Usuário ou senha inválidos"]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Dados incompletos"]);
}
?>
