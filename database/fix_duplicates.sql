-- Script pour diagnostiquer et corriger les doublons de produits
-- Exécuter sur gates.luthi.eu via SSH

-- 1. DIAGNOSTIC: Voir les doublons (produits avec le même nom)
SELECT nom, COUNT(*) as count, GROUP_CONCAT(id) as ids
FROM produits
GROUP BY nom
HAVING COUNT(*) > 1;

-- 2. DIAGNOSTIC: Voir tous les produits pour vérifier
SELECT id, nom, prix, actif, created_at
FROM produits
ORDER BY nom, created_at DESC;

-- 3. NETTOYAGE: Supprimer les doublons en gardant le plus récent
-- (décommenter pour exécuter - ATTENTION: action irréversible)
-- DELETE p1 FROM produits p1
-- INNER JOIN produits p2
-- WHERE p1.id < p2.id
-- AND p1.nom = p2.nom;

-- 4. ALTERNATIVE: Supprimer les doublons en gardant le plus ancien
-- (décommenter pour exécuter - ATTENTION: action irréversible)
-- DELETE p1 FROM produits p1
-- INNER JOIN produits p2
-- WHERE p1.id > p2.id
-- AND p1.nom = p2.nom;
