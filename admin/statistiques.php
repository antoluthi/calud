<?php
/**
 * Page Admin - Statistiques
 * Dashboard analytics avec graphiques et métriques
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
    <title>Statistiques - CRIMP. Admin</title>
    <link rel="stylesheet" href="css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        /* Stats-specific styles */
        .stats-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .periode-selector {
            display: flex;
            gap: 0.5rem;
        }

        .periode-btn {
            padding: 0.5rem 1rem;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }

        .periode-btn:hover {
            border-color: var(--accent);
            color: var(--text-primary);
        }

        .periode-btn.active {
            background-color: var(--accent);
            color: var(--bg-primary);
            border-color: var(--accent);
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
        }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .stat-card-title {
            font-size: 0.8rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card-icon {
            font-size: 1.3rem;
        }

        .stat-card-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .stat-card-change {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.8rem;
            margin-top: 0.5rem;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
        }

        .stat-card-change.positive {
            background-color: rgba(0, 255, 136, 0.1);
            color: var(--success);
        }

        .stat-card-change.negative {
            background-color: rgba(255, 68, 68, 0.1);
            color: var(--danger);
        }

        .stat-card-change.neutral {
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--text-secondary);
        }

        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .chart-container {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
        }

        .chart-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .chart-wrapper {
            position: relative;
            height: 300px;
        }

        .small-charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .small-chart-wrapper {
            position: relative;
            height: 200px;
        }

        .stats-list {
            list-style: none;
        }

        .stats-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .stats-list-item:last-child {
            border-bottom: none;
        }

        .stats-list-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            max-width: 70%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .stats-list-value {
            font-weight: 600;
            color: var(--text-primary);
        }

        .recent-visits-table {
            width: 100%;
            font-size: 0.85rem;
        }

        .recent-visits-table th {
            padding: 0.75rem;
            font-size: 0.75rem;
        }

        .recent-visits-table td {
            padding: 0.75rem;
        }

        .device-icon {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--border-color);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 1024px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .small-charts-grid {
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
                    <li><a href="statistiques.php" class="active"><span class="icon">📈</span> Statistiques</a></li>
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
            <div class="stats-header">
                <div class="page-header" style="margin-bottom: 0;">
                    <h1>📈 Statistiques</h1>
                    <p>Analyse du trafic et des performances de votre site</p>
                </div>
                <div class="periode-selector">
                    <button class="periode-btn" data-periode="7">7 jours</button>
                    <button class="periode-btn active" data-periode="30">30 jours</button>
                    <button class="periode-btn" data-periode="90">90 jours</button>
                    <button class="periode-btn" data-periode="365">1 an</button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-card-title">Aujourd'hui</span>
                        <span class="stat-card-icon">📅</span>
                    </div>
                    <div class="stat-card-value" id="visites-today">-</div>
                    <span class="stat-card-change neutral" id="sessions-today">- sessions</span>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-card-title">Cette semaine</span>
                        <span class="stat-card-icon">📆</span>
                    </div>
                    <div class="stat-card-value" id="visites-week">-</div>
                    <span class="stat-card-change neutral" id="sessions-week">- sessions</span>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-card-title">Ce mois</span>
                        <span class="stat-card-icon">🗓️</span>
                    </div>
                    <div class="stat-card-value" id="visites-month">-</div>
                    <span class="stat-card-change neutral" id="sessions-month">- sessions</span>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-card-title">Total</span>
                        <span class="stat-card-icon">🌟</span>
                    </div>
                    <div class="stat-card-value" id="visites-total">-</div>
                    <span class="stat-card-change neutral" id="tendance">- tendance</span>
                </div>
            </div>

            <!-- Main Charts -->
            <div class="charts-grid">
                <div class="chart-container">
                    <h3 class="chart-title">📊 Visites par jour</h3>
                    <div class="chart-wrapper">
                        <canvas id="visitesChart"></canvas>
                    </div>
                </div>
                <div class="chart-container">
                    <h3 class="chart-title">📱 Appareils</h3>
                    <div class="chart-wrapper">
                        <canvas id="devicesChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Small Charts -->
            <div class="small-charts-grid">
                <div class="chart-container">
                    <h3 class="chart-title">🔝 Pages les plus visitées</h3>
                    <ul class="stats-list" id="top-pages">
                        <li class="stats-list-item"><span class="stats-list-label">Chargement...</span></li>
                    </ul>
                </div>
                <div class="chart-container">
                    <h3 class="chart-title">🌐 Navigateurs</h3>
                    <div class="small-chart-wrapper">
                        <canvas id="browsersChart"></canvas>
                    </div>
                </div>
                <div class="chart-container">
                    <h3 class="chart-title">💻 Systèmes d'exploitation</h3>
                    <div class="small-chart-wrapper">
                        <canvas id="osChart"></canvas>
                    </div>
                </div>
                <div class="chart-container">
                    <h3 class="chart-title">🔗 Sources de trafic</h3>
                    <ul class="stats-list" id="referrers">
                        <li class="stats-list-item"><span class="stats-list-label">Chargement...</span></li>
                    </ul>
                </div>
            </div>

            <!-- Hours Chart -->
            <div class="chart-container" style="margin-bottom: 1.5rem;">
                <h3 class="chart-title">⏰ Heures de pointe</h3>
                <div class="chart-wrapper" style="height: 200px;">
                    <canvas id="hoursChart"></canvas>
                </div>
            </div>

            <!-- Recent Visits -->
            <div class="table-container">
                <div class="table-header">
                    <h2>🕐 Dernières visites</h2>
                </div>
                <table class="recent-visits-table">
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>Appareil</th>
                            <th>Navigateur</th>
                            <th>OS</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody id="recent-visits">
                        <tr><td colspan="5" style="text-align: center; padding: 2rem;">Chargement...</td></tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div class="loading-overlay" id="loading" style="display: none;">
        <div class="loading-spinner"></div>
    </div>

    <script src="js/statistiques.js"></script>
</body>
</html>
