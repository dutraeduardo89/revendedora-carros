CREATE DATABASE IF NOT EXISTS bd_revendedoracarros;
USE bd_revendedoracarros;
-- Tabela de Usuário
CREATE TABLE tb_usuario (
    login VARCHAR(50) PRIMARY KEY,
    senha VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
-- Tabela de Marca
CREATE TABLE tb_marca (
    id_marca INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
-- Tabela de Modelo
CREATE TABLE tb_modelo (
    id_modelo INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    id_marca INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_marca) REFERENCES tb_marca(id_marca)
);
-- Tabela de Acessório
CREATE TABLE tb_acessorio (
    id_acessorio INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
-- Tabela de Carro
CREATE TABLE tb_carro (
    id_carro INT AUTO_INCREMENT PRIMARY KEY,
    placa VARCHAR(10) NOT NULL UNIQUE,
    chassi VARCHAR(30) NOT NULL UNIQUE,
    cor VARCHAR(30) NOT NULL,
    ano INT NOT NULL,
    valor DECIMAL(10, 2) NOT NULL CHECK (valor > 0),
    quilometragem INT NOT NULL CHECK (quilometragem >= 0),
    situacao ENUM('D', 'V') NOT NULL,
    id_modelo INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_modelo) REFERENCES tb_modelo(id_modelo)
);
-- Tabela Pivô
CREATE TABLE tb_carro_acessorio (
    id_carro INT NOT NULL,
    id_acessorio INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_carro, id_acessorio),
    FOREIGN KEY (id_carro) REFERENCES tb_carro(id_carro) ON DELETE CASCADE,
    FOREIGN KEY (id_acessorio) REFERENCES tb_acessorio(id_acessorio) ON DELETE CASCADE
);
-- Tabela Trabalhe Conosco
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