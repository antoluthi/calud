<?php
/**
 * API Publique - Liste des Produits
 * Récupère tous les produits actifs pour l'affichage public
 */

require_once 'config.php';

// Désactiver l'exigence d'authentification pour cette API publique
// (on ne vérifie pas requireAuth() car c'est une API publique)

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// GET - Récupérer les produits actifs
if ($method === 'GET') {
    try {
        // Récupérer seulement les produits actifs
        $stmt = $db->query("
            SELECT id, nom, prix, description, image, caracteristiques, tailles, actif
            FROM produits
            WHERE actif = 1
            ORDER BY created_at DESC
        ");
        $produits = $stmt->fetchAll();

        // Transformer les données pour le format attendu par le frontend
        $produitsFormates = [];
        foreach ($produits as $produit) {
            // Décoder le JSON des caractéristiques
            $caracteristiques = $produit['caracteristiques']
                ? json_decode($produit['caracteristiques'], true)
                : [];

            // Décoder le JSON des tailles
            $tailles = $produit['tailles']
                ? json_decode($produit['tailles'], true)
                : ["S (15mm)", "M (20mm)", "L (25mm)", "XL (30mm)"];

            $produitsFormates[] = [
                'id' => (int)$produit['id'],
                'name' => $produit['nom'],
                'price' => (float)$produit['prix'],
                'description' => $produit['description'],
                'image' => $produit['image'],
                'features' => $caracteristiques,
                'sizes' => $tailles
            ];
        }

        sendJSON([
            'success' => true,
            'produits' => $produitsFormates
        ]);

    } catch (PDOException $e) {
        error_log("Erreur récupération produits: " . $e->getMessage());
        sendJSON([
            'success' => false,
            'error' => 'Erreur lors de la récupération des produits',
            'produits' => []
        ], 500);
    }
}

// Autres méthodes non autorisées pour l'API publique
if ($method !== 'GET') {
    sendJSON(['error' => 'Méthode non autorisée'], 405);
}
