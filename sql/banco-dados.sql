-- Script de criação do banco de dados
-- Sistema de Gestão Financeira e Controle de Bens

-- Tabela: Usuario
CREATE TABLE rmg_usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('administrador', 'gerente', 'operador') NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabela: Setor
CREATE TABLE rmg_setor (
    id_setor INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT
);

-- Tabela: Fornecedor
CREATE TABLE rmg_fornecedor (
    id_fornecedor INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cnpj VARCHAR(20),
    telefone VARCHAR(20),
    email VARCHAR(100),
    observacoes TEXT
);

-- Tabela: Cliente
CREATE TABLE rmg_cliente (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf_cnpj VARCHAR(20),
    telefone VARCHAR(20),
    email VARCHAR(100),
    observacoes TEXT
);

-- Tabela: Bem
CREATE TABLE rmg_bem (
    id_bem INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(200) NOT NULL,
    setor_id INT,
    data_aquisicao DATE,
    valor_aquisicao DECIMAL(10, 2),
    status ENUM('ativo', 'baixado') DEFAULT 'ativo',
    observacoes TEXT,
    CONSTRAINT fk_bem_setor FOREIGN KEY (setor_id) REFERENCES rmg_setor(id_setor)
);

-- Tabela: Manutencao
CREATE TABLE rmg_manutencao (
    id_manutencao INT AUTO_INCREMENT PRIMARY KEY,
    bem_id INT NOT NULL,
    data_manutencao DATE NOT NULL,
    descricao TEXT,
    custo DECIMAL(10, 2) NOT NULL,
    observacoes TEXT,
    CONSTRAINT fk_manutencao_bem FOREIGN KEY (bem_id) REFERENCES rmg_bem(id_bem)
);

-- Tabela: ContaPagar
CREATE TABLE rmg_conta_pagar (
    id_conta_pagar INT AUTO_INCREMENT PRIMARY KEY,
    fornecedor_id INT NOT NULL,
    descricao VARCHAR(200) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    data_vencimento DATE NOT NULL,
    status ENUM('pendente', 'paga') DEFAULT 'pendente',
    observacoes TEXT,
    CONSTRAINT fk_conta_pagar_fornecedor FOREIGN KEY (fornecedor_id) REFERENCES rmg_fornecedor(id_fornecedor)
);

-- Tabela: ContaReceber
CREATE TABLE rmg_conta_receber (
    id_conta_receber INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    descricao VARCHAR(200) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    data_vencimento DATE NOT NULL,
    status ENUM('pendente', 'recebida') DEFAULT 'pendente',
    observacoes TEXT,
    CONSTRAINT fk_conta_receber_cliente FOREIGN KEY (cliente_id) REFERENCES rmg_cliente(id_cliente)
);

-- Tabela: Pagamento
CREATE TABLE rmg_pagamento (
    id_pagamento INT AUTO_INCREMENT PRIMARY KEY,
    conta_pagar_id INT NOT NULL,
    data_pagamento DATE NOT NULL,
    valor_pago DECIMAL(10, 2) NOT NULL,
    CONSTRAINT fk_pagamento_conta_pagar FOREIGN KEY (conta_pagar_id) REFERENCES rmg_conta_pagar(id_conta_pagar)
);

-- Tabela: Recebimento
CREATE TABLE rmg_recebimento (
    id_recebimento INT AUTO_INCREMENT PRIMARY KEY,
    conta_receber_id INT NOT NULL,
    data_recebimento DATE NOT NULL,
    valor_recebido DECIMAL(10, 2) NOT NULL,
    CONSTRAINT fk_recebimento_conta_receber FOREIGN KEY (conta_receber_id) REFERENCES rmg_conta_receber(id_conta_receber)
);

-- Inserção de usuário administrador padrão (Senha: admin123)
-- Hash gerado para 'admin123'
INSERT INTO rmg_usuario (nome, usuario, senha, tipo_usuario, ativo) VALUES 
('Administrador', 'admin', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa', 'administrador', 1);
