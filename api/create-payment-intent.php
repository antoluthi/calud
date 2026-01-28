<?php
/**
 * Créer un PaymentIntent Stripe
 */

require_once 'config.php';
require_once 'stripe-config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Vérifier la configuration Stripe
if (!checkStripeConfig()) {
    http_response_code(500);
    echo json_encode(['error' => 'Stripe non configuré']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['amount'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Montant requis']);
    exit;
}

$amount = intval($data['amount']); // Amount in cents

if ($amount < 50) { // Minimum 0.50€
    http_response_code(400);
    echo json_encode(['error' => 'Montant minimum: 0.50€']);
    exit;
}

try {
    // Utiliser cURL pour appeler l'API Stripe (sans SDK)
    $ch = curl_init('https://api.stripe.com/v1/payment_intents');

    $postData = http_build_query([
        'amount' => $amount,
        'currency' => 'eur',
        'automatic_payment_methods[enabled]' => 'true',
        'metadata[order_id]' => $data['orderId'] ?? '',
        'metadata[email]' => $data['email'] ?? '',
    ]);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_USERPWD => STRIPE_SECRET_KEY . ':',
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($httpCode !== 200) {
        throw new Exception($result['error']['message'] ?? 'Erreur Stripe');
    }

    echo json_encode([
        'clientSecret' => $result['client_secret'],
        'paymentIntentId' => $result['id']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
