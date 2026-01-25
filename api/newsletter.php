<?php
/**
 * API Publique - Newsletter
 * Permet de s'abonner à la newsletter
 */

require_once 'config.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// POST - S'abonner à la newsletter
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validation
    if (empty($data['email'])) {
        sendJSON(['error' => 'Email requis'], 400);
    }

    // Validation de l'email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        sendJSON(['error' => 'Email invalide'], 400);
    }

    try {
        // Vérifier si l'email existe déjà
        $stmt = $db->prepare("SELECT id, actif FROM newsletter WHERE email = ?");
        $stmt->execute([$data['email']]);
        $existing = $stmt->fetch();

        if ($existing) {
            if ($existing['actif']) {
                sendJSON([
                    'success' => false,
                    'message' => 'Cet email est déjà inscrit à la newsletter'
                ], 200);
            } else {
                // Réactiver l'abonnement
                $stmt = $db->prepare("UPDATE newsletter SET actif = 1 WHERE id = ?");
                $stmt->execute([$existing['id']]);

                sendJSON([
                    'success' => true,
                    'message' => 'Votre abonnement a été réactivé !'
                ]);
            }
        } else {
            // Nouvel abonnement
            $stmt = $db->prepare("INSERT INTO newsletter (email) VALUES (?)");
            $stmt->execute([$data['email']]);

            sendJSON([
                'success' => true,
                'message' => 'Merci de votre inscription à la newsletter !'
            ], 201);
        }
    } catch (PDOException $e) {
        error_log("Erreur inscription newsletter: " . $e->getMessage());
        sendJSON(['error' => 'Erreur lors de l\'inscription'], 500);
    }
}

// Autres méthodes non autorisées
if ($method !== 'POST') {
    sendJSON(['error' => 'Méthode non autorisée'], 405);
}
