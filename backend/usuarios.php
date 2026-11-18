<?php
require 'config.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->login) && !empty($data->senha)) {
    try {
        // Verifica se o login já existe
        $stmt = $pdo->prepare("SELECT * FROM tb_usuario WHERE login = :login");
        $stmt->execute(['login' => $data->login]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(["message" => "Este nome de usuário já está em uso."]);
            exit;
        }

        // Criptografa a senha e insere
        $hash = password_hash($data->senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO tb_usuario (login, senha) VALUES (:login, :senha)");
        $stmt->execute(['login' => $data->login, 'senha' => $hash]);

        echo json_encode(["message" => "Usuário cadastrado com sucesso!"]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => "Erro ao cadastrar usuário."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Preencha todos os campos."]);
}
?>
