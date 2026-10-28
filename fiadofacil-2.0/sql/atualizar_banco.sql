USE sistema_fiado;
ALTER TABLE usuarios MODIFY nivel ENUM('admin','comum','cliente') NOT NULL DEFAULT 'comum';
ALTER TABLE usuarios ADD COLUMN status ENUM('pendente','aprovado','recusado') NOT NULL DEFAULT 'aprovado';
ALTER TABLE usuarios ADD COLUMN cliente_id INT NULL;
ALTER TABLE usuarios ADD COLUMN telefone_solicitado VARCHAR(20) NULL;
ALTER TABLE usuarios ADD FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE;
