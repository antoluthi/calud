<?php
/**
 * API Publique - Messages de Contact
 * Permet aux utilisateurs d'envoyer des messages
 */

require_once 'config.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// POST - Créer un nouveau message
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validation
    if (empty($data['userName']) || empty($data['userEmail']) || empty($data['message'])) {
        sendJSON(['error' => 'Nom, email et message sont requis'], 400);
    }

    // Validation de l'email
    if (!filter_var($data['userEmail'], FILTER_VALIDATE_EMAIL)) {
        sendJSON(['error' => 'Email invalide'], 400);
    }

    // Vérifier si l'utilisateur est connecté
    $userId = null;
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO messages (user_id, user_name, user_email, message)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $data['userName'],
            $data['userEmail'],
            $data['message']
        ]);

        $id = $db->lastInsertId();

        sendJSON([
            'success' => true,
            'message' => 'Message envoyé avec succès',
            'id' => $id
        ], 201);
    } catch (PDOException $e) {
        error_log("Erreur création message: " . $e->getMessage());
        sendJSON(['error' => 'Erreur lors de l\'envoi du message'], 500);
    }
}

// Autres méthodes non autorisées
if ($method !== 'POST') {
    sendJSON(['error' => 'Méthode non autorisée'], 405);
}
