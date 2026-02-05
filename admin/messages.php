<?php
/**
 * Gestion des Messages
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
    <title>Gestion des Messages - CRIMP. Admin</title>
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
                    <li><a href="statistiques.php"><span class="icon">📈</span> Statistiques</a></li>
                    <li><a href="produits.php"><span class="icon">📦</span> Produits</a></li>
                    <li><a href="commandes.php"><span class="icon">🛒</span> Commandes</a></li>
                    <li><a href="messages.php" class="active"><span class="icon">✉️</span> Messages</a></li>
                    <li><a href="newsletter.php"><span class="icon">📧</span> Newsletter</a></li>
                    <li><a href="clients.php"><span class="icon">👥</span> Clients</a></li>
                    <li><a href="maintenance.php"><span class="icon">🔧</span> Maintenance</a></li>
                    <li><a href="../index.html"><span class="icon">🏠</span> Retour au site</a></li>
                    <li><a href="../api/auth/logout.php"><span class="icon">🚪</span> Déconnexion</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>Gestion des Messages</h1>
                <p>Gérez les messages de contact des visiteurs</p>
            </div>

            <div id="alert-container"></div>

            <!-- Messages Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Liste des Messages</h2>
                    <div>
                        <span id="unreadCount" class="badge badge-info">0</span> non lus
                    </div>
                </div>
                <div id="messagesContainer">
                    <div style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                        <div class="loading"></div>
                        Chargement des messages...
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Message -->
    <div id="messageModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2 id="modalTitle">Message</h2>
                <button class="modal-close" onclick="closeMessageModal()">×</button>
            </div>
            <div id="messageContent" style="padding: 1rem;">
                <!-- Message content will be injected here -->
            </div>
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button class="btn btn-danger" onclick="deleteCurrentMessage()">🗑️ Supprimer</button>
                <button class="btn" onclick="closeMessageModal()" style="background-color: var(--bg-secondary); color: var(--text-primary);">Fermer</button>
            </div>
        </div>
    </div>

    <script src="js/messages.js"></script>
</body>
</html>
