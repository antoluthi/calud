<?php
/**
 * Validation d'un code promo
 * POST : { code, subtotal }
 * Retourne le detail de la reduction si le code est valide
 */

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['error' => 'Methode non autorisee'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$code = strtoupper(trim($input['code'] ?? ''));
$subtotal = floatval($input['subtotal'] ?? 0);

if (empty($code)) {
    sendJSON(['error' => 'Code promo requis'], 400);
}

$db = getDB();

$stmt = $db->prepare("SELECT * FROM promo_codes WHERE code = ? AND active = 1");
$stmt->execute([$code]);
$promo = $stmt->fetch();

if (!$promo) {
    sendJSON(['error' => 'Code promo invalide'], 404);
}

// Verifier les dates
if ($promo['starts_at'] && strtotime($promo['starts_at']) > time()) {
    sendJSON(['error' => 'Ce code promo n\'est pas encore actif'], 400);
}

if ($promo['expires_at'] && strtotime($promo['expires_at']) < time()) {
    sendJSON(['error' => 'Ce code promo a expire'], 400);
}

// Verifier le nombre d'utilisations
if ($promo['max_uses'] !== null && $promo['used_count'] >= $promo['max_uses']) {
    sendJSON(['error' => 'Ce code promo a atteint sa limite d\'utilisation'], 400);
}

// Verifier le montant minimum
if ($subtotal < $promo['min_order_amount']) {
    sendJSON(['error' => 'Montant minimum de commande : ' . number_format($promo['min_order_amount'], 2, ',', ' ') . ' EUR'], 400);
}

// Calculer la reduction
if ($promo['discount_type'] === 'percent') {
    $discount = round($subtotal * ($promo['discount_value'] / 100), 2);
    $label = '-' . intval($promo['discount_value']) . '%';
} else {
    $discount = min($promo['discount_value'], $subtotal);
    $label = '-' . number_format($promo['discount_value'], 2, ',', ' ') . ' EUR';
}

sendJSON([
    'valid' => true,
    'code' => $promo['code'],
    'discount_type' => $promo['discount_type'],
    'discount_value' => floatval($promo['discount_value']),
    'discount' => $discount,
    'label' => $label,
    'description' => $promo['description']
]);
