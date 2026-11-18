<?php
require 'config.php';
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->login) && !empty($data->senha_atual) && !empty($data->nova_senha)) {
    try {
        $stmt = $pdo->prepare("SELECT senha FROM tb_usuario WHERE login = :login");
        $stmt->execute(['login' => $data->login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($data->senha_atual, $user['senha'])) {
            $hash = password_hash($data->nova_senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE tb_usuario SET senha = :senha WHERE login = :login");
            $stmt->execute(['senha' => $hash, 'login' => $data->login]);
            echo json_encode(["message" => "Senha atualizada com sucesso!"]);
        } else {
            http_response_code(401);
            echo json_encode(["message" => "A senha atual está incorreta."]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["message" => "Erro no servidor: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Dados incompletos."]);
}
?>
