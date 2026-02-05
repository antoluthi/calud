<?php
/**
 * Inscription par email + mot de passe
 * POST : { email, password, name }
 */

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['error' => 'Methode non autorisee'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$name = trim($input['name'] ?? '');

// Validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJSON(['error' => 'Adresse email invalide'], 400);
}

if (strlen($password) < 8) {
    sendJSON(['error' => 'Le mot de passe doit contenir au moins 8 caracteres'], 400);
}

if (empty($name)) {
    sendJSON(['error' => 'Le nom est requis'], 400);
}

$db = getDB();

// Verifier si l'email existe deja
$stmt = $db->prepare("SELECT id, google_id, password_hash FROM users WHERE email = ?");
$stmt->execute([$email]);
$existingUser = $stmt->fetch();

if ($existingUser) {
    if ($existingUser['google_id'] && !$existingUser['password_hash']) {
        sendJSON(['error' => 'Un compte Google existe deja avec cet email. Utilisez la connexion Google.'], 409);
    }
    if ($existingUser['password_hash']) {
        sendJSON(['error' => 'Un compte existe deja avec cet email. Connectez-vous.'], 409);
    }
}

// Hash du mot de passe
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Creer le compte
$stmt = $db->prepare("
    INSERT INTO users (google_id, email, name, picture, password_hash, auth_method)
    VALUES (NULL, ?, ?, '', ?, 'email')
");
$stmt->execute([$email, $name, $passwordHash]);
$userId = $db->lastInsertId();

// Demarrer la session
$_SESSION['user_id'] = $userId;
$_SESSION['user_email'] = $email;
$_SESSION['user_name'] = $name;
$_SESSION['user_picture'] = '';

sendJSON([
    'success' => true,
    'user' => [
        'id' => (int)$userId,
        'email' => $email,
        'name' => $name,
        'picture' => '',
        'is_admin' => false
    ]
]);
