<?php
/**
 * Configuration Stripe
 *
 * IMPORTANT: Ajoutez vos clés Stripe dans le fichier .env :
 * STRIPE_SECRET_KEY=sk_test_xxxxx (ou sk_live_xxxxx en production)
 * STRIPE_PUBLISHABLE_KEY=pk_test_xxxxx (ou pk_live_xxxxx en production)
 */

// Charger les variables d'environnement
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

define('STRIPE_SECRET_KEY', $_ENV['STRIPE_SECRET_KEY'] ?? '');
define('STRIPE_PUBLISHABLE_KEY', $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '');

// Vérifier que les clés sont configurées
function checkStripeConfig() {
    if (empty(STRIPE_SECRET_KEY) || empty(STRIPE_PUBLISHABLE_KEY)) {
        return false;
    }
    return true;
}
