<?php
/**
 * Gestion des Codes Promo
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
    <title>Codes Promo - CRIMP. Admin</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>CRIMP. Admin</h2>
                <div class="user-info">
                    <?php if (!empty($user['picture'])): ?>
                        <img src="<?= htmlspecialchars($user['picture']) ?>" alt="Avatar" class="user-avatar">
                    <?php endif; ?>
                    <span class="user-name"><?= htmlspecialchars($user['name']) ?></span>
                </div>
            </div>
            <nav>
                <ul class="sidebar-nav">
                    <li><a href="index.php"><span class="icon">📊</span> Dashboard</a></li>
                    <li><a href="statistiques.php"><span class="icon">📈</span> Statistiques</a></li>
                    <li><a href="produits.php"><span class="icon">📦</span> Produits</a></li>
                    <li><a href="commandes.php"><span class="icon">🛒</span> Commandes</a></li>
                    <li><a href="messages.php"><span class="icon">✉️</span> Messages</a></li>
                    <li><a href="newsletter.php"><span class="icon">📧</span> Newsletter</a></li>
                    <li><a href="clients.php"><span class="icon">👥</span> Clients</a></li>
                    <li><a href="promo-codes.php" class="active"><span class="icon">🏷️</span> Codes Promo</a></li>
                    <li><a href="maintenance.php"><span class="icon">🔧</span> Maintenance</a></li>
                    <li><a href="../index.html"><span class="icon">🏠</span> Retour au site</a></li>
                    <li><a href="../api/auth/logout.php"><span class="icon">🚪</span> Deconnexion</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>Codes Promo</h1>
                <p>Gerez vos bons de reduction et codes promotionnels</p>
            </div>

            <div id="alert-container"></div>

            <!-- Create Form -->
            <div class="table-container" style="margin-bottom: 2rem;">
                <div class="table-header">
                    <h2 id="formTitle">Creer un code promo</h2>
                </div>
                <div style="padding: 2rem;">
                    <form id="promoForm">
                        <input type="hidden" id="promoId" value="">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="promoCode">Code *</label>
                                <input type="text" id="promoCode" class="form-control" required placeholder="ex: WELCOME10" style="text-transform: uppercase;">
                            </div>
                            <div class="form-group">
                                <label for="promoDescription">Description</label>
                                <input type="text" id="promoDescription" class="form-control" placeholder="ex: 10% pour les nouveaux clients">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="promoType">Type de reduction *</label>
                                <select id="promoType" class="form-control">
                                    <option value="percent">Pourcentage (%)</option>
                                    <option value="fixed">Montant fixe (EUR)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="promoValue">Valeur *</label>
                                <input type="number" id="promoValue" class="form-control" required min="0.01" step="0.01" placeholder="10">
                            </div>
                            <div class="form-group">
                                <label for="promoMinOrder">Commande minimum (EUR)</label>
                                <input type="number" id="promoMinOrder" class="form-control" min="0" step="0.01" value="0" placeholder="0">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="promoMaxUses">Utilisations max</label>
                                <input type="number" id="promoMaxUses" class="form-control" min="1" placeholder="Illimite">
                            </div>
                            <div class="form-group">
                                <label for="promoStartsAt">Date de debut</label>
                                <input type="datetime-local" id="promoStartsAt" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="promoExpiresAt">Date d'expiration</label>
                                <input type="datetime-local" id="promoExpiresAt" class="form-control">
                            </div>
                        </div>
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <button type="submit" class="btn btn-primary" id="submitBtn">Creer le code</button>
                            <button type="button" class="btn" id="cancelBtn" style="display: none;" onclick="resetForm()">Annuler</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Codes Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Codes existants</h2>
                </div>
                <div id="codesContainer">
                    <div style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                        <div class="loading"></div>
                        Chargement...
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="js/promo-codes.js"></script>
</body>
</html>
