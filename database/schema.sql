CREATE DATABASE IF NOT EXISTS bd_revendedoracarros CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE bd_revendedoracarros;

CREATE TABLE tb_usuario (
    login VARCHAR(50) PRIMARY KEY,
    senha VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE tb_marca (
    id_marca INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    login_usuario VARCHAR(50) NOT NULL,
    UNIQUE KEY uk_marca_usuario (login_usuario, nome),
    CONSTRAINT fk_tb_marca_user FOREIGN KEY (login_usuario) REFERENCES tb_usuario (login) ON DELETE CASCADE
);

CREATE TABLE tb_modelo (
    id_modelo INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    id_marca INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    login_usuario VARCHAR(50) NOT NULL,
    UNIQUE KEY uk_modelo (nome, id_marca),
    CONSTRAINT fk_modelo_marca FOREIGN KEY (id_marca) REFERENCES tb_marca (id_marca) ON DELETE CASCADE,
    CONSTRAINT fk_tb_modelo_user FOREIGN KEY (login_usuario) REFERENCES tb_usuario (login) ON DELETE CASCADE
);

CREATE TABLE tb_acessorio (
    id_acessorio INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    login_usuario VARCHAR(50) NOT NULL,
    UNIQUE KEY uk_acessorio_usuario (login_usuario, nome),
    CONSTRAINT fk_tb_acessorio_user FOREIGN KEY (login_usuario) REFERENCES tb_usuario (login) ON DELETE CASCADE
);

CREATE TABLE tb_cor (
    id_cor INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(40) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    login_usuario VARCHAR(50) NOT NULL,
    UNIQUE KEY uk_cor_usuario (login_usuario, nome),
    CONSTRAINT fk_tb_cor_user FOREIGN KEY (login_usuario) REFERENCES tb_usuario (login) ON DELETE CASCADE
);

CREATE TABLE tb_carro (
    id_carro INT AUTO_INCREMENT PRIMARY KEY,
    placa VARCHAR(10) NOT NULL,
    chassi VARCHAR(30) NOT NULL,
    cor VARCHAR(30) NOT NULL,
    id_cor INT NULL,
    ano INT NOT NULL,
    ano_modelo INT NOT NULL,
    versao VARCHAR(80) NULL,
    categoria VARCHAR(40) NULL,
    combustivel VARCHAR(30) NULL,
    transmissao VARCHAR(30) NULL,
    motorizacao VARCHAR(40) NULL,
    portas TINYINT NULL,
    passageiros TINYINT NULL,
    condicao ENUM('novo', 'seminovo', 'usado', 'velho') NULL,
    conservacao ENUM('otima', 'boa', 'regular', 'ruim') NULL,
    historico VARCHAR(500) NULL,
    valor DECIMAL(10, 2) NOT NULL,
    quilometragem INT NOT NULL,
    situacao ENUM('D', 'V', 'R') NOT NULL,
    id_modelo INT NOT NULL,
    login_usuario VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_placa (placa),
    UNIQUE KEY uk_chassi (chassi),
    CONSTRAINT fk_carro_modelo FOREIGN KEY (id_modelo) REFERENCES tb_modelo (id_modelo) ON DELETE CASCADE,
    CONSTRAINT fk_carro_usuario FOREIGN KEY (login_usuario) REFERENCES tb_usuario (login) ON DELETE CASCADE,
    CONSTRAINT fk_carro_cor FOREIGN KEY (id_cor) REFERENCES tb_cor (id_cor) ON DELETE SET NULL,
    CONSTRAINT tb_carro_chk_valor CHECK (valor > 0),
    CONSTRAINT tb_carro_chk_km CHECK (quilometragem >= 0),
    CONSTRAINT tb_carro_chk_anos CHECK (ano_modelo >= ano AND ano_modelo <= ano + 1)
);

CREATE TABLE tb_carro_acessorio (
    id_carro INT NOT NULL,
    id_acessorio INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_carro, id_acessorio),
    CONSTRAINT fk_ca_carro FOREIGN KEY (id_carro) REFERENCES tb_carro (id_carro) ON DELETE CASCADE,
    CONSTRAINT fk_ca_acessorio FOREIGN KEY (id_acessorio) REFERENCES tb_acessorio (id_acessorio) ON DELETE CASCADE
);

CREATE TABLE tb_config_classificacao (
    login_usuario VARCHAR(50) PRIMARY KEY,
    novo_km_max INT NOT NULL DEFAULT 0,
    seminovo_km_max INT NOT NULL DEFAULT 30000,
    velho_idade_min INT NOT NULL DEFAULT 15,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_config_user FOREIGN KEY (login_usuario) REFERENCES tb_usuario (login) ON DELETE CASCADE
);

CREATE TABLE tb_sessao (
    id_sessao INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_token (token_hash),
    KEY idx_sessao_login (login),
    CONSTRAINT fk_sessao_user FOREIGN KEY (login) REFERENCES tb_usuario (login) ON DELETE CASCADE
);

CREATE TABLE tb_trabalheconosco (
    id_candidato INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    email VARCHAR(50) NOT NULL,
    celular VARCHAR(15),
    cidade VARCHAR(50),
    estado VARCHAR(2),
    cargo_pretendido VARCHAR(50),
    link_linkedin VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
