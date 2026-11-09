-- Execute este arquivo no phpMyAdmin do banco da Peças Fáceis.
-- As tabelas são adicionais; não apagam as tabelas atuais.

ALTER TABLE whatsapp_config
    ADD COLUMN cloud_phone_number_id VARCHAR(100) NOT NULL DEFAULT '' AFTER numero,
    ADD COLUMN cloud_access_token TEXT NULL AFTER cloud_phone_number_id,
    ADD COLUMN cloud_api_version VARCHAR(20) NOT NULL DEFAULT 'v23.0' AFTER cloud_access_token,
    ADD COLUMN cloud_template_name VARCHAR(100) NOT NULL DEFAULT '' AFTER cloud_api_version,
    ADD COLUMN cloud_template_language VARCHAR(20) NOT NULL DEFAULT 'pt_BR' AFTER cloud_template_name,
    ADD COLUMN cloud_ativo TINYINT(1) NOT NULL DEFAULT 0 AFTER cloud_template_language;

CREATE TABLE IF NOT EXISTS pedidos (
    id INT NOT NULL AUTO_INCREMENT,
    cliente_nome VARCHAR(150) NOT NULL,
    cliente_telefone VARCHAR(50) NOT NULL,
    cliente_email VARCHAR(150) NULL,
    forma_pagamento VARCHAR(60) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'novo',
    whatsapp_status ENUM('pendente','enviado','falhou') NOT NULL DEFAULT 'pendente',
    whatsapp_erro TEXT NULL,
    whatsapp_message_id VARCHAR(255) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pedidos_criado_em (criado_em),
    KEY idx_pedidos_whatsapp_status (whatsapp_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedido_itens (
    id INT NOT NULL AUTO_INCREMENT,
    pedido_id INT NOT NULL,
    carro_id INT NULL,
    produto_nome VARCHAR(180) NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_pedido_itens_pedido (pedido_id),
    CONSTRAINT fk_pedido_itens_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    CONSTRAINT fk_pedido_itens_carro FOREIGN KEY (carro_id) REFERENCES carros(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
