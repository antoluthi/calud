<?php
/**
 * Endpoint de login Google OAuth
 * Redirige l'utilisateur vers la page de connexion Google
 */

require_once '../config.php';

// Générer un état unique pour la sécurité CSRF
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

// Paramètres OAuth pour Google
$params = [
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'access_type' => 'online',
    'prompt' => 'select_account'
];

// URL d'autorisation Google
$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

// Rediriger vers Google
header('Location: ' . $authUrl);
exit;
