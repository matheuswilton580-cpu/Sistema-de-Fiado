Claude
Banco infinityfree · SQL
-- =====================================================================
-- Sistema de Fiado - banco para InfinityFree (SEM stored procedures)
--
-- COMO USAR:
--   1. No phpMyAdmin, SELECIONE o banco (ex.: if0_43295038_fiado)
--   2. Vá em "Importar" e envie este arquivo
--
-- As tabelas usam CREATE TABLE IF NOT EXISTS, então pode importar mesmo
-- que elas já existam. ATENÇÃO: os INSERTs de exemplo no final NÃO
-- verificam duplicidade. Importe este arquivo só uma vez com as tabelas
-- vazias (se der erro no meio e precisar repetir, apague as tabelas antes).
--
-- As antigas procedures sp_relatorio_clientes e sp_registrar_compra
-- agora são funções PHP (veja funcoes_fiado.php).
-- =====================================================================
 
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
 
-- ---------------------------------------------------------------------
-- TABELAS
-- ---------------------------------------------------------------------
 
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    telefone VARCHAR(20),
    endereco VARCHAR(200),
    limite_credito DECIMAL(10,2) DEFAULT 500.00,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel ENUM('admin','comum','cliente') NOT NULL DEFAULT 'comum',
    status ENUM('pendente','aprovado','recusado','bloqueado') NOT NULL DEFAULT 'aprovado',
    cliente_id INT NULL,
    telefone_solicitado VARCHAR(20) NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
CREATE TABLE IF NOT EXISTS compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    descricao VARCHAR(200),
    valor DECIMAL(10,2) NOT NULL,
    data_compra DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
CREATE TABLE IF NOT EXISTS pagamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_pagamento DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
SET FOREIGN_KEY_CHECKS = 1;
 
-- ---------------------------------------------------------------------
-- DADOS DE EXEMPLO (use só na PRIMEIRA importação)
-- ---------------------------------------------------------------------
-- Usuários (senhas criptografadas com password_hash do PHP)
--   admin   | senha: admin123 | nível: admin
--   usuario | senha: user123  | nível: comum
-- IMPORTANTE: troque essas senhas depois que o site estiver no ar.
INSERT INTO usuarios (nome, usuario, senha, nivel) VALUES
('Administrador', 'admin',   '$2y$10$MJxxI0F.bM2VFWJck879Vu0Hjgf9SUqQP3EKnVxW0uqApbfxhlO/S', 'admin'),
('Atendente',     'usuario', '$2y$10$0HMHeAXinJ1McZeqFdMlTuNUpoM9n/jHCTstb0Kb/KVZJ.b9Q/dqy', 'comum');
 
INSERT INTO clientes (nome, telefone, endereco, limite_credito) VALUES
('João Silva',     '(62) 99999-9999', 'Rua das Flores, 123', 500.00),
('Maria Souza',    '(62) 98888-8888', 'Av. Goiás, 456',      300.00),
('Carlos Pereira', '(62) 97777-7777', 'Rua 10, 89',          400.00),
('Ana Lima',       '(62) 96666-6666', 'Rua 5, 22',           200.00);
 
INSERT INTO compras (cliente_id, descricao, valor, data_compra) VALUES
(1, 'Arroz e feijão',  45.00, NOW() - INTERVAL 10 DAY),
(1, 'Refrigerante',    15.00, NOW() - INTERVAL 5 DAY),
(2, 'Pão e manteiga',  20.00, NOW() - INTERVAL 3 DAY),
(3, 'Compra do mês',  180.00, NOW() - INTERVAL 45 DAY),
(4, 'Frios',           60.00, NOW() - INTERVAL 20 DAY);
 
INSERT INTO pagamentos (cliente_id, valor, data_pagamento) VALUES
(2, 10.00, NOW() - INTERVAL 1 DAY),
(3, 50.00, NOW() - INTERVAL 30 DAY),
(4, 60.00, NOW() - INTERVAL 15 DAY);
 


Claude terminou a resposta
