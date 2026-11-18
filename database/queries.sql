USE bd_revendedoracarros;
-- Query 1: Join 
SELECT c.id_carro,
       c.placa,
       a.id_acessorio,
       a.nome AS descricao_acessorio,
       ca.created_at AS data_vinculo
FROM tb_carro_acessorio ca
       INNER JOIN tb_carro c ON ca.id_carro = c.id_carro
       INNER JOIN tb_acessorio a ON ca.id_acessorio = a.id_acessorio;
-- Query 2: Carros com Modelo, Marca e Timestamps
SELECT c.id_carro,
       c.placa,
       c.ano,
       c.valor,
       m.nome AS modelo,
       mar.nome AS marca,
       c.created_at AS criado_em,
       c.updated_at AS editado_em
FROM tb_carro c
       INNER JOIN tb_modelo m ON c.id_modelo = m.id_modelo
       INNER JOIN tb_marca mar ON m.id_marca = mar.id_marca;