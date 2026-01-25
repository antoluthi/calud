<?php
/**
 * Gestion des Commandes
 */

require_once '../api/config.php';

// Vérifier que l'utilisateur est admin
if (!isAdmin()) {
    header('Location: ../index.html');
    exit;
}

$user = getCurrentUser();
$db = getDB();

// Récupérer toutes les commandes avec les informations du client
$commandesQuery = $db->query("
    SELECT c.*, u.name as client_name, u.email as client_email, u.picture as client_picture
    FROM commandes c
    LEFT JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC
");
$commandes = $commandesQuery->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Commandes - AL Admin</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>AL Admin</h2>
                <div class="user-info">
                    <img src="<?= htmlspecialchars($user['picture']) ?>" alt="Avatar" class="user-avatar">
                    <span class="user-name"><?= htmlspecialchars($user['name']) ?></span>
                </div>
            </div>
            <nav>
                <ul class="sidebar-nav">
                    <li><a href="index.php"><span class="icon">📊</span> Dashboard</a></li>
                    <li><a href="produits.php"><span class="icon">📦</span> Produits</a></li>
                    <li><a href="commandes.php" class="active"><span class="icon">🛒</span> Commandes</a></li>
                    <li><a href="../index.html"><span class="icon">🏠</span> Retour au site</a></li>
                    <li><a href="../api/auth/logout.php"><span class="icon">🚪</span> Déconnexion</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>Gestion des Commandes</h1>
                <p>Suivez et gérez toutes les commandes</p>
            </div>

            <div id="alert-container"></div>

            <!-- Orders Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Liste des Commandes</h2>
                </div>
                <?php if (empty($commandes)): ?>
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commandes as $commande): ?>
                                <tr>
                                    <td>#<?= $commande['id'] ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <?php if ($commande['client_picture']): ?>
                                                <img src="<?= htmlspecialchars($commande['client_picture']) ?>" alt="Avatar" style="width: 32px; height: 32px; border-radius: 50%;">
                                            <?php endif; ?>
                                            <div>
                                                <div><?= htmlspecialchars($commande['client_name']) ?></div>
                                                <div style="font-size: 0.8rem; color: var(--text-secondary);"><?= htmlspecialchars($commande['client_email']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="font-weight: 600;"><?= number_format($commande['total'], 2, ',', ' ') ?> €</td>
                                    <td>
                                        <?php
                                        $badgeClass = [
                                            'en_attente' => 'badge-warning',
                                            'confirmee' => 'badge-info',
                                            'expediee' => 'badge-success',
                                            'livree' => 'badge-success',
                                            'annulee' => 'badge-danger'
                                        ][$commande['status']] ?? 'badge-info';

                                        $statusLabel = [
                                            'en_attente' => 'En attente',
                                            'confirmee' => 'Confirmée',
                                            'expediee' => 'Expédiée',
                                            'livree' => 'Livrée',
                                            'annulee' => 'Annulée'
                                        ][$commande['status']] ?? $commande['status'];
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($commande['created_at'])) ?></td>
                                    <td>
                                        <button class="btn-icon" onclick='viewOrder(<?= json_encode($commande) ?>)' title="Voir détails">👁️</button>
                                        <button class="btn-icon" onclick='changeStatus(<?= $commande['id'] ?>, "<?= $commande['status'] ?>")' title="Changer statut">🔄</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal Détails Commande -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Détails de la commande</h2>
                <button class="modal-close" onclick="closeOrderModal()">×</button>
            </div>
            <div id="orderDetails"></div>
        </div>
    </div>

    <!-- Modal Changer Statut -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Changer le statut</h2>
                <button class="modal-close" onclick="closeStatusModal()">×</button>
            </div>
            <form id="statusForm">
                <input type="hidden" id="orderId">

                <div class="form-group">
                    <label>Nouveau statut</label>
                    <select id="newStatus" class="form-control">
                        <option value="en_attente">En attente</option>
                        <option value="confirmee">Confirmée</option>
                        <option value="expediee">Expédiée</option>
                        <option value="livree">Livrée</option>
                        <option value="annulee">Annulée</option>
                    </select>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="closeStatusModal()" style="background-color: var(--bg-secondary); color: var(--text-primary);">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <span id="submitBtnText">Enregistrer</span>
                        <span id="submitBtnLoader" class="loading" style="display: none;"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/commandes.js"></script>
</body>
</html>
