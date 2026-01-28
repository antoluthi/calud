<?php
/**
 * API Checkout - Enregistre les commandes
 */

// Capturer tout output
ob_start();

// Désactiver l'affichage des erreurs PHP
ini_set('display_errors', 0);
ini_set('html_errors', 0);
error_reporting(0);

header('Content-Type: application/json');

// Fonction pour retourner une erreur JSON proprement
function returnError($message) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

// Fonction pour retourner un succès
function returnSuccess($data) {
    ob_end_clean();
    echo json_encode($data);
    exit;
}

try {
    require_once 'config.php';
    // Re-désactiver les erreurs (config.php les réactive)
    ini_set('display_errors', 0);
    ini_set('html_errors', 0);
    error_reporting(0);
} catch (Throwable $e) {
    returnError('Config error: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    returnError('Method not allowed');
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    returnError('Invalid data');
}

// Validate required fields
$required = ['email', 'firstName', 'lastName', 'address', 'postalCode', 'city', 'country', 'items', 'total'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        returnError("Missing field: $field");
    }
}

try {
    $pdo = getDB();

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

    returnSuccess([
        'success' => true,
        'orderId' => $orderId,
        'message' => 'Commande enregistrée avec succès'
    ]);

} catch (PDOException $e) {
    error_log("Checkout error: " . $e->getMessage());
    http_response_code(500);
    returnError('Erreur base de données: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("Checkout error: " . $e->getMessage());
    http_response_code(500);
    returnError('Erreur: ' . $e->getMessage());
}
