USE bd_revendedoracarros;

INSERT INTO tb_usuario (login, senha) VALUES ('admin', '123');

INSERT INTO tb_marca (nome) VALUES ('Toyota'), ('Honda'), ('Ford'), ('Fiat');

INSERT INTO tb_modelo (nome, id_marca) VALUES 
('Corolla', 1), ('Civic', 2), ('Ka', 3), ('Uno', 4);

INSERT INTO tb_acessorio (nome) VALUES 
('Ar Condicionado'), ('Roda de Liga Leve'), ('Vidro Elétrico'), 
('Banco de Couro'), ('Faróis de LED'), ('Sensor de Estacionamento');

INSERT INTO tb_carro (placa, chassi, cor, ano, valor, quilometragem, situacao, id_modelo) VALUES 
('ABC1234', 'SDF2DS1F', 'PRETO', 2022, 55000.00, 82000, 'D', 1),
('YUI1258', 'FDF1D1222', 'AZUL', 2026, 59000.00, 34100, 'D', 1),
('EDVB6512', 'SERWERW', 'AZUL', 2018, 150400.00, 84000, 'D', 3),
('TGN2483', 'DSDS6580', 'GRAFITE', 2026, 39000.00, 32000, 'D', 2);

INSERT INTO tb_carro_acessorio (id_carro, id_acessorio) VALUES 
(1, 1), (1, 2), (2, 1), (2, 3), (2, 4), (3, 5), (3, 6), (4, 1), (4, 3);
