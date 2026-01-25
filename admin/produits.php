<?php
/**
 * Gestion des Produits
 */

require_once '../api/config.php';

// Vérifier que l'utilisateur est admin
if (!isAdmin()) {
    header('Location: ../index.html');
    exit;
}

$user = getCurrentUser();
$db = getDB();

// Récupérer tous les produits
$produitsQuery = $db->query("
    SELECT *
    FROM produits
    ORDER BY created_at DESC
");
$produits = $produitsQuery->fetchAll();

// Décoder le JSON des caractéristiques pour chaque produit
foreach ($produits as &$produit) {
    if ($produit['caracteristiques']) {
        $produit['caracteristiques'] = json_decode($produit['caracteristiques']);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Produits - AL Admin</title>
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
                    <li><a href="produits.php" class="active"><span class="icon">📦</span> Produits</a></li>
                    <li><a href="commandes.php"><span class="icon">🛒</span> Commandes</a></li>
                    <li><a href="clients.php"><span class="icon">👥</span> Clients</a></li>
                    <li><a href="../index.html"><span class="icon">🏠</span> Retour au site</a></li>
                    <li><a href="../api/auth/logout.php"><span class="icon">🚪</span> Déconnexion</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>Gestion des Produits</h1>
                <p>Gérez votre catalogue de produits</p>
            </div>

            <div id="alert-container"></div>

            <!-- Products Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Liste des Produits</h2>
                    <button class="btn btn-primary" onclick="openProductModal()">➕ Nouveau Produit</button>
                </div>
                <?php if (empty($produits)): ?>
                    <div style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                        Aucun produit. Cliquez sur "Nouveau Produit" pour commencer.
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Prix</th>
                                <th>Statut</th>
                                <th>Date de création</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produits as $produit): ?>
                                <tr>
                                    <td>#<?= $produit['id'] ?></td>
                                    <td><?= htmlspecialchars($produit['nom']) ?></td>
                                    <td><?= number_format($produit['prix'], 2, ',', ' ') ?> €</td>
                                    <td>
                                        <span class="badge <?= $produit['actif'] ? 'badge-success' : 'badge-danger' ?>">
                                            <?= $produit['actif'] ? 'Actif' : 'Inactif' ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($produit['created_at'])) ?></td>
                                    <td>
                                        <button class="btn-icon" onclick='editProduct(<?= json_encode($produit) ?>)' title="Modifier">✏️</button>
                                        <button class="btn-icon" onclick="toggleProductStatus(<?= $produit['id'] ?>, <?= $produit['actif'] ? 'false' : 'true' ?>)" title="<?= $produit['actif'] ? 'Désactiver' : 'Activer' ?>">
                                            <?= $produit['actif'] ? '🔴' : '🟢' ?>
                                        </button>
                                        <button class="btn-icon" onclick="deleteProduct(<?= $produit['id'] ?>, '<?= htmlspecialchars($produit['nom'], ENT_QUOTES) ?>')" title="Supprimer">🗑️</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal Produit -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nouveau Produit</h2>
                <button class="modal-close" onclick="closeProductModal()">×</button>
            </div>
            <form id="productForm">
                <input type="hidden" id="productId" name="id">

                <div class="form-group">
                    <label for="nom">Nom du produit *</label>
                    <input type="text" id="nom" name="nom" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="prix">Prix (€) *</label>
                    <input type="number" id="prix" name="prix" class="form-control" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control"></textarea>
                </div>

                <div class="form-group">
                    <label for="image">URL de l'image</label>
                    <input type="text" id="image" name="image" class="form-control" placeholder="images/produit.jpg">
                </div>

                <div class="form-group">
                    <label for="caracteristiques">Caractéristiques (une par ligne)</label>
                    <textarea id="caracteristiques" name="caracteristiques" class="form-control" placeholder="✓ Caractéristique 1&#10;✓ Caractéristique 2"></textarea>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="actif" name="actif" checked>
                        Produit actif
                    </label>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="closeProductModal()" style="background-color: var(--bg-secondary); color: var(--text-primary);">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <span id="submitBtnText">Créer le produit</span>
                        <span id="submitBtnLoader" class="loading" style="display: none;"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/produits.js"></script>
</body>
</html>
