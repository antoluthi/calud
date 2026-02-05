<?php
/**
 * API de Tracking - Enregistre les visites de manière anonyme
 * Endpoint appelé à chaque chargement de page
 */

// Inclure la configuration pour la connexion DB et les headers CORS
require_once 'config.php';

// Handle preflight (déjà géré partiellement par config.php mais au cas où)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Connexion DB via la fonction centrale (utilise les bonnes credentials du .env)
$db = getDB();

// Récupérer les données
$input = json_decode(file_get_contents('php://input'), true);
$page = $input['page'] ?? $_SERVER['HTTP_REFERER'] ?? '/';

// Anonymiser l'IP avec hash
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
// Ajouter un "salt" quotidien pour rendre impossible le traçage sur longue période
$ipHash = hash('sha256', $ip . date('Y-m-d') . 'SALT_SECRET'); 

// Détecter le device et navigateur
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';

// Détection device type simplifié
function detectDeviceType($ua) {
    $ua = strtolower($ua);
    if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $ua)) {
        return 'tablet';
    }
    if (preg_match('/(mobile|iphone|ipod|android.*mobile|blackberry|phone|opera mini|iemobile)/i', $ua)) {
        return 'mobile';
    }
    return 'desktop';
}

// Détection navigateur simplifiée
function detectBrowser($ua) {
    if (preg_match('/Edge|Edg/i', $ua)) return 'Edge';
    if (preg_match('/Chrome/i', $ua)) return 'Chrome';
    if (preg_match('/Firefox/i', $ua)) return 'Firefox';
    if (preg_match('/Safari/i', $ua)) return 'Safari';
    if (preg_match('/Opera|OPR/i', $ua)) return 'Opera';
    if (preg_match('/MSIE|Trident/i', $ua)) return 'Internet Explorer';
    return 'Autre';
}

// Détection OS simplifiée
function detectOS($ua) {
    if (preg_match('/Windows/i', $ua)) return 'Windows';
    if (preg_match('/Macintosh|Mac OS/i', $ua)) return 'macOS';
    if (preg_match('/Linux/i', $ua)) return 'Linux';
    if (preg_match('/Android/i', $ua)) return 'Android';
    if (preg_match('/iOS|iPhone|iPad/i', $ua)) return 'iOS';
    return 'Autre';
}

$deviceType = detectDeviceType($userAgent);
$browser = detectBrowser($userAgent);
$os = detectOS($userAgent);

// Session ID basé sur IP hash + UA (empreinte journalière)
$sessionId = hash('sha256', $ipHash . $userAgent);

// Récupérer le user_id si l'utilisateur est connecté
$userId = null;
$user = getCurrentUser();
if ($user && isset($user['id'])) {
    $userId = (int)$user['id'];
}

// Insérer la visite
try {
    $stmt = $db->prepare("
        INSERT INTO visites (page, ip_hash, user_agent, referer, device_type, browser, os, session_id, user_id)
        VALUES (:page, :ip_hash, :user_agent, :referer, :device_type, :browser, :os, :session_id, :user_id)
    ");

    $stmt->execute([
        ':page' => substr($page, 0, 500),
        ':ip_hash' => $ipHash,
        ':user_agent' => substr($userAgent, 0, 500),
        ':referer' => substr($referer, 0, 500),
        ':device_type' => $deviceType,
        ':browser' => $browser,
        ':os' => $os,
        ':session_id' => $sessionId,
        ':user_id' => $userId
    ]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    // Log silencieux pour ne pas exposer l'erreur
    error_log('Erreur tracking: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to record visit']);
}
