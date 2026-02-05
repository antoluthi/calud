<?php
/**
 * Endpoint de statut d'authentification
 * Retourne les informations de l'utilisateur connecté ou null
 */

require_once '../config.php';

// Récupérer l'utilisateur connecté
$user = getCurrentUser();

if ($user) {
    // Recuperer les champs supplementaires pour l'auth method
    $db = getDB();
    $stmt = $db->prepare("SELECT auth_method, password_hash, google_id FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $authInfo = $stmt->fetch();

    sendJSON([
        'authenticated' => true,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'picture' => $user['picture'],
            'is_admin' => (bool)($user['is_admin'] ?? false),
            'auth_method' => $authInfo['auth_method'] ?? 'google',
            'has_password' => !empty($authInfo['password_hash']),
            'has_google' => !empty($authInfo['google_id'])
        ]
    ]);
} else {
    sendJSON([
        'authenticated' => false,
        'user' => null
    ]);
}
