<?php
/**
 * Endpoint de statut d'authentification
 * Retourne les informations de l'utilisateur connecté ou null
 */

require_once '../config.php';

// Récupérer l'utilisateur connecté
$user = getCurrentUser();

if ($user) {
    sendJSON([
        'authenticated' => true,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'picture' => $user['picture'],
            'is_admin' => (bool)($user['is_admin'] ?? false)
        ]
    ]);
} else {
    sendJSON([
        'authenticated' => false,
        'user' => null
    ]);
}
