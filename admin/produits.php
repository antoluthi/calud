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

// Décoder le JSON des caractéristiques, tailles et images pour chaque produit
foreach ($produits as &$produit) {
    if ($produit['caracteristiques']) {
        $produit['caracteristiques'] = json_decode($produit['caracteristiques']);
    }
    if ($produit['tailles']) {
        $produit['tailles'] = json_decode($produit['tailles']);
    }
    if (isset($produit['images']) && $produit['images']) {
        $produit['images'] = json_decode($produit['images']);
    }
}
unset($produit); // Casser la référence
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Produits - PRJ CRIMP. Admin</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>PRJ CRIMP. Admin</h2>
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
                    <li><a href="messages.php"><span class="icon">✉️</span> Messages</a></li>
                    <li><a href="newsletter.php"><span class="icon">📧</span> Newsletter</a></li>
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
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="btn" onclick="checkDuplicates()" style="background-color: var(--bg-secondary); color: var(--text-primary);">🔍 Vérifier doublons</button>
                        <button class="btn btn-primary" onclick="openProductModal()">➕ Nouveau Produit</button>
                    </div>
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
                                        <button class="btn-icon" onclick='editProduct(<?= htmlspecialchars(json_encode($produit), ENT_QUOTES) ?>)' title="Modifier">✏️</button>
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
                    <label for="tailles">Tailles disponibles (une par ligne)</label>
                    <textarea id="tailles" name="tailles" class="form-control" placeholder="S (15mm)&#10;M (20mm)&#10;L (25mm)&#10;XL (30mm)"></textarea>
                    <small style="color: var(--text-secondary); font-size: 0.85rem;">Exemples: S (15mm), M (20mm), L (25mm), XL (30mm)</small>
                </div>

                <hr style="border-color: var(--border-color); margin: 1.5rem 0;">
                <h4 style="color: var(--accent); margin-bottom: 1rem;">Informations detaillees (Modal)</h4>

                <div class="form-group">
                    <label for="images">Images supplementaires (une URL par ligne)</label>
                    <textarea id="images" name="images" class="form-control" placeholder="images/produit-1.jpg&#10;images/produit-2.jpg&#10;images/produit-3.jpg"></textarea>
                    <small style="color: var(--text-secondary); font-size: 0.85rem;">Ces images apparaitront dans la galerie de la modal produit</small>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="dimensions">Dimensions</label>
                        <input type="text" id="dimensions" name="dimensions" class="form-control" placeholder="20 x 15 x 3 cm">
                    </div>
                    <div class="form-group">
                        <label for="poids">Poids</label>
                        <input type="text" id="poids" name="poids" class="form-control" placeholder="850g">
                    </div>
                </div>

                <div class="form-group">
                    <label for="materiaux">Materiaux</label>
                    <textarea id="materiaux" name="materiaux" class="form-control" placeholder="Bois de hetre, resine epoxy..."></textarea>
                </div>

                <div class="form-group">
                    <label for="guide_tailles">Guide des tailles</label>
                    <textarea id="guide_tailles" name="guide_tailles" class="form-control" placeholder="S: convient aux mains de moins de 17cm&#10;M: convient aux mains de 17-19cm&#10;L: convient aux mains de 19-21cm&#10;XL: convient aux mains de plus de 21cm"></textarea>
                </div>

                <div class="form-group">
                    <label for="video_url">URL Video YouTube (optionnel)</label>
                    <input type="text" id="video_url" name="video_url" class="form-control" placeholder="https://www.youtube.com/watch?v=...">
                    <small style="color: var(--text-secondary); font-size: 0.85rem;">Coller l'URL complete de la video YouTube</small>
                </div>

                <div class="form-group">
                    <label for="guide_pdf">Guide d'utilisation PDF (optionnel)</label>
                    <input type="text" id="guide_pdf" name="guide_pdf" class="form-control" placeholder="guides/mon-guide.pdf">
                    <small style="color: var(--text-secondary); font-size: 0.85rem;">Chemin vers le fichier PDF du guide d'utilisation (ex: guides/poutre-pro.pdf)</small>
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
