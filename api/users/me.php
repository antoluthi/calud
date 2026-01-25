<?php
/**
 * Endpoint pour récupérer les informations de l'utilisateur connecté
 * Nécessite une authentification
 */

require_once '../config.php';

// Vérifier que l'utilisateur est connecté
requireAuth();

// Récupérer l'utilisateur
$user = getCurrentUser();

sendJSON([
    'id' => $user['id'],
    'email' => $user['email'],
    'name' => $user['name'],
    'picture' => $user['picture']
]);
