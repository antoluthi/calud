<?php
/**
 * Verification d'email
 * GET : ?token=xxx
 * Valide le token, active le compte, redirige vers le site
 */

require_once '../config.php';

// Override le Content-Type JSON de config.php
header('Content-Type: text/html; charset=utf-8');

$token = $_GET['token'] ?? '';

if (empty($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    showResult(false, 'Lien de verification invalide.');
    exit;
}

$db = getDB();

// Chercher l'utilisateur avec ce token
$stmt = $db->prepare("SELECT id, email, name, verification_token_expires FROM users WHERE verification_token = ? AND email_verified = 0");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    showResult(false, 'Ce lien est invalide ou a deja ete utilise.');
    exit;
}

// Verifier l'expiration
if (strtotime($user['verification_token_expires']) < time()) {
    showResult(false, 'Ce lien a expire. Veuillez vous re-inscrire.');
    exit;
}

// Activer le compte
$stmt = $db->prepare("UPDATE users SET email_verified = 1, verification_token = NULL, verification_token_expires = NULL WHERE id = ?");
$stmt->execute([$user['id']]);

// Connecter l'utilisateur directement
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_picture'] = '';

showResult(true, 'Votre compte a ete active avec succes !');

/**
 * Affiche une page de resultat avec le theme CRIMP.
 */
function showResult($success, $message) {
    $icon = $success ? '&#10003;' : '&#10007;';
    $iconBg = $success ? 'rgba(74, 222, 128, 0.15)' : 'rgba(248, 113, 113, 0.15)';
    $iconColor = $success ? '#4ade80' : '#f87171';
    $btnText = $success ? 'Acceder au site' : 'Retour a l\'accueil';
    $baseUrl = defined('BASE_URL') ? BASE_URL : '';

    echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification - CRIMP.</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Space Grotesk", sans-serif;
            background-color: #0a0a0a;
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.07) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
            z-index: 0;
        }
        .card {
            position: relative;
            z-index: 1;
            background: #181818;
            border: 1px solid #2a2a2a;
            border-radius: 20px;
            padding: 3rem 2.5rem;
            max-width: 440px;
            width: 90%;
            text-align: center;
        }
        .icon {
            width: 64px; height: 64px;
            background: ' . $iconBg . ';
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: ' . $iconColor . ';
        }
        h1 {
            font-size: 1.1rem;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 2rem;
            color: #ffffff;
        }
        .message {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        .sub {
            color: #888888;
            font-size: 0.9rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            padding: 0.875rem 2.5rem;
            background: #ffffff;
            color: #0a0a0a;
            text-decoration: none;
            border-radius: 100px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: opacity 0.2s;
            font-family: inherit;
        }
        .btn:hover { opacity: 0.85; }
    </style>
</head>
<body>
    <div class="card">
        <h1>CRIMP.</h1>
        <div class="icon">' . $icon . '</div>
        <p class="message">' . htmlspecialchars($message) . '</p>
        <p class="sub">' . ($success ? 'Vous etes maintenant connecte.' : 'Veuillez reessayer ou contactez-nous.') . '</p>
        <a href="' . $baseUrl . '/" class="btn">' . $btnText . '</a>
    </div>
</body>
</html>';
}
