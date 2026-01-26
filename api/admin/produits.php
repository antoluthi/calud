<?php
/**
 * API Admin - Gestion des Produits
 * CRUD complet pour les produits
 */

require_once '../config.php';

// Vérifier que l'utilisateur est admin
requireAdmin();

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// GET - Récupérer les produits
if ($method === 'GET') {
    if (isset($_GET['id'])) {
        // Récupérer un produit spécifique
        $stmt = $db->prepare("SELECT * FROM produits WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $produit = $stmt->fetch();

        if ($produit) {
            // Décoder le JSON des caractéristiques
            if ($produit['caracteristiques']) {
                $produit['caracteristiques'] = json_decode($produit['caracteristiques']);
            }
            if ($produit['tailles']) {
                $produit['tailles'] = json_decode($produit['tailles']);
            }
            // Décoder le JSON des images
            if (isset($produit['images']) && $produit['images']) {
                $produit['images'] = json_decode($produit['images']);
            }
            sendJSON($produit);
        } else {
            sendJSON(['error' => 'Produit non trouvé'], 404);
        }
    } else {
        // Récupérer tous les produits
        $stmt = $db->query("SELECT * FROM produits ORDER BY created_at DESC");
        $produits = $stmt->fetchAll();

        // Décoder le JSON pour chaque produit
        foreach ($produits as &$produit) {
            if ($produit['caracteristiques']) {
                $produit['caracteristiques'] = json_decode($produit['caracteristiques']);
            }
            if ($produit['tailles']) {
                $produit['tailles'] = json_decode($produit['tailles']);
            }
            // Décoder le JSON des images
            if (isset($produit['images']) && $produit['images']) {
                $produit['images'] = json_decode($produit['images']);
            }
        }

        sendJSON($produits);
    }
}

// POST - Créer un nouveau produit
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validation
    if (empty($data['nom']) || !isset($data['prix'])) {
        sendJSON(['error' => 'Le nom et le prix sont requis'], 400);
    }

    // Préparer les caractéristiques (convertir en JSON)
    $caracteristiques = isset($data['caracteristiques']) && is_array($data['caracteristiques'])
        ? json_encode($data['caracteristiques'], JSON_UNESCAPED_UNICODE)
        : '[]';

    // Préparer les tailles (convertir en JSON)
    $tailles = isset($data['tailles']) && is_array($data['tailles'])
        ? json_encode($data['tailles'], JSON_UNESCAPED_UNICODE)
        : json_encode(["S (15mm)", "M (20mm)", "L (25mm)", "XL (30mm)"], JSON_UNESCAPED_UNICODE);

    // Préparer les images (convertir en JSON)
    $images = isset($data['images']) && is_array($data['images'])
        ? json_encode($data['images'], JSON_UNESCAPED_UNICODE)
        : null;

    try {
        $stmt = $db->prepare("
            INSERT INTO produits (nom, prix, description, image, caracteristiques, tailles, actif, images, dimensions, poids, materiaux, guide_tailles, video_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['nom'],
            $data['prix'],
            $data['description'] ?? '',
            $data['image'] ?? '',
            $caracteristiques,
            $tailles,
            isset($data['actif']) ? ($data['actif'] ? 1 : 0) : 1,
            $images,
            $data['dimensions'] ?? null,
            $data['poids'] ?? null,
            $data['materiaux'] ?? null,
            $data['guide_tailles'] ?? null,
            $data['video_url'] ?? null
        ]);

        $id = $db->lastInsertId();

        sendJSON([
            'success' => true,
            'message' => 'Produit créé avec succès',
            'id' => $id
        ], 201);
    } catch (PDOException $e) {
        error_log("Erreur création produit: " . $e->getMessage());
        sendJSON(['error' => 'Erreur lors de la création du produit'], 500);
    }
}

// PUT - Mettre à jour un produit
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validation
    if (empty($data['id'])) {
        sendJSON(['error' => 'ID du produit requis'], 400);
    }

    // Vérifier que le produit existe
    $stmt = $db->prepare("SELECT id FROM produits WHERE id = ?");
    $stmt->execute([$data['id']]);
    if (!$stmt->fetch()) {
        sendJSON(['error' => 'Produit non trouvé'], 404);
    }

    try {
        // Construire la requête dynamiquement
        $updates = [];
        $params = [];

        if (isset($data['nom'])) {
            $updates[] = "nom = ?";
            $params[] = $data['nom'];
        }

        if (isset($data['prix'])) {
            $updates[] = "prix = ?";
            $params[] = $data['prix'];
        }

        if (isset($data['description'])) {
            $updates[] = "description = ?";
            $params[] = $data['description'];
        }

        if (isset($data['image'])) {
            $updates[] = "image = ?";
            $params[] = $data['image'];
        }

        if (isset($data['caracteristiques'])) {
            $updates[] = "caracteristiques = ?";
            $carac = is_array($data['caracteristiques'])
                ? json_encode($data['caracteristiques'], JSON_UNESCAPED_UNICODE)
                : $data['caracteristiques'];
            $params[] = $carac;
        }

        if (isset($data['tailles'])) {
            $updates[] = "tailles = ?";
            $tailles = is_array($data['tailles'])
                ? json_encode($data['tailles'], JSON_UNESCAPED_UNICODE)
                : $data['tailles'];
            $params[] = $tailles;
        }

        if (isset($data['actif'])) {
            $updates[] = "actif = ?";
            $params[] = $data['actif'] ? 1 : 0;
        }

        if (isset($data['images'])) {
            $updates[] = "images = ?";
            $imgs = is_array($data['images'])
                ? json_encode($data['images'], JSON_UNESCAPED_UNICODE)
                : $data['images'];
            $params[] = $imgs;
        }

        if (isset($data['dimensions'])) {
            $updates[] = "dimensions = ?";
            $params[] = $data['dimensions'];
        }

        if (isset($data['poids'])) {
            $updates[] = "poids = ?";
            $params[] = $data['poids'];
        }

        if (isset($data['materiaux'])) {
            $updates[] = "materiaux = ?";
            $params[] = $data['materiaux'];
        }

        if (isset($data['guide_tailles'])) {
            $updates[] = "guide_tailles = ?";
            $params[] = $data['guide_tailles'];
        }

        if (isset($data['video_url'])) {
            $updates[] = "video_url = ?";
            $params[] = $data['video_url'];
        }

        if (empty($updates)) {
            sendJSON(['error' => 'Aucune donnée à mettre à jour'], 400);
        }

        $params[] = $data['id'];
        $sql = "UPDATE produits SET " . implode(', ', $updates) . " WHERE id = ?";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        sendJSON([
            'success' => true,
            'message' => 'Produit mis à jour avec succès'
        ]);
    } catch (PDOException $e) {
        error_log("Erreur mise à jour produit: " . $e->getMessage());
        sendJSON(['error' => 'Erreur lors de la mise à jour du produit'], 500);
    }
}

// DELETE - Supprimer un produit
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;

    if (!$id) {
        sendJSON(['error' => 'ID du produit requis'], 400);
    }

    try {
        $stmt = $db->prepare("DELETE FROM produits WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            sendJSON([
                'success' => true,
                'message' => 'Produit supprimé avec succès'
            ]);
        } else {
            sendJSON(['error' => 'Produit non trouvé'], 404);
        }
    } catch (PDOException $e) {
        error_log("Erreur suppression produit: " . $e->getMessage());
        sendJSON(['error' => 'Erreur lors de la suppression du produit'], 500);
    }
}
