<?php
/**
 * API Admin - Gestion maintenance et protection par mot de passe
 * GET : retourne les settings + IP du client
 * PUT : met a jour les settings
 */

require_once '../config.php';
requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$db = getDB();

if ($method === 'GET') {
    // Recuperer tous les settings
    $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    // IP du client
    $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    if (strpos($clientIp, ',') !== false) {
        $clientIp = trim(explode(',', $clientIp)[0]);
    }

    sendJSON([
        'maintenance_enabled' => ($settings['maintenance_enabled'] ?? '0') === '1',
        'maintenance_ips' => json_decode($settings['maintenance_ips'] ?? '[]', true) ?: [],
        'password_enabled' => ($settings['password_enabled'] ?? '0') === '1',
        'has_password' => !empty($settings['password_hash']),
        'client_ip' => $clientIp
    ]);

} elseif ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($input === null) {
        sendJSON(['error' => 'Donnees invalides'], 400);
    }

    // Mettre a jour chaque setting fourni
    $allowedKeys = ['maintenance_enabled', 'maintenance_ips', 'password_enabled', 'password_hash'];

    $stmt = $db->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");

    foreach ($allowedKeys as $key) {
        if (!array_key_exists($key, $input)) continue;

        $value = $input[$key];

        if ($key === 'maintenance_enabled' || $key === 'password_enabled') {
            $value = $value ? '1' : '0';
        } elseif ($key === 'maintenance_ips') {
            // S'assurer que c'est un array JSON valide
            if (!is_array($value)) {
                sendJSON(['error' => 'maintenance_ips doit etre un tableau'], 400);
            }
            $value = json_encode(array_values($value));
        } elseif ($key === 'password_hash') {
            // Hasher le mot de passe si non vide
            if ($value === '') {
                // Vider le hash = desactiver
                $value = '';
            } else {
                $value = password_hash($value, PASSWORD_DEFAULT);
            }
        }

        $stmt->execute([$value, $key]);
    }

    sendJSON(['success' => true, 'message' => 'Parametres mis a jour']);

} else {
    sendJSON(['error' => 'Methode non autorisee'], 405);
}
