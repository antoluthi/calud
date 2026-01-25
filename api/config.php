<?php
/**
 * Configuration de l'application
 * Ce fichier contient les paramètres de connexion à la base de données
 * et les credentials Google OAuth
 */

// Démarrer la session si pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration de la base de données
define('DB_HOST', 'localhost');          // Adresse du serveur MySQL
define('DB_NAME', 'site_escalade');      // Nom de la base de données
define('DB_USER', 'root');               // Utilisateur MySQL (à changer pour production)
define('DB_PASS', '');                   // Mot de passe MySQL (à changer pour production)
define('DB_CHARSET', 'utf8mb4');

// Configuration Google OAuth
define('GOOGLE_CLIENT_ID', 'VOTRE_CLIENT_ID_ICI');           // Remplacer par votre Client ID
define('GOOGLE_CLIENT_SECRET', 'VOTRE_CLIENT_SECRET_ICI');   // Remplacer par votre Client Secret

// URL de base (à adapter selon votre environnement)
// Pour le développement local
define('BASE_URL', 'http://localhost:8000');
// Pour la production, décommenter et modifier:
// define('BASE_URL', 'https://votre-domaine.com');

define('REDIRECT_URI', BASE_URL . '/api/auth/callback.php');

// Configuration des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers CORS pour permettre les requêtes depuis le frontend
header('Access-Control-Allow-Origin: *');
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
        $stmt = $db->prepare("SELECT id, email, name, picture FROM users WHERE id = ?");
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
