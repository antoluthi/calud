<?php
/**
 * Connexion par email + mot de passe
 * POST : { email, password }
 */

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['error' => 'Methode non autorisee'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    sendJSON(['error' => 'Email et mot de passe requis'], 400);
}

$db = getDB();

// Chercher l'utilisateur par email
$stmt = $db->prepare("SELECT id, email, name, picture, password_hash, google_id, is_admin FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    sendJSON(['error' => 'Email ou mot de passe incorrect'], 401);
}

// Si l'utilisateur n'a pas de password_hash (compte Google uniquement)
if (!$user['password_hash']) {
    sendJSON(['error' => 'Ce compte utilise la connexion Google. Utilisez le bouton Google.'], 401);
}

// Verifier le mot de passe
if (!password_verify($password, $user['password_hash'])) {
    sendJSON(['error' => 'Email ou mot de passe incorrect'], 401);
}

// Mettre a jour last_login
$stmt = $db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
$stmt->execute([$user['id']]);

// Demarrer la session
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_picture'] = $user['picture'] ?? '';

sendJSON([
    'success' => true,
    'user' => [
        'id' => (int)$user['id'],
        'email' => $user['email'],
        'name' => $user['name'],
        'picture' => $user['picture'] ?? '',
        'is_admin' => (bool)$user['is_admin']
    ]
]);
