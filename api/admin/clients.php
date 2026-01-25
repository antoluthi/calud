<?php
/**
 * API Admin - Gestion des Clients
 * Récupération et gestion des utilisateurs
 */

require_once '../config.php';

// Vérifier que l'utilisateur est admin
requireAdmin();

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// GET - Récupérer les clients
if ($method === 'GET') {
    if (isset($_GET['id'])) {
        // Récupérer un client spécifique avec ses commandes
        $clientId = $_GET['id'];

        // Récupérer le client
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$clientId]);
        $client = $stmt->fetch();

        if (!$client) {
            sendJSON(['error' => 'Client non trouvé'], 404);
        }

        // Récupérer ses commandes
        $stmt = $db->prepare("
            SELECT id, total, status, created_at
            FROM commandes
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$clientId]);
        $commandes = $stmt->fetchAll();

        sendJSON([
            'client' => $client,
            'commandes' => $commandes
        ]);
    } else {
        // Récupérer tous les clients avec stats
        $stmt = $db->query("
            SELECT
                u.*,
                COUNT(DISTINCT c.id) as nb_commandes,
                COALESCE(SUM(c.total), 0) as total_depense
            FROM users u
            LEFT JOIN commandes c ON u.id = c.user_id
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ");
        $clients = $stmt->fetchAll();

        sendJSON($clients);
    }
}

// PUT - Mettre à jour un client (droits admin)
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validation
    if (empty($data['id'])) {
        sendJSON(['error' => 'ID du client requis'], 400);
    }

    // Vérifier que le client existe
    $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$data['id']]);
    if (!$stmt->fetch()) {
        sendJSON(['error' => 'Client non trouvé'], 404);
    }

    // Empêcher de se retirer les droits admin soi-même
    $currentUser = getCurrentUser();
    if ($data['id'] == $currentUser['id'] && isset($data['is_admin']) && !$data['is_admin']) {
        sendJSON(['error' => 'Vous ne pouvez pas vous retirer vos propres droits admin'], 403);
    }

    try {
        // Construire la requête dynamiquement
        $updates = [];
        $params = [];

        if (isset($data['is_admin'])) {
            $updates[] = "is_admin = ?";
            $params[] = $data['is_admin'] ? 1 : 0;
        }

        if (empty($updates)) {
            sendJSON(['error' => 'Aucune donnée à mettre à jour'], 400);
        }

        $params[] = $data['id'];
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        sendJSON([
            'success' => true,
            'message' => 'Client mis à jour avec succès'
        ]);
    } catch (PDOException $e) {
        error_log("Erreur mise à jour client: " . $e->getMessage());
        sendJSON(['error' => 'Erreur lors de la mise à jour du client'], 500);
    }
}

// DELETE - Supprimer un client (dangereux, à utiliser avec précaution)
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;

    if (!$id) {
        sendJSON(['error' => 'ID du client requis'], 400);
    }

    // Empêcher de se supprimer soi-même
    $currentUser = getCurrentUser();
    if ($id == $currentUser['id']) {
        sendJSON(['error' => 'Vous ne pouvez pas supprimer votre propre compte'], 403);
    }

    try {
        // Vérifier s'il y a des commandes
        $stmt = $db->prepare("SELECT COUNT(*) as nb FROM commandes WHERE user_id = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetch();

        if ($count['nb'] > 0) {
            sendJSON(['error' => 'Impossible de supprimer un client avec des commandes'], 400);
        }

        // Supprimer le client
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            sendJSON([
                'success' => true,
                'message' => 'Client supprimé avec succès'
            ]);
        } else {
            sendJSON(['error' => 'Client non trouvé'], 404);
        }
    } catch (PDOException $e) {
        error_log("Erreur suppression client: " . $e->getMessage());
        sendJSON(['error' => 'Erreur lors de la suppression du client'], 500);
    }
}
