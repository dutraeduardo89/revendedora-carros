<?php
require 'config.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->login) && !empty($data->senha)) {
    $stmt = $pdo->prepare("SELECT * FROM tb_usuario WHERE login = :login");
    $stmt->execute(['login' => $data->login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verifica a senha usando a função correta do PHP
    if ($user && password_verify($data->senha, $user['senha'])) {
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
