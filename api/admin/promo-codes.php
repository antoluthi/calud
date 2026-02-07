<?php
/**
 * API Admin - Gestion des codes promo
 * GET    : liste tous les codes promo
 * POST   : creer un code promo
 * PUT    : modifier un code promo (id en param)
 * DELETE : supprimer un code promo (id en param)
 */

require_once '../config.php';
requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

// ===== GET : Liste des codes promo =====
if ($method === 'GET') {
    $stmt = $db->query("SELECT * FROM promo_codes ORDER BY created_at DESC");
    sendJSON($stmt->fetchAll());
}

// ===== POST : Creer un code promo =====
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $code = strtoupper(trim($input['code'] ?? ''));
    $description = trim($input['description'] ?? '');
    $discountType = $input['discount_type'] ?? 'percent';
    $discountValue = floatval($input['discount_value'] ?? 0);
    $minOrderAmount = floatval($input['min_order_amount'] ?? 0);
    $maxUses = isset($input['max_uses']) && $input['max_uses'] !== '' ? intval($input['max_uses']) : null;
    $startsAt = !empty($input['starts_at']) ? $input['starts_at'] : null;
    $expiresAt = !empty($input['expires_at']) ? $input['expires_at'] : null;

    if (empty($code)) {
        sendJSON(['error' => 'Le code est requis'], 400);
    }

    if (!in_array($discountType, ['percent', 'fixed'])) {
        sendJSON(['error' => 'Type de reduction invalide'], 400);
    }

    if ($discountValue <= 0) {
        sendJSON(['error' => 'La valeur de reduction doit etre positive'], 400);
    }

    if ($discountType === 'percent' && $discountValue > 100) {
        sendJSON(['error' => 'Le pourcentage ne peut pas depasser 100'], 400);
    }

    // Verifier unicite
    $stmt = $db->prepare("SELECT id FROM promo_codes WHERE code = ?");
    $stmt->execute([$code]);
    if ($stmt->fetch()) {
        sendJSON(['error' => 'Ce code existe deja'], 409);
    }

    $stmt = $db->prepare("
        INSERT INTO promo_codes (code, description, discount_type, discount_value, min_order_amount, max_uses, starts_at, expires_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$code, $description, $discountType, $discountValue, $minOrderAmount, $maxUses, $startsAt, $expiresAt]);

    sendJSON(['success' => true, 'id' => (int)$db->lastInsertId()]);
}

// ===== PUT : Modifier un code promo =====
if ($method === 'PUT') {
    $id = $_GET['id'] ?? null;
    if (!$id) sendJSON(['error' => 'ID requis'], 400);

    $input = json_decode(file_get_contents('php://input'), true);

    $fields = [];
    $params = [];

    if (isset($input['code'])) {
        $fields[] = 'code = ?';
        $params[] = strtoupper(trim($input['code']));
    }
    if (isset($input['description'])) {
        $fields[] = 'description = ?';
        $params[] = trim($input['description']);
    }
    if (isset($input['discount_type'])) {
        $fields[] = 'discount_type = ?';
        $params[] = $input['discount_type'];
    }
    if (isset($input['discount_value'])) {
        $fields[] = 'discount_value = ?';
        $params[] = floatval($input['discount_value']);
    }
    if (isset($input['min_order_amount'])) {
        $fields[] = 'min_order_amount = ?';
        $params[] = floatval($input['min_order_amount']);
    }
    if (array_key_exists('max_uses', $input)) {
        $fields[] = 'max_uses = ?';
        $params[] = $input['max_uses'] !== null && $input['max_uses'] !== '' ? intval($input['max_uses']) : null;
    }
    if (array_key_exists('starts_at', $input)) {
        $fields[] = 'starts_at = ?';
        $params[] = !empty($input['starts_at']) ? $input['starts_at'] : null;
    }
    if (array_key_exists('expires_at', $input)) {
        $fields[] = 'expires_at = ?';
        $params[] = !empty($input['expires_at']) ? $input['expires_at'] : null;
    }
    if (isset($input['active'])) {
        $fields[] = 'active = ?';
        $params[] = $input['active'] ? 1 : 0;
    }

    if (empty($fields)) {
        sendJSON(['error' => 'Aucun champ a modifier'], 400);
    }

    $params[] = $id;
    $stmt = $db->prepare("UPDATE promo_codes SET " . implode(', ', $fields) . " WHERE id = ?");
    $stmt->execute($params);

    sendJSON(['success' => true]);
}

// ===== DELETE : Supprimer un code promo =====
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) sendJSON(['error' => 'ID requis'], 400);

    $stmt = $db->prepare("DELETE FROM promo_codes WHERE id = ?");
    $stmt->execute([$id]);

    sendJSON(['success' => true]);
}
