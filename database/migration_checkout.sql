-- Migration: Mise à jour de la table commandes pour le checkout
-- À exécuter si la structure actuelle ne correspond pas

-- Vérifier/modifier la structure de la table commandes
ALTER TABLE commandes
    ADD COLUMN IF NOT EXISTS order_id VARCHAR(50) UNIQUE,
    ADD COLUMN IF NOT EXISTS email VARCHAR(255),
    ADD COLUMN IF NOT EXISTS phone VARCHAR(50),
    ADD COLUMN IF NOT EXISTS first_name VARCHAR(100),
    ADD COLUMN IF NOT EXISTS last_name VARCHAR(100),
    ADD COLUMN IF NOT EXISTS address TEXT,
    ADD COLUMN IF NOT EXISTS address2 VARCHAR(255),
    ADD COLUMN IF NOT EXISTS postal_code VARCHAR(20),
    ADD COLUMN IF NOT EXISTS city VARCHAR(100),
    ADD COLUMN IF NOT EXISTS country VARCHAR(10) DEFAULT 'FR',
    ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50),
    ADD COLUMN IF NOT EXISTS subtotal DECIMAL(10,2),
    ADD COLUMN IF NOT EXISTS shipping DECIMAL(10,2),
    MODIFY COLUMN status ENUM('pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending';

-- Index pour recherche par order_id
CREATE INDEX IF NOT EXISTS idx_order_id ON commandes(order_id);
CREATE INDEX IF NOT EXISTS idx_email ON commandes(email);
CREATE INDEX IF NOT EXISTS idx_status ON commandes(status);
