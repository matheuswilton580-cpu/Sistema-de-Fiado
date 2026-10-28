USE sistema_fiado;
ALTER TABLE usuarios MODIFY status ENUM('pendente','aprovado','recusado','bloqueado') NOT NULL DEFAULT 'aprovado';
