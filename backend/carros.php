<?php
require 'config.php';
$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Listar todos os carros com JOIN e Timestamps
        $stmt = $pdo->prepare("SELECT c.*, m.nome AS modelo, mar.nome AS marca, 
                               DATE_FORMAT(c.created_at, '%d/%m/%Y %H:%i') AS criado_em,
                               DATE_FORMAT(c.updated_at, '%d/%m/%Y %H:%i') AS editado_em
                               FROM tb_carro c
                               INNER JOIN tb_modelo m ON c.id_modelo = m.id_modelo
                               INNER JOIN tb_marca mar ON m.id_marca = mar.id_marca
                               ORDER BY c.id_carro DESC");
        $stmt->execute();
        $carros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($carros);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        // Insere o carro
        $stmt = $pdo->prepare("INSERT INTO tb_carro (placa, chassi, cor, ano, valor, quilometragem, situacao, id_modelo) VALUES (:placa, :chassi, :cor, :ano, :valor, :km, :sit, :modelo)");
        try {
            $stmt->execute([
                'placa' => $data->placa, 'chassi' => $data->chassi, 'cor' => $data->cor,
                'ano' => $data->ano, 'valor' => $data->valor, 'km' => $data->quilometragem,
                'sit' => 'D', 'modelo' => $data->id_modelo
            ]);
            echo json_encode(["message" => "Carro cadastrado"]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["message" => "Erro ao cadastrar", "error" => $e->getMessage()]);
        }
        break;
}
?>
