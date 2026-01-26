-- Migration: Ajout des champs pour la modal produit avec galerie d'images
-- Date: 2026-01-26

-- Ajouter les nouvelles colonnes à la table produits
ALTER TABLE produits
ADD COLUMN images JSON DEFAULT NULL COMMENT 'Array JSON d\'URLs d\'images ["url1.jpg", "url2.jpg", ...]',
ADD COLUMN dimensions VARCHAR(100) DEFAULT NULL COMMENT 'Dimensions du produit (ex: "20 x 15 x 3 cm")',
ADD COLUMN poids VARCHAR(50) DEFAULT NULL COMMENT 'Poids du produit (ex: "850g")',
ADD COLUMN materiaux TEXT DEFAULT NULL COMMENT 'Description des matériaux utilisés',
ADD COLUMN guide_tailles TEXT DEFAULT NULL COMMENT 'Guide détaillé des tailles',
ADD COLUMN video_url VARCHAR(500) DEFAULT NULL COMMENT 'URL vidéo YouTube (optionnel)';

-- Index pour améliorer les performances (optionnel)
-- CREATE INDEX idx_produits_actif ON produits(actif);
