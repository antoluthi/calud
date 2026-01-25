<?php
/**
 * API Admin - Gestion des Commandes
 * Récupération et mise à jour des commandes
 */

require_once '../config.php';

// Vérifier que l'utilisateur est admin
requireAdmin();

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// GET - Récupérer les commandes
if ($method === 'GET') {
    if (isset($_GET['id'])) {
        // Récupérer une commande spécifique avec ses items
        $commandeId = $_GET['id'];

        // Récupérer la commande
        $stmt = $db->prepare("
            SELECT c.*, u.name as client_name, u.email as client_email, u.picture as client_picture
            FROM commandes c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$commandeId]);
        $commande = $stmt->fetch();

        if (!$commande) {
            sendJSON(['error' => 'Commande non trouvée'], 404);
        }

        // Récupérer les items de la commande
        $stmt = $db->prepare("
            SELECT ci.*, p.nom as produit_nom
            FROM commande_items ci
            LEFT JOIN produits p ON ci.produit_id = p.id
            WHERE ci.commande_id = ?
        ");
        $stmt->execute([$commandeId]);
        $items = $stmt->fetchAll();

        sendJSON([
            'commande' => $commande,
            'client' => [
                'name' => $commande['client_name'],
                'email' => $commande['client_email'],
                'picture' => $commande['client_picture']
            ],
            'items' => $items
        ]);
    } else {
        // Récupérer toutes les commandes
        $stmt = $db->query("
            SELECT c.*, u.name as client_name, u.email as client_email
            FROM commandes c
            LEFT JOIN users u ON c.user_id = u.id
            ORDER BY c.created_at DESC
        ");
        $commandes = $stmt->fetchAll();

        sendJSON($commandes);
    }
}

// PUT - Mettre à jour une commande (statut)
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validation
    if (empty($data['id'])) {
        sendJSON(['error' => 'ID de la commande requis'], 400);
    }

    if (empty($data['status'])) {
        sendJSON(['error' => 'Statut requis'], 400);
    }

    // Vérifier que le statut est valide
    $statusValides = ['en_attente', 'confirmee', 'expediee', 'livree', 'annulee'];
    if (!in_array($data['status'], $statusValides)) {
        sendJSON(['error' => 'Statut invalide'], 400);
    }

    // Vérifier que la commande existe
    $stmt = $db->prepare("SELECT id FROM commandes WHERE id = ?");
    $stmt->execute([$data['id']]);
    if (!$stmt->fetch()) {
        sendJSON(['error' => 'Commande non trouvée'], 404);
    }

    try {
        $stmt = $db->prepare("UPDATE commandes SET status = ? WHERE id = ?");
        $stmt->execute([$data['status'], $data['id']]);

        sendJSON([
            'success' => true,
            'message' => 'Statut de la commande mis à jour avec succès'
        ]);
    } catch (PDOException $e) {
        error_log("Erreur mise à jour commande: " . $e->getMessage());
        sendJSON(['error' => 'Erreur lors de la mise à jour de la commande'], 500);
    }
}

// POST - Créer une nouvelle commande (pour usage futur)
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validation
    if (empty($data['user_id']) || empty($data['items']) || !is_array($data['items'])) {
        sendJSON(['error' => 'user_id et items sont requis'], 400);
    }

    try {
        $db->beginTransaction();

        // Calculer le total
        $total = 0;
        foreach ($data['items'] as $item) {
            $total += $item['prix_unitaire'] * $item['quantite'];
        }

        // Créer la commande
        $stmt = $db->prepare("
            INSERT INTO commandes (user_id, total, status)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $data['user_id'],
            $total,
            $data['status'] ?? 'en_attente'
        ]);

        $commandeId = $db->lastInsertId();

        // Ajouter les items
        $stmt = $db->prepare("
            INSERT INTO commande_items (commande_id, produit_id, quantite, prix_unitaire)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($data['items'] as $item) {
            $stmt->execute([
                $commandeId,
                $item['produit_id'],
                $item['quantite'],
                $item['prix_unitaire']
            ]);
        }

        $db->commit();

        sendJSON([
            'success' => true,
            'message' => 'Commande créée avec succès',
            'id' => $commandeId
        ], 201);
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Erreur création commande: " . $e->getMessage());
        sendJSON(['error' => 'Erreur lors de la création de la commande'], 500);
    }
}

// DELETE - Supprimer une commande
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;

    if (!$id) {
        sendJSON(['error' => 'ID de la commande requis'], 400);
    }

    try {
        // Les items seront supprimés automatiquement (CASCADE)
        $stmt = $db->prepare("DELETE FROM commandes WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            sendJSON([
                'success' => true,
                'message' => 'Commande supprimée avec succès'
            ]);
        } else {
            sendJSON(['error' => 'Commande non trouvée'], 404);
        }
    } catch (PDOException $e) {
        error_log("Erreur suppression commande: " . $e->getMessage());
        sendJSON(['error' => 'Erreur lors de la suppression de la commande'], 500);
    }
}
