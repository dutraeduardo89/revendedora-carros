<?php
require 'config.php';
$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;
$login = $_SERVER['HTTP_X_USER_LOGIN'] ?? 'admin';

switch($method) {
    case 'GET':
        $stmt = $pdo->prepare("SELECT c.*, m.nome AS modelo, mar.nome AS marca, 
                               DATE_FORMAT(c.created_at, '%d/%m/%Y %H:%i') AS criado_em,
                               DATE_FORMAT(c.updated_at, '%d/%m/%Y %H:%i') AS editado_em
                               FROM tb_carro c
                               INNER JOIN tb_modelo m ON c.id_modelo = m.id_modelo
                               INNER JOIN tb_marca mar ON m.id_marca = mar.id_marca
                               WHERE c.login_usuario = :login
                               ORDER BY c.id_carro DESC");
        $stmt->execute(['login' => $login]);
        $carros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($carros as &$carro) {
            $stmtAc = $pdo->prepare("SELECT a.nome FROM tb_carro_acessorio ca INNER JOIN tb_acessorio a ON ca.id_acessorio = a.id_acessorio WHERE ca.id_carro = :id");
            $stmtAc->execute(['id' => $carro['id_carro']]);
            $carro['acessorios'] = $stmtAc->fetchAll(PDO::FETCH_COLUMN);
        }
        echo json_encode($carros);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO tb_carro (placa, chassi, cor, ano, valor, quilometragem, situacao, id_modelo, login_usuario) VALUES (:placa, :chassi, :cor, :ano, :valor, :km, 'D', :modelo, :login)");
            $stmt->execute([
                'placa' => strtoupper(trim($data->placa)), 'chassi' => strtoupper(trim($data->chassi)), 
                'cor' => ucfirst(strtolower(trim($data->cor))), 'ano' => $data->ano, 'valor' => $data->valor, 
                'km' => $data->quilometragem, 'modelo' => $data->id_modelo, 'login' => $login
            ]);
            $id_carro = $pdo->lastInsertId();
            if (!empty($data->acessorios)) {
                $stmtAc = $pdo->prepare("INSERT INTO tb_carro_acessorio (id_carro, id_acessorio) VALUES (:id_carro, :id_acessorio)");
                foreach ($data->acessorios as $id_ac) $stmtAc->execute(['id_carro' => $id_carro, 'id_acessorio' => $id_ac]);
            }
            $pdo->commit();
            echo json_encode(["message" => "Carro cadastrado com sucesso!"]);
        } catch (PDOException $e) {
            $pdo->rollBack(); http_response_code(400);
            echo json_encode(["message" => "Placa ou Chassi já cadastrados no sistema."]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE tb_carro SET placa=:placa, chassi=:chassi, cor=:cor, ano=:ano, valor=:valor, quilometragem=:km, situacao=:sit, id_modelo=:modelo WHERE id_carro=:id AND login_usuario=:login");
            $stmt->execute([
                'placa' => strtoupper(trim($data->placa)), 'chassi' => strtoupper(trim($data->chassi)), 
                'cor' => ucfirst(strtolower(trim($data->cor))), 'ano' => $data->ano, 'valor' => $data->valor, 
                'km' => $data->quilometragem, 'sit' => $data->situacao, 'modelo' => $data->id_modelo, 
                'id' => $data->id_carro, 'login' => $login
            ]);
            
            // Atualiza Acessórios (Deleta os antigos, insere os novos)
            $pdo->prepare("DELETE FROM tb_carro_acessorio WHERE id_carro = :id")->execute(['id' => $data->id_carro]);
            if (!empty($data->acessorios)) {
                $stmtAc = $pdo->prepare("INSERT INTO tb_carro_acessorio (id_carro, id_acessorio) VALUES (:id_carro, :id_acessorio)");
                foreach ($data->acessorios as $id_ac) $stmtAc->execute(['id_carro' => $data->id_carro, 'id_acessorio' => $id_ac]);
            }
            $pdo->commit();
            echo json_encode(["message" => "Carro atualizado com sucesso!"]);
        } catch (PDOException $e) {
            $pdo->rollBack(); http_response_code(400);
            echo json_encode(["message" => "Erro: " . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        try {
            $stmt = $pdo->prepare("DELETE FROM tb_carro WHERE id_carro=:id AND login_usuario=:login");
            $stmt->execute(['id' => $id, 'login' => $login]);
            echo json_encode(["message" => "Veículo excluído com sucesso!"]);
        } catch (PDOException $e) {
            http_response_code(400);
            echo json_encode(["message" => "Erro ao excluir."]);
        }
        break;
}
?>
