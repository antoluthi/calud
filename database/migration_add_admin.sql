-- Migration: Ajouter la colonne is_admin à la table users
-- À exécuter sur une base de données existante

USE site_escalade;

-- Ajouter la colonne is_admin si elle n'existe pas
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin BOOLEAN DEFAULT FALSE;

-- Pour définir un utilisateur comme admin, utilisez:
-- UPDATE users SET is_admin = TRUE WHERE email = 'votre-email@example.com';
