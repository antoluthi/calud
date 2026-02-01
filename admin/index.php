<?php
/**
 * Dashboard Admin
 */

require_once '../api/config.php';

// Vérifier que l'utilisateur est admin
if (!isAdmin()) {
    header('Location: ../index.html');
    exit;
}

$user = getCurrentUser();
$db = getDB();

// Récupérer les statistiques
$statsQuery = $db->query("
    SELECT
        (SELECT COUNT(*) FROM produits WHERE actif = 1) as produits_actifs,
        (SELECT COUNT(*) FROM users) as total_utilisateurs,
        (SELECT COUNT(*) FROM commandes) as total_commandes,
        (SELECT COUNT(*) FROM commandes WHERE status = 'pending') as commandes_en_attente
");
$stats = $statsQuery->fetch();

// Récupérer les dernières commandes
$commandesQuery = $db->query("
    SELECT c.*, u.name as client_name, u.email as client_email
    FROM commandes c
    LEFT JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC
    LIMIT 5
");
$dernieresCommandes = $commandesQuery->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - CRIMP.</title>
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
                    <li><a href="index.php" class="active"><span class="icon">📊</span> Dashboard</a></li>
                    <li><a href="produits.php"><span class="icon">📦</span> Produits</a></li>
                    <li><a href="commandes.php"><span class="icon">🛒</span> Commandes</a></li>
                    <li><a href="messages.php"><span class="icon">✉️</span> Messages</a></li>
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
                <h1>Dashboard</h1>
                <p>Vue d'ensemble de votre site e-commerce</p>
            </div>

            <!-- Stats Cards -->
            <div class="cards-grid">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Produits Actifs</span>
                        <span class="card-icon">📦</span>
                    </div>
                    <div class="card-value"><?= $stats['produits_actifs'] ?></div>
                    <div class="card-subtitle">Produits disponibles</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Total Commandes</span>
                        <span class="card-icon">🛒</span>
                    </div>
                    <div class="card-value"><?= $stats['total_commandes'] ?></div>
                    <div class="card-subtitle">Toutes les commandes</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title">En Attente</span>
                        <span class="card-icon">⏳</span>
                    </div>
                    <div class="card-value"><?= $stats['commandes_en_attente'] ?></div>
                    <div class="card-subtitle">Commandes à traiter</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Clients</span>
                        <span class="card-icon">👥</span>
                    </div>
                    <div class="card-value"><?= $stats['total_utilisateurs'] ?></div>
                    <div class="card-subtitle">Utilisateurs inscrits</div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Dernières Commandes</h2>
                    <a href="commandes.php" class="btn btn-primary btn-sm">Voir toutes</a>
                </div>
                <?php if (empty($dernieresCommandes)): ?>
                    <div style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                        Aucune commande pour le moment
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Total</th>
                                <th>Statut</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dernieresCommandes as $commande): ?>
                                <tr>
                                    <td>#<?= $commande['id'] ?></td>
                                    <td><?= htmlspecialchars($commande['client_name'] ?? trim(($commande['first_name'] ?? '') . ' ' . ($commande['last_name'] ?? '')) ?: 'Client') ?></td>
                                    <td><?= number_format($commande['total'], 2, ',', ' ') ?> €</td>
                                    <td>
                                        <?php
                                        $badgeClass = [
                                            'pending' => 'badge-warning',
                                            'paid' => 'badge-info',
                                            'processing' => 'badge-info',
                                            'shipped' => 'badge-success',
                                            'delivered' => 'badge-success',
                                            'cancelled' => 'badge-danger'
                                        ][$commande['status']] ?? 'badge-info';

                                        $statusLabel = [
                                            'pending' => 'En attente',
                                            'paid' => 'Payée',
                                            'processing' => 'En préparation',
                                            'shipped' => 'Expédiée',
                                            'delivered' => 'Livrée',
                                            'cancelled' => 'Annulée'
                                        ][$commande['status']] ?? $commande['status'];
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($commande['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
