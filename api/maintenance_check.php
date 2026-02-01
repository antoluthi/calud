<?php
/**
 * Middleware de maintenance et protection par mot de passe
 * A inclure en haut des pages publiques (index.html, checkout.html, mes-commandes.html)
 */

// Ne pas bloquer les routes API, admin, assets, password.php, maintenance.html
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$requestPath = parse_url($requestUri, PHP_URL_PATH);

$skipPaths = [
    '/api/',
    '/admin/',
    '/css/',
    '/js/',
    '/images/',
    '/guides/',
    '/favicon.ico',
    '/password.php',
    '/maintenance.html',
];

foreach ($skipPaths as $path) {
    if (strpos($requestPath, $path) === 0 || $requestPath === $path) {
        return; // Laisser passer
    }
}

// Charger la config pour la session et la DB
require_once __DIR__ . '/config.php';

// Admin bypass total
if (isAdmin()) {
    return;
}

// Recuperer les settings depuis la DB
try {
    $db = getDB();
    $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Si la table n'existe pas encore, laisser passer
    return;
}

// Reinitialiser le Content-Type car config.php met application/json
header('Content-Type: text/html; charset=UTF-8');

// Check maintenance mode
$maintenanceEnabled = ($settings['maintenance_enabled'] ?? '0') === '1';
if ($maintenanceEnabled) {
    $allowedIps = json_decode($settings['maintenance_ips'] ?? '[]', true) ?: [];
    $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    // Prendre la premiere IP si X-Forwarded-For contient plusieurs
    if (strpos($clientIp, ',') !== false) {
        $clientIp = trim(explode(',', $clientIp)[0]);
    }

    if (!in_array($clientIp, $allowedIps)) {
        http_response_code(503);
        header('Retry-After: 3600');
        readfile(__DIR__ . '/../maintenance.html');
        exit;
    }
}

// Check password protection
$passwordEnabled = ($settings['password_enabled'] ?? '0') === '1';
if ($passwordEnabled) {
    $passwordHash = $settings['password_hash'] ?? '';
    if ($passwordHash !== '' && empty($_SESSION['site_password_ok'])) {
        header('Location: /password.php');
        exit;
    }
}
