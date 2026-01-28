<?php
/**
 * Retourne la clé publique Stripe
 */

require_once 'stripe-config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (checkStripeConfig()) {
    echo json_encode([
        'publishableKey' => STRIPE_PUBLISHABLE_KEY
    ]);
} else {
    echo json_encode([
        'publishableKey' => null,
        'message' => 'Stripe non configuré'
    ]);
}
