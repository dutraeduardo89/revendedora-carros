-- Seed para banco vazio, depois de schema.sql.
-- A senha inicial do usuário admin é 1234 e está armazenada com password_hash.
USE bd_revendedoracarros;

INSERT INTO tb_usuario (login, senha) VALUES
('admin', '$2y$12$O2f8a6Amq8gE7JP2Qq/2V.7B1IgU47TDtr.3Nn9OVkZb7PgoPNMwC');

INSERT INTO tb_config_classificacao (login_usuario, novo_km_max, seminovo_km_max, velho_idade_min) VALUES
('admin', 0, 30000, 15);

INSERT INTO tb_marca (nome, login_usuario) VALUES
('Toyota', 'admin'), ('Honda', 'admin'), ('Ford', 'admin'), ('Fiat', 'admin');

INSERT INTO tb_modelo (nome, id_marca, login_usuario) VALUES
('Corolla', 1, 'admin'), ('Civic', 2, 'admin'), ('Ka', 3, 'admin'), ('Uno', 4, 'admin');

INSERT INTO tb_acessorio (nome, login_usuario) VALUES
('Ar Condicionado', 'admin'), ('Roda de Liga Leve', 'admin'), ('Vidro Elétrico', 'admin'),
('Banco de Couro', 'admin'), ('Faróis de LED', 'admin'), ('Sensor de Estacionamento', 'admin');

INSERT INTO tb_cor (nome, login_usuario) VALUES
('Branco', 'admin'), ('Preto', 'admin'), ('Prata', 'admin'), ('Cinza', 'admin'),
('Vermelho', 'admin'), ('Azul', 'admin'), ('Verde', 'admin'), ('Marrom', 'admin'), ('Bege', 'admin');

INSERT INTO tb_carro (
    placa, chassi, cor, id_cor, ano, ano_modelo, categoria, combustivel, transmissao, motorizacao,
    portas, passageiros, condicao, conservacao, valor, quilometragem, situacao, id_modelo, login_usuario
) VALUES
('ABC1D23', '9BWZZZ377VT004251', 'Preto', 2, 2022, 2023, 'Sedan', 'Flex', 'Automático', '2.0', 4, 5, 'usado', 'boa', 55000.00, 82000, 'D', 1, 'admin');

INSERT INTO tb_carro_acessorio (id_carro, id_acessorio) VALUES (1, 1), (1, 2);
