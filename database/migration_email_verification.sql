-- Migration: Verification email pour les comptes email/password
-- A executer apres migration_email_auth.sql

ALTER TABLE users
    ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER auth_method,
    ADD COLUMN verification_token VARCHAR(64) DEFAULT NULL AFTER email_verified,
    ADD COLUMN verification_token_expires DATETIME DEFAULT NULL AFTER verification_token;

-- Les comptes Google sont automatiquement verifies
UPDATE users SET email_verified = 1 WHERE google_id IS NOT NULL;

-- Les comptes email existants sont aussi marques comme verifies (ils existaient avant cette feature)
UPDATE users SET email_verified = 1 WHERE password_hash IS NOT NULL AND email_verified = 0;

-- Index pour la recherche par token
ALTER TABLE users ADD INDEX idx_verification_token (verification_token);
