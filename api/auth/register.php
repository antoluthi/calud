<?php
/**
 * Inscription par email + mot de passe
 * POST : { email, password, name }
 * Envoie un email de verification (le compte n'est pas actif tant que non verifie)
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
$stmt = $db->prepare("SELECT id, google_id, password_hash, email_verified FROM users WHERE email = ?");
$stmt->execute([$email]);
$existingUser = $stmt->fetch();

if ($existingUser) {
    if ($existingUser['google_id'] && !$existingUser['password_hash']) {
        sendJSON(['error' => 'Un compte Google existe deja avec cet email. Utilisez la connexion Google.'], 409);
    }
    if ($existingUser['password_hash'] && $existingUser['email_verified']) {
        sendJSON(['error' => 'Un compte existe deja avec cet email. Connectez-vous.'], 409);
    }
    // Compte existant non verifie : on le supprime pour permettre une re-inscription
    if ($existingUser['password_hash'] && !$existingUser['email_verified']) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$existingUser['id']]);
    }
}

// Hash du mot de passe
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Generer un token de verification
$token = bin2hex(random_bytes(32));
$tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

// Creer le compte (non verifie)
$stmt = $db->prepare("
    INSERT INTO users (google_id, email, name, picture, password_hash, auth_method, email_verified, verification_token, verification_token_expires)
    VALUES (NULL, ?, ?, '', ?, 'email', 0, ?, ?)
");
$stmt->execute([$email, $name, $passwordHash, $token, $tokenExpires]);

// Envoyer l'email de verification
$verifyUrl = BASE_URL . '/api/auth/verify-email.php?token=' . $token;
sendVerificationEmail($email, $name, $verifyUrl);

sendJSON([
    'success' => true,
    'needs_verification' => true,
    'message' => 'Un email de verification a ete envoye a ' . $email
]);

/**
 * Envoie l'email de verification avec template sombre CRIMP.
 */
function sendVerificationEmail($email, $name, $verifyUrl) {
    $domain = $_SERVER['HTTP_HOST'] ?? 'antonin.luthi.eu';

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: CRIMP. <noreply@" . $domain . ">\r\n";
    $headers .= "Reply-To: contact@" . $domain . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $subject = "Verifiez votre compte CRIMP.";

    $safeName = htmlspecialchars($name);
    $safeUrl = htmlspecialchars($verifyUrl);

    $htmlMessage = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="utf-8"></head>
    <body style="margin: 0; padding: 0; background-color: #0a0a0a; font-family: Arial, Helvetica, sans-serif;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #0a0a0a; padding: 20px 0;">
            <tr><td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">

                    <!-- Header -->
                    <tr><td style="padding: 30px 24px; text-align: center;">
                        <h1 style="margin: 0; color: #ffffff; font-size: 20px; letter-spacing: 3px; text-transform: uppercase;">CRIMP.</h1>
                    </td></tr>

                    <!-- Content -->
                    <tr><td style="padding: 0 24px 24px;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #181818; border-radius: 12px; border: 1px solid #2a2a2a;">
                            <tr><td style="padding: 32px 24px; text-align: center;">
                                <div style="width: 56px; height: 56px; background-color: rgba(74, 222, 128, 0.15); border-radius: 50%; margin: 0 auto 20px; line-height: 56px; font-size: 28px;">&#9993;</div>
                                <h2 style="margin: 0 0 12px; color: #ffffff; font-size: 22px; font-weight: 600;">Verifiez votre email</h2>
                                <p style="margin: 0 0 24px; color: #888888; font-size: 14px; line-height: 1.6;">
                                    Bonjour ' . $safeName . ',<br>
                                    Merci d\'avoir cree votre compte CRIMP. Cliquez sur le bouton ci-dessous pour activer votre compte.
                                </p>
                                <a href="' . $safeUrl . '" style="display: inline-block; padding: 14px 40px; background-color: #ffffff; color: #0a0a0a; text-decoration: none; border-radius: 100px; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Activer mon compte</a>
                                <p style="margin: 24px 0 0; color: #888888; font-size: 12px; line-height: 1.6;">
                                    Ce lien expire dans 24 heures.<br>
                                    Si vous n\'avez pas cree de compte, ignorez cet email.
                                </p>
                            </td></tr>
                        </table>
                    </td></tr>

                    <!-- Fallback URL -->
                    <tr><td style="padding: 0 24px 24px; text-align: center;">
                        <p style="margin: 0; color: #555555; font-size: 11px; line-height: 1.6; word-break: break-all;">
                            Si le bouton ne fonctionne pas, copiez ce lien :<br>
                            <a href="' . $safeUrl . '" style="color: #888888;">' . $safeUrl . '</a>
                        </p>
                    </td></tr>

                    <!-- Footer -->
                    <tr><td style="padding: 16px 24px; text-align: center; border-top: 1px solid #2a2a2a;">
                        <p style="margin: 0; color: #555555; font-size: 11px;">&copy; 2026 CRIMP. - Tous droits reserves</p>
                    </td></tr>

                </table>
            </td></tr>
        </table>
    </body>
    </html>';

    try {
        mail($email, $subject, $htmlMessage, $headers);
    } catch (Exception $e) {
        error_log("Erreur envoi email verification: " . $e->getMessage());
    }
}
