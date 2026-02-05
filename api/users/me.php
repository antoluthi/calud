<?php
/**
 * Endpoint pour récupérer/supprimer les informations de l'utilisateur connecté
 * Nécessite une authentification
 */

require_once '../config.php';

// Vérifier que l'utilisateur est connecté
requireAuth();

$method = $_SERVER['REQUEST_METHOD'];

// DELETE - Supprimer son propre compte
if ($method === 'DELETE') {
    $user = getCurrentUser();

    // Un admin ne peut pas supprimer son propre compte
    if (isAdmin()) {
        sendJSON(['error' => 'Un administrateur ne peut pas supprimer son propre compte'], 403);
    }

    try {
        $db = getDB();

        // Dissocier les commandes (elles restent comme commandes guest)
        $stmt = $db->prepare("UPDATE commandes SET user_id = NULL WHERE user_id = ?");
        $stmt->execute([$user['id']]);

        // Supprimer le compte
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);

        // Détruire la session
        session_destroy();

        sendJSON(['success' => true]);
    } catch (PDOException $e) {
        error_log("Erreur suppression compte: " . $e->getMessage());
        sendJSON(['error' => 'Erreur lors de la suppression du compte'], 500);
    }
}

// GET - Récupérer l'utilisateur
$user = getCurrentUser();

sendJSON([
    'id' => $user['id'],
    'email' => $user['email'],
    'name' => $user['name'],
    'picture' => $user['picture']
]);
