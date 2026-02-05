-- Migration: Ajouter user_id à la table visites pour tracker les utilisateurs connectés
-- Permet d'identifier les comptes connectés dans le traffic et filtrer le traffic admin

USE site_escalade;

ALTER TABLE visites
ADD COLUMN user_id INT NULL DEFAULT NULL AFTER session_id,
ADD INDEX idx_user_id (user_id),
ADD CONSTRAINT fk_visites_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
