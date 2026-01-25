<?php
/**
 * Endpoint de déconnexion
 * Détruit la session et déconnecte l'utilisateur
 */

require_once '../config.php';

// Détruire toutes les variables de session
$_SESSION = [];

// Détruire la session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Retourner une réponse JSON
sendJSON(['success' => true, 'message' => 'Déconnexion réussie']);
