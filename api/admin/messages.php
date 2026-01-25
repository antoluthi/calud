<?php
/**
 * API Admin - Gestion des Messages
 * Récupération et gestion des messages de contact
 */

require_once '../config.php';

// Vérifier que l'utilisateur est admin
requireAdmin();

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// GET - Récupérer les messages
if ($method === 'GET') {
    if (isset($_GET['id'])) {
        // Récupérer un message spécifique
        $stmt = $db->prepare("SELECT * FROM messages WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $message = $stmt->fetch();

        if ($message) {
            sendJSON($message);
        } else {
            sendJSON(['error' => 'Message non trouvé'], 404);
        }
    } else {
        // Récupérer tous les messages
        $stmt = $db->query("
            SELECT * FROM messages
            ORDER BY created_at DESC
        ");
        $messages = $stmt->fetchAll();

        sendJSON([
            'success' => true,
            'messages' => $messages
        ]);
    }
}

// PUT - Marquer un message comme lu/non lu
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validation
    if (empty($data['id'])) {
        sendJSON(['error' => 'ID du message requis'], 400);
    }

    try {
        $updates = [];
        $params = [];

        if (isset($data['lu'])) {
            $updates[] = "lu = ?";
            $params[] = $data['lu'] ? 1 : 0;
        }

        if (empty($updates)) {
            sendJSON(['error' => 'Aucune donnée à mettre à jour'], 400);
        }

        $params[] = $data['id'];
        $sql = "UPDATE messages SET " . implode(', ', $updates) . " WHERE id = ?";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        sendJSON([
            'success' => true,
            'message' => 'Message mis à jour avec succès'
        ]);
    } catch (PDOException $e) {
        error_log("Erreur mise à jour message: " . $e->getMessage());
        sendJSON(['error' => 'Erreur lors de la mise à jour du message'], 500);
    }
}

// DELETE - Supprimer un message
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;

    if (!$id) {
        sendJSON(['error' => 'ID du message requis'], 400);
    }

    try {
        $stmt = $db->prepare("DELETE FROM messages WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            sendJSON([
                'success' => true,
                'message' => 'Message supprimé avec succès'
            ]);
        } else {
            sendJSON(['error' => 'Message non trouvé'], 404);
        }
    } catch (PDOException $e) {
        error_log("Erreur suppression message: " . $e->getMessage());
        sendJSON(['error' => 'Erreur lors de la suppression du message'], 500);
    }
}
