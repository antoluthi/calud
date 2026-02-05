-- Migration: Création de la table visites pour le tracking analytics
-- Exécuter ce script via phpMyAdmin ou en ligne de commande MySQL

USE site_escalade;

-- Table des visites
CREATE TABLE IF NOT EXISTS visites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page VARCHAR(500) NOT NULL,
    ip_hash VARCHAR(64) NOT NULL COMMENT 'Hash SHA256 de l\'IP pour anonymisation',
    user_agent VARCHAR(500),
    referer VARCHAR(500),
    device_type ENUM('desktop', 'mobile', 'tablet') DEFAULT 'desktop',
    browser VARCHAR(100),
    os VARCHAR(100),
    country VARCHAR(100),
    session_id VARCHAR(64) COMMENT 'Identifiant de session pour grouper les visites',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_page (page(100)),
    INDEX idx_device_type (device_type),
    INDEX idx_session_id (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des pages vues par session (pour tracking plus précis)
CREATE TABLE IF NOT EXISTS pages_vues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visite_id INT NOT NULL,
    page VARCHAR(500) NOT NULL,
    temps_passe INT DEFAULT 0 COMMENT 'Temps passé sur la page en secondes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (visite_id) REFERENCES visites(id) ON DELETE CASCADE,
    INDEX idx_visite_id (visite_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
