<?php
/**
 * Connexion par email + mot de passe
 * POST : { email, password }
 * Rate limiting : max 5 tentatives par email ou IP sur 15 minutes
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
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// --- Rate limiting ---
$windowMinutes = 15;
$maxAttempts = 5;

// Nettoyer les anciennes tentatives (> 1 heure)
$db->exec("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");

// Compter les tentatives recentes par email
$stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
$stmt->execute([$email, $windowMinutes]);
$attemptsByEmail = (int)$stmt->fetchColumn();

// Compter les tentatives recentes par IP
$stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
$stmt->execute([$ip, $windowMinutes]);
$attemptsByIp = (int)$stmt->fetchColumn();

if ($attemptsByEmail >= $maxAttempts || $attemptsByIp >= $maxAttempts) {
    sendJSON(['error' => 'Trop de tentatives de connexion. Reessayez dans 15 minutes.'], 429);
}

// Chercher l'utilisateur par email
$stmt = $db->prepare("SELECT id, email, name, picture, password_hash, google_id, is_admin, email_verified FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    // Enregistrer la tentative echouee
    $stmt = $db->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)");
    $stmt->execute([$email, $ip]);
    sendJSON(['error' => 'Email ou mot de passe incorrect'], 401);
}

// Si l'utilisateur n'a pas de password_hash (compte Google uniquement)
if (!$user['password_hash']) {
    sendJSON(['error' => 'Ce compte utilise la connexion Google. Utilisez le bouton Google.'], 401);
}

// Verifier le mot de passe
if (!password_verify($password, $user['password_hash'])) {
    // Enregistrer la tentative echouee
    $stmt = $db->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)");
    $stmt->execute([$email, $ip]);
    sendJSON(['error' => 'Email ou mot de passe incorrect'], 401);
}

// Verifier que l'email est confirme
if (!$user['email_verified']) {
    sendJSON(['error' => 'Votre email n\'a pas ete verifie. Consultez votre boite de reception.', 'needs_verification' => true], 403);
}

// Connexion reussie - nettoyer les tentatives pour cet email
$stmt = $db->prepare("DELETE FROM login_attempts WHERE email = ?");
$stmt->execute([$email]);

// Rehash le mot de passe si l'algorithme a evolue
if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$newHash, $user['id']]);
}

// Mettre a jour last_login
$stmt = $db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
$stmt->execute([$user['id']]);

// Regenerer l'ID de session (anti session fixation)
session_regenerate_id(true);

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
