-- Migration: Ajout du champ model_3d pour le viewer 3D dans la galerie produit
ALTER TABLE produits ADD COLUMN model_3d VARCHAR(500) NULL AFTER guide_pdf;
