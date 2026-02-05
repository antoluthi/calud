-- Migration: Email + Password Authentication
-- Ajoute le support de l'authentification par email/mot de passe en parallele de Google OAuth

-- Rendre google_id nullable (les users email n'en ont pas)
ALTER TABLE users MODIFY COLUMN google_id VARCHAR(255) NULL;

-- Supprimer et recreer l'index UNIQUE sur google_id (MySQL autorise multiple NULL dans UNIQUE)
ALTER TABLE users DROP INDEX google_id;
ALTER TABLE users ADD UNIQUE INDEX unique_google_id (google_id);

-- Ajouter la colonne password_hash pour stocker le hash bcrypt
ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NULL AFTER picture;

-- Ajouter la colonne auth_method pour distinguer le type de compte
ALTER TABLE users ADD COLUMN auth_method ENUM('google', 'email') NOT NULL DEFAULT 'google' AFTER password_hash;
