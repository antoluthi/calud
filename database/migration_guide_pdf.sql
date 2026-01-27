-- Migration: Ajouter le champ guide d'utilisation PDF aux produits
-- Exécuter sur gates.luthi.eu

ALTER TABLE produits
ADD COLUMN guide_pdf VARCHAR(500) NULL AFTER video_url;

-- Vérifier que la colonne a été ajoutée
DESCRIBE produits;
