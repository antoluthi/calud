-- Migration: Table site_settings pour mode maintenance et protection par mot de passe
-- A executer apres les migrations precedentes

CREATE TABLE IF NOT EXISTS `site_settings` (
    `setting_key` VARCHAR(50) PRIMARY KEY,
    `setting_value` TEXT NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Valeurs par defaut
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
    ('maintenance_enabled', '0'),
    ('maintenance_ips', '[]'),
    ('password_enabled', '0'),
    ('password_hash', '')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
