<?php
/**
 * API Checkout - Enregistre les commandes
 */

// Désactiver l'affichage des erreurs PHP (retourner JSON propre)
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

// Validate required fields
$required = ['email', 'firstName', 'lastName', 'address', 'postalCode', 'city', 'country', 'items', 'total'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Missing field: $field"]);
        exit;
    }
}

try {
    $pdo = getPDO();

    // Generate order ID
    $orderId = 'AL-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

    // Insert order
    $stmt = $pdo->prepare("
        INSERT INTO commandes (
            order_id, email, phone, first_name, last_name,
            address, address2, postal_code, city, country,
            payment_method, subtotal, shipping, total, status, created_at
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, 'pending', NOW()
        )
    ");

    $stmt->execute([
        $orderId,
        $data['email'],
        $data['phone'] ?? '',
        $data['firstName'],
        $data['lastName'],
        $data['address'],
        $data['address2'] ?? '',
        $data['postalCode'],
        $data['city'],
        $data['country'],
        $data['paymentMethod'],
        $data['subtotal'],
        $data['shipping'],
        $data['total']
    ]);

    $commandeId = $pdo->lastInsertId();

    // Insert order items
    $stmtItem = $pdo->prepare("
        INSERT INTO commande_items (commande_id, product_name, product_size, quantity, price)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($data['items'] as $item) {
        $stmtItem->execute([
            $commandeId,
            $item['name'],
            $item['size'],
            $item['quantity'],
            $item['price']
        ]);
    }

    // TODO: Send confirmation email

    echo json_encode([
        'success' => true,
        'orderId' => $orderId,
        'message' => 'Commande enregistrée avec succès'
    ]);

} catch (PDOException $e) {
    error_log("Checkout error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur base de données: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Checkout error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur: ' . $e->getMessage()]);
}
