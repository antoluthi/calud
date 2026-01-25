-- Migration pour ajouter les tailles aux produits
USE site_escalade;

-- Ajouter la colonne tailles (JSON) à la table produits
ALTER TABLE produits
ADD COLUMN tailles JSON DEFAULT NULL AFTER caracteristiques;

-- Mettre à jour les produits existants avec des tailles par défaut
UPDATE produits
SET tailles = JSON_ARRAY('S (15mm)', 'M (20mm)', 'L (25mm)', 'XL (30mm)')
WHERE tailles IS NULL;
