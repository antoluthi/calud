<?php
/**
 * Gestion de la Newsletter
 */

require_once '../api/config.php';

// Vérifier que l'utilisateur est admin
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
    <title>Gestion de la Newsletter - CRIMP. Admin</title>
    <link rel="stylesheet" href="css/admin.css">
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
                    <li><a href="newsletter.php" class="active"><span class="icon">📧</span> Newsletter</a></li>
                    <li><a href="clients.php"><span class="icon">👥</span> Clients</a></li>
                    <li><a href="../index.html"><span class="icon">🏠</span> Retour au site</a></li>
                    <li><a href="../api/auth/logout.php"><span class="icon">🚪</span> Déconnexion</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>Gestion de la Newsletter</h1>
                <p>Gérez vos abonnés et envoyez des emails en masse</p>
            </div>

            <div id="alert-container"></div>

            <!-- Stats Cards -->
            <div class="cards-grid" style="margin-bottom: 2rem;">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Total Abonnés</span>
                        <span class="card-icon">📧</span>
                    </div>
                    <div class="card-value" id="totalSubscribers">0</div>
                    <div class="card-subtitle">Tous les abonnés</div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Abonnés Actifs</span>
                        <span class="card-icon">✅</span>
                    </div>
                    <div class="card-value" id="activeSubscribers">0</div>
                    <div class="card-subtitle">Recevront les emails</div>
                </div>
            </div>

            <!-- Send Email Section -->
            <div class="table-container" style="margin-bottom: 2rem;">
                <div class="table-header">
                    <h2>Envoyer un Email</h2>
                </div>
                <div style="padding: 2rem;">
                    <form id="emailForm">
                        <div class="form-group">
                            <label for="emailSubject">Sujet de l'email *</label>
                            <input type="text" id="emailSubject" name="subject" class="form-control" required placeholder="Nouvelle collection disponible !">
                        </div>

                        <div class="form-group">
                            <label for="emailMessage">Message *</label>
                            <textarea id="emailMessage" name="message" class="form-control" required placeholder="Votre message ici..." style="min-height: 200px;"></textarea>
                            <small style="color: var(--text-secondary); font-size: 0.85rem;">Le message sera envoyé à tous les abonnés actifs</small>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <span id="sendBtnText">📤 Envoyer à tous les abonnés</span>
                            <span id="sendBtnLoader" class="loading" style="display: none;"></span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Subscribers Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Liste des Abonnés</h2>
                </div>
                <div id="subscribersContainer">
                    <div style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                        <div class="loading"></div>
                        Chargement des abonnés...
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="js/newsletter.js"></script>
</body>
</html>
