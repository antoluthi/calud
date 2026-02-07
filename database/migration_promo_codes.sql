-- Migration: Codes promo / bons de reduction
-- Systeme de codes promo avec pourcentage ou montant fixe, limites d'utilisation, dates de validite

CREATE TABLE IF NOT EXISTS promo_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    discount_type ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
    discount_value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0,
    max_uses INT DEFAULT NULL,
    used_count INT NOT NULL DEFAULT 0,
    starts_at DATETIME DEFAULT NULL,
    expires_at DATETIME DEFAULT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ajouter colonne promo_code et discount a la table commandes
ALTER TABLE commandes
    ADD COLUMN promo_code VARCHAR(50) DEFAULT NULL AFTER payment_method,
    ADD COLUMN discount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER promo_code;
