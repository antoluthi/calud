<?php
/**
 * API Admin - Gestion de la Newsletter
 * Récupération des abonnés et envoi d'emails en masse
 */

require_once '../config.php';

// Vérifier que l'utilisateur est admin
requireAdmin();

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// GET - Récupérer les abonnés
if ($method === 'GET') {
    try {
        $stmt = $db->query("
            SELECT id, email, actif, created_at
            FROM newsletter
            ORDER BY created_at DESC
        ");
        $abonnes = $stmt->fetchAll();

        sendJSON([
            'success' => true,
            'abonnes' => $abonnes,
            'total' => count($abonnes),
            'actifs' => count(array_filter($abonnes, fn($a) => $a['actif'] == 1))
        ]);
    } catch (PDOException $e) {
        error_log("Erreur récupération abonnés: " . $e->getMessage());
        sendJSON(['error' => 'Erreur lors de la récupération des abonnés'], 500);
    }
}

// POST - Envoyer un email à tous les abonnés
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validation
    if (empty($data['subject']) || empty($data['message'])) {
        sendJSON(['error' => 'Sujet et message requis'], 400);
    }

    try {
        // Récupérer tous les abonnés actifs
        $stmt = $db->query("SELECT email FROM newsletter WHERE actif = 1");
        $abonnes = $stmt->fetchAll();

        if (empty($abonnes)) {
            sendJSON([
                'success' => false,
                'message' => 'Aucun abonné actif'
            ]);
        }

        $emails = array_column($abonnes, 'email');
        $subject = $data['subject'];
        $message = $data['message'];

        // En-têtes pour l'email HTML
        $headers = [
            'MIME-Version' => '1.0',
            'Content-type' => 'text/html; charset=utf-8',
            'From' => 'CRIMP. <noreply@' . $_SERVER['HTTP_HOST'] . '>',
            'Reply-To' => 'contact@' . $_SERVER['HTTP_HOST'],
            'X-Mailer' => 'PHP/' . phpversion()
        ];

        $headersString = '';
        foreach ($headers as $key => $value) {
            $headersString .= "$key: $value\r\n";
        }

        // Template HTML pour l'email
        $htmlMessage = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #e75480; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; background: #f9f9f9; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>CRIMP.</h1>
                </div>
                <div class='content'>
                    " . nl2br(htmlspecialchars($message)) . "
                </div>
                <div class='footer'>
                    <p>Vous recevez cet email car vous êtes abonné à notre newsletter.</p>
                    <p><a href='https://" . $_SERVER['HTTP_HOST'] . "'>Visiter notre site</a></p>
                </div>
            </div>
        </body>
        </html>
        ";

        $sent = 0;
        $failed = 0;

        // Envoyer l'email à chaque abonné
        foreach ($emails as $email) {
            if (mail($email, $subject, $htmlMessage, $headersString)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        sendJSON([
            'success' => true,
            'message' => "Email envoyé à $sent abonné(s)" . ($failed > 0 ? " ($failed échec(s))" : ''),
            'sent' => $sent,
            'failed' => $failed
        ]);

    } catch (PDOException $e) {
        error_log("Erreur envoi newsletter: " . $e->getMessage());
        sendJSON(['error' => 'Erreur lors de l\'envoi de la newsletter'], 500);
    }
}

// DELETE - Désabonner un email
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;

    if (!$id) {
        sendJSON(['error' => 'ID requis'], 400);
    }

    try {
        // Désactiver l'abonnement au lieu de supprimer
        $stmt = $db->prepare("UPDATE newsletter SET actif = 0 WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            sendJSON([
                'success' => true,
                'message' => 'Abonnement désactivé'
            ]);
        } else {
            sendJSON(['error' => 'Abonné non trouvé'], 404);
        }
    } catch (PDOException $e) {
        error_log("Erreur désabonnement: " . $e->getMessage());
        sendJSON(['error' => 'Erreur lors du désabonnement'], 500);
    }
}
