<?php
require_once __DIR__ . '/api/config.php';

// Reinitialiser le Content-Type car config.php met application/json
header('Content-Type: text/html; charset=UTF-8');

// Si deja authentifie par mot de passe ou admin, rediriger vers l'accueil
if (!empty($_SESSION['site_password_ok']) || isAdmin()) {
    header('Location: /');
    exit;
}

// Traiter le POST du mot de passe
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'password_hash'");
        $stmt->execute();
        $row = $stmt->fetch();
        $storedHash = $row ? $row['setting_value'] : '';

        if ($storedHash !== '' && password_verify($_POST['password'], $storedHash)) {
            $_SESSION['site_password_ok'] = true;
            header('Location: /');
            exit;
        } else {
            $error = 'Mot de passe incorrect';
        }
    } catch (Exception $e) {
        $error = 'Erreur serveur';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acces protege - CRIMP.</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-primary: #0a0a0a;
            --bg-secondary: #111111;
            --bg-card: #181818;
            --text-primary: #ffffff;
            --text-secondary: #888888;
            --accent: #ffffff;
            --accent-hover: #cccccc;
            --border-color: #2a2a2a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Space Grotesk', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.07) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
            z-index: 0;
        }

        .container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }

        .logo {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--text-primary);
            margin-bottom: 2.5rem;
        }

        .card {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2rem;
        }

        .card-icon {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .card h2 {
            text-align: center;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .card-subtitle {
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 0.95rem;
            font-family: inherit;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
        }

        .submit-btn {
            width: 100%;
            padding: 0.875rem;
            background-color: var(--accent);
            border: none;
            border-radius: 100px;
            color: var(--bg-primary);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-family: inherit;
        }

        .submit-btn:hover {
            background-color: var(--accent-hover);
            transform: translateY(-2px);
        }

        .error-msg {
            background-color: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 0.75rem;
            border-radius: 10px;
            text-align: center;
            font-size: 0.9rem;
            margin-bottom: 1.25rem;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.5rem 0;
            color: var(--text-secondary);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background-color: var(--border-color);
        }

        .google-btn {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 0.875rem 1.5rem;
            background-color: transparent;
            border: 1px solid var(--border-color);
            border-radius: 100px;
            color: var(--text-primary);
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            text-decoration: none;
        }

        .google-btn:hover {
            background-color: var(--accent);
            border-color: var(--accent);
            color: var(--bg-primary);
        }

        .google-btn svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">CRIMP.</div>
        <div class="card">
            <div class="card-icon">🔒</div>
            <h2>Acces protege</h2>
            <p class="card-subtitle">Entrez le mot de passe pour acceder au site</p>

            <?php if ($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="/password.php">
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required autofocus placeholder="Entrez le mot de passe">
                </div>
                <button type="submit" class="submit-btn">Acceder</button>
            </form>

            <div class="divider">ou</div>

            <a href="/api/auth/login.php" class="google-btn">
                <svg viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z" fill="#4285F4"/>
                    <path d="M9.003 18c2.43 0 4.467-.806 5.956-2.18L12.05 13.56c-.806.54-1.836.86-3.047.86-2.344 0-4.328-1.584-5.036-3.711H.96v2.332C2.438 15.983 5.482 18 9.003 18z" fill="#34A853"/>
                    <path d="M3.964 10.712c-.18-.54-.282-1.117-.282-1.71 0-.593.102-1.17.282-1.71V4.96H.957C.347 6.175 0 7.55 0 9.002c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/>
                    <path d="M9.003 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.464.891 11.428 0 9.002 0 5.48 0 2.438 2.017.96 4.958L3.967 7.29c.708-2.127 2.692-3.71 5.036-3.71z" fill="#EA4335"/>
                </svg>
                Se connecter avec Google
            </a>
        </div>
    </div>
</body>
</html>
