<?php
/**
 * Configuration de l'application
 * Ce fichier lit les credentials depuis un fichier .env sur le serveur
 * pour éviter d'exposer les credentials dans Git
 */

// Démarrer la session si pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Charger le fichier .env s'il existe (sur le serveur uniquement)
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Ignorer les commentaires
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Configuration de la base de données (lire depuis .env ou valeurs par défaut)
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'site_escalade');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', 'utf8mb4');

// Configuration Google OAuth (lire depuis .env ou valeurs par défaut)
define('GOOGLE_CLIENT_ID', $_ENV['GOOGLE_CLIENT_ID'] ?? 'VOTRE_CLIENT_ID_ICI');
define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET'] ?? 'VOTRE_CLIENT_SECRET_ICI');

// URL de base (lire depuis .env ou valeur par défaut)
define('BASE_URL', $_ENV['BASE_URL'] ?? 'http://localhost:8000');

define('REDIRECT_URI', BASE_URL . '/api/auth/callback.php');

// Configuration des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers CORS pour permettre les requêtes depuis le frontend
$allowedOrigin = $_ENV['BASE_URL'] ?? 'http://localhost:8000';
header('Access-Control-Allow-Origin: ' . $allowedOrigin);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Fonction pour obtenir une connexion à la base de données
function getDB() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log("Erreur de connexion DB: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Erreur de connexion à la base de données']);
        exit;
    }
}

// Fonction pour envoyer une réponse JSON
function sendJSON($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Fonction pour obtenir l'utilisateur connecté
function getCurrentUser() {
    if (isset($_SESSION['user_id'])) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, email, name, picture, is_admin FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    return null;
}

// Fonction pour vérifier si l'utilisateur est connecté
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        sendJSON(['error' => 'Non authentifié'], 401);
    }
}

// Fonction pour vérifier si l'utilisateur est admin
function isAdmin() {
    $user = getCurrentUser();
    return $user && isset($user['is_admin']) && $user['is_admin'] == 1;
}

// Fonction pour exiger les droits admin
function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        sendJSON(['error' => 'Accès refusé: droits administrateur requis'], 403);
    }
}
