-- Script de criação do banco de dados
-- Sistema de Gestão Financeira e Controle de Bens (SaaS Multi-Tenant)

-- Tabela: Empresa (Tenant)
CREATE TABLE rmg_empresa (
    id_empresa INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    razao_social VARCHAR(200) NOT NULL,
    nome_fantasia VARCHAR(200),
    cnpj VARCHAR(20),
    telefone VARCHAR(20),
    email VARCHAR(100),
    ativa BOOLEAN NOT NULL DEFAULT TRUE,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    observacoes TEXT
);

-- Tabela: Usuario
-- tipo_usuario: 'super_admin' = administrador geral do SaaS (sem empresa)
--               'gerente' = gerente de uma empresa (pode criar operadores)
--               'operador' = operador de uma empresa
CREATE TABLE rmg_usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NULL,
    nome VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('super_admin', 'gerente', 'operador') NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_empresa FOREIGN KEY (empresa_id) REFERENCES rmg_empresa(id_empresa),
    UNIQUE KEY uk_usuario_empresa (usuario, empresa_id)
);

-- Tabela: Setor
CREATE TABLE rmg_setor (
    id_setor INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    CONSTRAINT fk_setor_empresa FOREIGN KEY (empresa_id) REFERENCES rmg_empresa(id_empresa)
);

-- Tabela: Fornecedor
CREATE TABLE rmg_fornecedor (
    id_fornecedor INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cnpj VARCHAR(20),
    telefone VARCHAR(20),
    email VARCHAR(100),
    observacoes TEXT,
    CONSTRAINT fk_fornecedor_empresa FOREIGN KEY (empresa_id) REFERENCES rmg_empresa(id_empresa)
);

-- Tabela: Cliente
CREATE TABLE rmg_cliente (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cpf_cnpj VARCHAR(20),
    telefone VARCHAR(20),
    email VARCHAR(100),
    observacoes TEXT,
    CONSTRAINT fk_cliente_empresa FOREIGN KEY (empresa_id) REFERENCES rmg_empresa(id_empresa)
);

-- Tabela: Bem
CREATE TABLE rmg_bem (
    id_bem INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    descricao VARCHAR(200) NOT NULL,
    setor_id INT,
    data_aquisicao DATE,
    valor_aquisicao DECIMAL(10, 2),
    status ENUM('ativo', 'baixado') DEFAULT 'ativo',
    observacoes TEXT,
    CONSTRAINT fk_bem_setor FOREIGN KEY (setor_id) REFERENCES rmg_setor(id_setor),
    CONSTRAINT fk_bem_empresa FOREIGN KEY (empresa_id) REFERENCES rmg_empresa(id_empresa)
);

-- Tabela: Manutencao
CREATE TABLE rmg_manutencao (
    id_manutencao INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    bem_id INT NOT NULL,
    data_manutencao DATE NOT NULL,
    descricao TEXT,
    custo DECIMAL(10, 2) NOT NULL,
    observacoes TEXT,
    CONSTRAINT fk_manutencao_bem FOREIGN KEY (bem_id) REFERENCES rmg_bem(id_bem),
    CONSTRAINT fk_manutencao_empresa FOREIGN KEY (empresa_id) REFERENCES rmg_empresa(id_empresa)
);

-- Tabela: ContaPagar
CREATE TABLE rmg_conta_pagar (
    id_conta_pagar INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    fornecedor_id INT NOT NULL,
    descricao VARCHAR(200) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    data_vencimento DATE NOT NULL,
    status ENUM('pendente', 'paga') DEFAULT 'pendente',
    observacoes TEXT,
    CONSTRAINT fk_conta_pagar_fornecedor FOREIGN KEY (fornecedor_id) REFERENCES rmg_fornecedor(id_fornecedor),
    CONSTRAINT fk_conta_pagar_empresa FOREIGN KEY (empresa_id) REFERENCES rmg_empresa(id_empresa)
);

-- Tabela: ContaReceber
CREATE TABLE rmg_conta_receber (
    id_conta_receber INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    cliente_id INT NOT NULL,
    descricao VARCHAR(200) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    data_vencimento DATE NOT NULL,
    status ENUM('pendente', 'recebida') DEFAULT 'pendente',
    observacoes TEXT,
    CONSTRAINT fk_conta_receber_cliente FOREIGN KEY (cliente_id) REFERENCES rmg_cliente(id_cliente),
    CONSTRAINT fk_conta_receber_empresa FOREIGN KEY (empresa_id) REFERENCES rmg_empresa(id_empresa)
);

-- Tabela: Pagamento
CREATE TABLE rmg_pagamento (
    id_pagamento INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    conta_pagar_id INT NOT NULL,
    data_pagamento DATE NOT NULL,
    valor_pago DECIMAL(10, 2) NOT NULL,
    CONSTRAINT fk_pagamento_conta_pagar FOREIGN KEY (conta_pagar_id) REFERENCES rmg_conta_pagar(id_conta_pagar),
    CONSTRAINT fk_pagamento_empresa FOREIGN KEY (empresa_id) REFERENCES rmg_empresa(id_empresa)
);

-- Tabela: Recebimento
CREATE TABLE rmg_recebimento (
    id_recebimento INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    conta_receber_id INT NOT NULL,
    data_recebimento DATE NOT NULL,
    valor_recebido DECIMAL(10, 2) NOT NULL,
    CONSTRAINT fk_recebimento_conta_receber FOREIGN KEY (conta_receber_id) REFERENCES rmg_conta_receber(id_conta_receber),
    CONSTRAINT fk_recebimento_empresa FOREIGN KEY (empresa_id) REFERENCES rmg_empresa(id_empresa)
);

-- Tabela: Log do Sistema (registro de todas as movimentações)
CREATE TABLE rmg_log (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NULL,
    usuario_id INT NULL,
    usuario_nome VARCHAR(100),
    tabela VARCHAR(50) NOT NULL,
    acao ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    registro_id INT NULL,
    descricao TEXT,
    dados_anteriores TEXT NULL,
    dados_novos TEXT NULL,
    ip VARCHAR(45),
    data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_empresa FOREIGN KEY (empresa_id) REFERENCES rmg_empresa(id_empresa) ON DELETE SET NULL,
    CONSTRAINT fk_log_usuario FOREIGN KEY (usuario_id) REFERENCES rmg_usuario(id_usuario) ON DELETE SET NULL,
    INDEX idx_log_empresa (empresa_id),
    INDEX idx_log_tabela (tabela),
    INDEX idx_log_acao (acao),
    INDEX idx_log_data_hora (data_hora)
);

-- Inserção do Super Administrador do SaaS (Senha: admin123)
-- Este usuário não possui empresa_id (é o dono da plataforma)
INSERT INTO rmg_usuario (empresa_id, nome, usuario, senha, tipo_usuario, ativo) VALUES 
(NULL, 'Administrador SaaS', 'admin', '$2y$10$w9lHUKAQRvDqCUkm959kvO2hmitAWFkdcV0mfCkpHMvzBOG5kSQ9S', 'super_admin', 1);
