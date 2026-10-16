-- Script de Criação e População do Banco de Dados
-- Sistema SAEP - Gestão de Estoque
-- Banco de dados: saep_db

CREATE DATABASE IF NOT EXISTS saep_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE saep_db;

-- 1. Tabela de Usuários
DROP TABLE IF EXISTS movimentacoes;
DROP TABLE IF EXISTS produtos;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL
);

-- 2. Tabela de Produtos
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nome VARCHAR(100) NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    descricao TEXT,
    tamanho VARCHAR(50),
    peso VARCHAR(50),
    quantidade_estoque INT NOT NULL DEFAULT 0,
    estoque_minimo INT NOT NULL DEFAULT 5
);

-- 3. Tabela de Movimentações de Estoque
CREATE TABLE movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo ENUM('entrada', 'saida') NOT NULL,
    quantidade INT NOT NULL,
    data_movimentacao DATE NOT NULL,
    data_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- População inicial com no mínimo três registros por tabela

-- Registros de Usuários
INSERT INTO usuarios (id, nome, email, senha) VALUES
(1, 'Administrador do Almoxarifado', 'admin@saep.com', '123456'),
(2, 'Carlos Silva', 'carlos@saep.com', '123456'),
(3, 'Mariana Costa', 'mariana@saep.com', '123456');

-- Registros de Produtos (ferramentas e equipamentos manuais conforme o contexto do desafio)
INSERT INTO produtos (id, codigo, nome, categoria, descricao, tamanho, peso, quantidade_estoque, estoque_minimo) VALUES
(1, 'FER-001', 'Martelo de Unha 25mm Cabo Fibra', 'Martelo', 'Cabeça de aço forjado e cabo de fibra com empunhadura emborrachada', '25mm / 30cm', '550g', 15, 5),
(2, 'FER-002', 'Chave de Fenda Isolada 1/4x6', 'Chave de Fenda', 'Haste em cromo vanádio com revestimento isolante 1000V', '1/4 x 6 polegadas', '120g', 8, 10),
(3, 'FER-003', 'Chave Phillips Imantada PH2x100', 'Chave Phillips', 'Ponta imantada com cabo ergonômico', 'PH2 x 100mm', '95g', 20, 8);

-- Registros de Movimentações
INSERT INTO movimentacoes (id, produto_id, usuario_id, tipo, quantidade, data_movimentacao) VALUES
(1, 1, 1, 'entrada', 15, '2026-10-10'),
(2, 2, 2, 'entrada', 12, '2026-10-11'),
(3, 2, 2, 'saida', 4, '2026-10-12');
