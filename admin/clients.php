<?php
/**
 * Gestion des Clients/Utilisateurs
 */

require_once '../api/config.php';

// Vérifier que l'utilisateur est admin
if (!isAdmin()) {
    header('Location: ../index.html');
    exit;
}

$user = getCurrentUser();
$db = getDB();

// Récupérer tous les utilisateurs avec stats
$clientsQuery = $db->query("
    SELECT
        u.*,
        COUNT(DISTINCT c.id) as nb_commandes,
        COALESCE(SUM(c.total), 0) as total_depense
    FROM users u
    LEFT JOIN commandes c ON u.id = c.user_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$clients = $clientsQuery->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Clients - CRIMP. Admin</title>
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
                    <li><a href="newsletter.php"><span class="icon">📧</span> Newsletter</a></li>
                    <li><a href="clients.php" class="active"><span class="icon">👥</span> Clients</a></li>
                    <li><a href="../index.html"><span class="icon">🏠</span> Retour au site</a></li>
                    <li><a href="../api/auth/logout.php"><span class="icon">🚪</span> Déconnexion</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>Gestion des Clients</h1>
                <p>Liste complète des utilisateurs inscrits</p>
            </div>

            <div id="alert-container"></div>

            <!-- Stats rapides -->
            <div class="cards-grid" style="margin-bottom: 2rem;">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Total Clients</span>
                        <span class="card-icon">👥</span>
                    </div>
                    <div class="card-value"><?= count($clients) ?></div>
                    <div class="card-subtitle">Utilisateurs inscrits</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Administrateurs</span>
                        <span class="card-icon">🔐</span>
                    </div>
                    <div class="card-value"><?= count(array_filter($clients, fn($c) => $c['is_admin'])) ?></div>
                    <div class="card-subtitle">Comptes admin</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Clients actifs</span>
                        <span class="card-icon">💚</span>
                    </div>
                    <div class="card-value"><?= count(array_filter($clients, fn($c) => $c['nb_commandes'] > 0)) ?></div>
                    <div class="card-subtitle">Ont passé commande</div>
                </div>
            </div>

            <!-- Clients Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Liste des Clients</h2>
                    <input type="text" id="searchInput" placeholder="🔍 Rechercher par nom ou email..."
                           style="padding: 0.5rem 1rem; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-primary); width: 300px;">
                </div>
                <?php if (empty($clients)): ?>
                    <div style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                        Aucun client inscrit
                    </div>
                <?php else: ?>
                    <table id="clientsTable">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Email</th>
                                <th>Inscription</th>
                                <th>Dernière connexion</th>
                                <th>Commandes</th>
                                <th>Total dépensé</th>
                                <th>Newsletter</th>
                                <th>Rôle</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                                <tr class="client-row" data-email="<?= htmlspecialchars($client['email']) ?>" data-name="<?= htmlspecialchars($client['name']) ?>">
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <?php if ($client['picture']): ?>
                                                <img src="<?= htmlspecialchars($client['picture']) ?>" alt="Avatar"
                                                     style="width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--border-color);">
                                            <?php endif; ?>
                                            <div>
                                                <div style="font-weight: 600;"><?= htmlspecialchars($client['name']) ?></div>
                                                <?php if ($client['id'] == $user['id']): ?>
                                                    <span class="badge badge-info" style="font-size: 0.7rem;">Vous</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="color: var(--text-secondary);"><?= htmlspecialchars($client['email']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($client['created_at'])) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($client['last_login'])) ?></td>
                                    <td style="text-align: center;">
                                        <?php if ($client['nb_commandes'] > 0): ?>
                                            <span class="badge badge-info"><?= $client['nb_commandes'] ?></span>
                                        <?php else: ?>
                                            <span style="color: var(--text-secondary);">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-weight: 600;"><?= number_format($client['total_depense'], 2, ',', ' ') ?> €</td>
                                    <td style="text-align: center;">
                                        <?php if (isset($client['newsletter_subscribed']) && $client['newsletter_subscribed']): ?>
                                            <span class="badge badge-success">📧 Abonné</span>
                                        <?php else: ?>
                                            <span style="color: var(--text-secondary);">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($client['is_admin']): ?>
                                            <span class="badge badge-warning">👑 Admin</span>
                                        <?php else: ?>
                                            <span class="badge" style="background-color: rgba(255,255,255,0.1); color: var(--text-secondary);">Client</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn-icon" onclick='viewClient(<?= json_encode($client) ?>)' title="Voir détails">👁️</button>
                                        <?php if ($client['id'] != $user['id']): ?>
                                            <button class="btn-icon" onclick="toggleAdmin(<?= $client['id'] ?>, <?= $client['is_admin'] ? 'false' : 'true' ?>, '<?= htmlspecialchars($client['name'], ENT_QUOTES) ?>')"
                                                    title="<?= $client['is_admin'] ? 'Retirer droits admin' : 'Donner droits admin' ?>">
                                                <?= $client['is_admin'] ? '👤' : '👑' ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal Détails Client -->
    <div id="clientModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Détails du client</h2>
                <button class="modal-close" onclick="closeClientModal()">×</button>
            </div>
            <div id="clientDetails"></div>
        </div>
    </div>

    <script src="js/clients.js"></script>
</body>
</html>
