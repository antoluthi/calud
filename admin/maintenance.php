<?php
/**
 * Admin - Mode maintenance et protection par mot de passe
 */

require_once '../api/config.php';

if (!isAdmin()) {
    header('Location: ../index.html');
    exit;
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - Admin CRIMP.</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .setting-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
        }

        .setting-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .setting-card-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .setting-card-title h3 {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .setting-card-title .icon {
            font-size: 1.3rem;
        }

        /* Toggle switch */
        .toggle {
            position: relative;
            width: 50px;
            height: 26px;
            cursor: pointer;
        }

        .toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--border-color);
            border-radius: 26px;
            transition: all 0.3s ease;
        }

        .toggle-slider::before {
            content: '';
            position: absolute;
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: var(--text-primary);
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .toggle input:checked + .toggle-slider {
            background-color: var(--accent);
        }

        .toggle input:checked + .toggle-slider::before {
            transform: translateX(24px);
        }

        .setting-description {
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
            line-height: 1.5;
        }

        /* IP list */
        .ip-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .ip-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 0.75rem;
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.9rem;
        }

        .ip-item .remove-ip {
            background: none;
            border: none;
            color: var(--danger);
            cursor: pointer;
            font-size: 1.1rem;
            padding: 0 0.25rem;
            transition: opacity 0.3s ease;
        }

        .ip-item .remove-ip:hover {
            opacity: 0.7;
        }

        .ip-add-row {
            display: flex;
            gap: 0.5rem;
        }

        .ip-add-row input {
            flex: 1;
        }

        .current-ip {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.75rem;
            padding: 0.6rem 0.75rem;
            background-color: rgba(0, 212, 255, 0.08);
            border: 1px solid rgba(0, 212, 255, 0.2);
            border-radius: 8px;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .current-ip code {
            color: var(--accent);
            font-weight: 600;
        }

        .current-ip button {
            margin-left: auto;
            background: none;
            border: 1px solid var(--accent);
            color: var(--accent);
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .current-ip button:hover {
            background-color: var(--accent);
            color: var(--bg-primary);
        }

        /* Password section */
        .password-row {
            display: flex;
            gap: 0.5rem;
        }

        .password-row input {
            flex: 1;
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            padding: 0.2rem 0.6rem;
            border-radius: 6px;
        }

        .status-on {
            background-color: rgba(0, 255, 136, 0.15);
            color: var(--success);
        }

        .status-off {
            background-color: rgba(255, 68, 68, 0.15);
            color: var(--danger);
        }

        .empty-list {
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-style: italic;
            padding: 0.5rem 0;
        }

        .save-bar {
            position: sticky;
            bottom: 0;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1rem 1.5rem;
            display: none;
            align-items: center;
            justify-content: space-between;
            margin-top: 1.5rem;
        }

        .save-bar.visible {
            display: flex;
        }

        .save-bar-text {
            color: var(--warning);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>CRIMP. Admin</h2>
                <div class="user-info">
                    <img src="<?= htmlspecialchars($user['picture']) ?>" alt="Avatar" class="user-avatar">
                    <span class="user-name"><?= htmlspecialchars($user['name']) ?></span>
                </div>
            </div>
            <nav>
                <ul class="sidebar-nav">
                    <li><a href="index.php"><span class="icon">📊</span> Dashboard</a></li>
                    <li><a href="produits.php"><span class="icon">📦</span> Produits</a></li>
                    <li><a href="commandes.php"><span class="icon">🛒</span> Commandes</a></li>
                    <li><a href="messages.php"><span class="icon">✉️</span> Messages</a></li>
                    <li><a href="newsletter.php"><span class="icon">📧</span> Newsletter</a></li>
                    <li><a href="clients.php"><span class="icon">👥</span> Clients</a></li>
                    <li><a href="maintenance.php" class="active"><span class="icon">🔧</span> Maintenance</a></li>
                    <li><a href="../index.html"><span class="icon">🏠</span> Retour au site</a></li>
                    <li><a href="../api/auth/logout.php"><span class="icon">🚪</span> Déconnexion</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>Maintenance</h1>
                <p>Gerez l'acces au site : mode maintenance et protection par mot de passe</p>
            </div>

            <div id="alertContainer"></div>

            <div class="settings-grid">
                <!-- Card 1: Mode maintenance -->
                <div class="setting-card">
                    <div class="setting-card-header">
                        <div class="setting-card-title">
                            <span class="icon">🚧</span>
                            <h3>Mode maintenance</h3>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" id="maintenanceToggle">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <p class="setting-description">
                        Quand active, seules les IPs autorisees peuvent acceder au site. Les autres visiteurs voient une page de maintenance.
                        Les admins connectes bypassent toujours cette restriction.
                    </p>

                    <div class="form-group">
                        <label>IPs autorisees</label>
                        <div class="ip-list" id="ipList"></div>
                        <div class="ip-add-row">
                            <input type="text" class="form-control" id="newIpInput" placeholder="Ex: 192.168.1.1">
                            <button class="btn btn-primary btn-sm" id="addIpBtn">Ajouter</button>
                        </div>
                        <div class="current-ip" id="currentIpRow">
                            Votre IP actuelle : <code id="currentIpDisplay">...</code>
                            <button id="addCurrentIpBtn">Ajouter mon IP</button>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Protection par mot de passe -->
                <div class="setting-card">
                    <div class="setting-card-header">
                        <div class="setting-card-title">
                            <span class="icon">🔒</span>
                            <h3>Protection par mot de passe</h3>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" id="passwordToggle">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <p class="setting-description">
                        Quand active, les visiteurs doivent entrer un mot de passe pour acceder au site.
                        La page de mot de passe permet aussi de se connecter via Google (pour les admins).
                    </p>

                    <div class="form-group">
                        <label>Definir le mot de passe</label>
                        <div class="password-row">
                            <input type="password" class="form-control" id="passwordInput" placeholder="Nouveau mot de passe">
                            <button class="btn btn-primary btn-sm" id="setPasswordBtn">Definir</button>
                        </div>
                        <div style="margin-top: 0.5rem;">
                            <span class="status-indicator" id="passwordStatus">
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save bar -->
            <div class="save-bar" id="saveBar">
                <span class="save-bar-text">Modifications non enregistrees</span>
                <button class="btn btn-primary" id="saveBtn">Enregistrer</button>
            </div>
        </main>
    </div>

    <script src="js/maintenance.js"></script>
</body>
</html>
