SET NAMES utf8;
DROP DATABASE IF EXISTS sistema_fiado;
CREATE DATABASE sistema_fiado CHARACTER SET utf8 COLLATE utf8_general_ci;
USE sistema_fiado;

-- Tabela de clientes
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    telefone VARCHAR(20),
    endereco VARCHAR(200),
    limite_credito DECIMAL(10,2) DEFAULT 500.00,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de usuários (nível admin/comum/cliente, status da solicitação e vínculo com o cliente)
CREATE TABLE usuarios (
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
);

-- Tabela de compras (fiado)
CREATE TABLE compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    descricao VARCHAR(200),
    valor DECIMAL(10,2) NOT NULL,
    data_compra DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);

-- Tabela de pagamentos
CREATE TABLE pagamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_pagamento DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- STORED PROCEDURES
-- ---------------------------------------------------------------------
DELIMITER $$

-- SP 1 (consulta/relatório complexo):
-- Lista os clientes com total de compras e pagamentos no período, saldo
-- devedor, dias devendo e situação (em_dia | devendo | inadimplente).
-- Datas NULL = sem filtro de período. p_status 'todos' = sem filtro de status.
CREATE PROCEDURE sp_relatorio_clientes(
    IN p_data_ini DATE,
    IN p_data_fim DATE,
    IN p_status   VARCHAR(20)
)
BEGIN
    DECLARE v_ini DATE DEFAULT '1000-01-01';
    DECLARE v_fim DATE DEFAULT '9999-12-31';
    DECLARE v_filtra_periodo TINYINT DEFAULT 0;

    IF p_data_ini IS NOT NULL THEN SET v_ini = p_data_ini; SET v_filtra_periodo = 1; END IF;
    IF p_data_fim IS NOT NULL THEN SET v_fim = p_data_fim; SET v_filtra_periodo = 1; END IF;

    SELECT r.*
    FROM (
        SELECT t.*,
               CASE
                   WHEN t.saldo_devedor <= 0 THEN 'em_dia'
                   WHEN t.dias_devendo > 30  THEN 'inadimplente'
                   ELSE 'devendo'
               END AS situacao
        FROM (
            SELECT c.id, c.nome, c.telefone, c.limite_credito,
                   COALESCE((SELECT SUM(co.valor) FROM compras co
                             WHERE co.cliente_id = c.id
                               AND DATE(co.data_compra) BETWEEN v_ini AND v_fim), 0) AS compras_periodo,
                   COALESCE((SELECT SUM(p.valor) FROM pagamentos p
                             WHERE p.cliente_id = c.id
                               AND DATE(p.data_pagamento) BETWEEN v_ini AND v_fim), 0) AS pagamentos_periodo,
                   COALESCE((SELECT SUM(co2.valor) FROM compras co2 WHERE co2.cliente_id = c.id), 0)
                   - COALESCE((SELECT SUM(p2.valor) FROM pagamentos p2 WHERE p2.cliente_id = c.id), 0)
                   AS saldo_devedor,
                   COALESCE(DATEDIFF(NOW(),
                       (SELECT MIN(co3.data_compra) FROM compras co3 WHERE co3.cliente_id = c.id)), 0)
                   AS dias_devendo
            FROM clientes c
        ) t
    ) r
    WHERE (p_status = 'todos' OR r.situacao = p_status)
      AND (v_filtra_periodo = 0 OR r.compras_periodo > 0 OR r.pagamentos_periodo > 0)
    ORDER BY r.saldo_devedor DESC, r.nome;
END$$

-- SP 2 (operação transacional):
-- Registra uma compra no fiado só se o cliente existir, o valor for positivo
-- e o novo saldo não ultrapassar o limite de crédito. Usa transação e trava a
-- linha do cliente (FOR UPDATE) para duas compras simultâneas não estourarem o limite.
CREATE PROCEDURE sp_registrar_compra(
    IN  p_cliente_id INT,
    IN  p_descricao  VARCHAR(200),
    IN  p_valor      DECIMAL(10,2),
    OUT p_ok         TINYINT,
    OUT p_msg        VARCHAR(255)
)
proc: BEGIN
    DECLARE v_limite DECIMAL(10,2) DEFAULT NULL;
    DECLARE v_saldo  DECIMAL(10,2) DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_ok  = 0;
        SET p_msg = 'Erro interno ao registrar a compra. Nada foi gravado.';
    END;

    SET p_ok  = 0;
    SET p_msg = '';

    IF p_valor IS NULL OR p_valor <= 0 THEN
        SET p_msg = 'O valor da compra deve ser maior que zero.';
        LEAVE proc;
    END IF;

    START TRANSACTION;

    SELECT limite_credito INTO v_limite
    FROM clientes WHERE id = p_cliente_id FOR UPDATE;

    IF v_limite IS NULL THEN
        ROLLBACK;
        SET p_msg = 'Cliente não encontrado.';
        LEAVE proc;
    END IF;

    SELECT COALESCE((SELECT SUM(valor) FROM compras    WHERE cliente_id = p_cliente_id), 0)
         - COALESCE((SELECT SUM(valor) FROM pagamentos WHERE cliente_id = p_cliente_id), 0)
    INTO v_saldo;

    IF v_saldo + p_valor > v_limite THEN
        ROLLBACK;
        SET p_msg = CONCAT('Limite de crédito excedido. Limite: R$ ', FORMAT(v_limite, 2),
                           ' | Dívida atual: R$ ', FORMAT(v_saldo, 2));
        LEAVE proc;
    END IF;

    INSERT INTO compras (cliente_id, descricao, valor)
    VALUES (p_cliente_id, p_descricao, p_valor);

    COMMIT;
    SET p_ok  = 1;
    SET p_msg = 'Compra registrada com sucesso!';
END$$

DELIMITER ;

-- ---------------------------------------------------------------------
-- DADOS DE EXEMPLO
-- ---------------------------------------------------------------------
-- Usuários (senhas criptografadas com password_hash do PHP)
--   admin   | senha: admin123 | nível: admin
--   usuario | senha: user123  | nível: comum
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
