<?php
/**
 * API Commandes - Récupère les commandes de l'utilisateur connecté
 */

require_once 'config.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    sendJSON(['success' => false, 'error' => 'Method not allowed'], 405);
}

$user = getCurrentUser();
$pdo = getDB();

// Récupérer les commandes par user_id ou par email (fallback pour anciennes commandes)
$stmt = $pdo->prepare("
    SELECT id, order_id, email, first_name, last_name, address, address2,
           postal_code, city, country, subtotal, shipping, total, status, created_at
    FROM commandes
    WHERE user_id = ? OR email = ?
    ORDER BY created_at DESC
");
$stmt->execute([$user['id'], $user['email']]);
$commandes = $stmt->fetchAll();

// Pour chaque commande, récupérer les items
$stmtItems = $pdo->prepare("
    SELECT product_name, product_size, quantity, price
    FROM commande_items
    WHERE commande_id = ?
");

foreach ($commandes as &$commande) {
    $stmtItems->execute([$commande['id']]);
    $commande['items'] = $stmtItems->fetchAll();
}
unset($commande);

sendJSON(['success' => true, 'commandes' => $commandes]);
