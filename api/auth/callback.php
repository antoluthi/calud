<?php
/**
 * Callback OAuth Google
 * Reçoit le code d'autorisation et échange contre un access token
 * Récupère les informations utilisateur et crée/met à jour l'utilisateur en DB
 */

require_once '../config.php';

// Vérifier l'état pour prévenir les attaques CSRF
if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    die('Erreur de sécurité: état OAuth invalide');
}

// Vérifier si on a reçu un code
if (!isset($_GET['code'])) {
    die('Erreur: code d\'autorisation manquant');
}

$code = $_GET['code'];

// Échanger le code contre un access token
$tokenUrl = 'https://oauth2.googleapis.com/token';
$tokenData = [
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => REDIRECT_URI,
    'grant_type' => 'authorization_code'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$tokenInfo = json_decode($response, true);

if (!isset($tokenInfo['access_token'])) {
    die('Erreur: impossible d\'obtenir l\'access token');
}

$accessToken = $tokenInfo['access_token'];

// Utiliser l'access token pour obtenir les infos utilisateur
$userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$userInfo = json_decode($response, true);

if (!isset($userInfo['id'])) {
    die('Erreur: impossible d\'obtenir les informations utilisateur');
}

// Extraire les informations
$googleId = $userInfo['id'];
$email = $userInfo['email'];
$name = $userInfo['name'] ?? '';
$picture = $userInfo['picture'] ?? '';

// Connexion à la base de données
$db = getDB();

// Vérifier si l'utilisateur existe déjà
$stmt = $db->prepare("SELECT id FROM users WHERE google_id = ?");
$stmt->execute([$googleId]);
$existingUser = $stmt->fetch();

if ($existingUser) {
    // Mettre à jour les informations de l'utilisateur existant
    $stmt = $db->prepare("
        UPDATE users
        SET email = ?, name = ?, picture = ?, last_login = CURRENT_TIMESTAMP
        WHERE google_id = ?
    ");
    $stmt->execute([$email, $name, $picture, $googleId]);
    $userId = $existingUser['id'];
} else {
    // Créer un nouvel utilisateur
    $stmt = $db->prepare("
        INSERT INTO users (google_id, email, name, picture)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$googleId, $email, $name, $picture]);
    $userId = $db->lastInsertId();
}

// Enregistrer l'utilisateur dans la session
$_SESSION['user_id'] = $userId;
$_SESSION['user_email'] = $email;
$_SESSION['user_name'] = $name;
$_SESSION['user_picture'] = $picture;

// Nettoyer l'état OAuth
unset($_SESSION['oauth_state']);

// Rediriger vers la page d'accueil
header('Location: ' . BASE_URL . '/index.html?login=success');
exit;
