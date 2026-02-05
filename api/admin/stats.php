<?php
/**
 * API Admin - Statistiques de visite
 * Retourne les données analytics pour le dashboard admin
 */

require_once '../config.php';

// Vérifier que l'utilisateur est admin
if (!isAdmin()) {
    sendJSON(['error' => 'Accès non autorisé'], 403);
    exit;
}

$db = getDB();

// Paramètre période (par défaut 30 jours)
$periode = $_GET['periode'] ?? '30';
$periode = (int)min(max($periode, 7), 365); // Entre 7 et 365 jours

// Paramètre pour cacher le traffic admin
$hideAdmin = isset($_GET['hide_admin']) && $_GET['hide_admin'] === '1';

// Construire la clause WHERE pour filtrer le traffic admin
$adminFilter = '';
if ($hideAdmin) {
    $adminFilter = ' AND (v.user_id IS NULL OR v.user_id NOT IN (SELECT id FROM users WHERE is_admin = 1))';
}

try {
    // 1. Visites par jour (pour le graphique)
    $visitesParJour = $db->prepare("
        SELECT
            DATE(v.created_at) as date,
            COUNT(*) as visites,
            COUNT(DISTINCT v.session_id) as sessions
        FROM visites v
        WHERE v.created_at >= DATE_SUB(CURDATE(), INTERVAL :periode DAY)
        $adminFilter
        GROUP BY DATE(v.created_at)
        ORDER BY date ASC
    ");
    $visitesParJour->execute([':periode' => $periode]);
    $graphData = $visitesParJour->fetchAll(PDO::FETCH_ASSOC);

    // 2. Statistiques globales
    $today = $db->query("
        SELECT COUNT(*) as count, COUNT(DISTINCT v.session_id) as sessions
        FROM visites v
        WHERE DATE(v.created_at) = CURDATE()
        $adminFilter
    ")->fetch();

    $yesterday = $db->query("
        SELECT COUNT(*) as count
        FROM visites v
        WHERE DATE(v.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        $adminFilter
    ")->fetch();

    $thisWeek = $db->query("
        SELECT COUNT(*) as count, COUNT(DISTINCT v.session_id) as sessions
        FROM visites v
        WHERE v.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        $adminFilter
    ")->fetch();

    $thisMonth = $db->query("
        SELECT COUNT(*) as count, COUNT(DISTINCT v.session_id) as sessions
        FROM visites v
        WHERE v.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        $adminFilter
    ")->fetch();

    $allTime = $db->query("
        SELECT COUNT(*) as count, COUNT(DISTINCT v.session_id) as sessions
        FROM visites v
        WHERE 1=1
        $adminFilter
    ")->fetch();

    // 3. Top pages
    $topPages = $db->prepare("
        SELECT
            v.page,
            COUNT(*) as visites
        FROM visites v
        WHERE v.created_at >= DATE_SUB(CURDATE(), INTERVAL :periode DAY)
        $adminFilter
        GROUP BY v.page
        ORDER BY visites DESC
        LIMIT 10
    ");
    $topPages->execute([':periode' => $periode]);

    // 4. Répartition par device
    $devices = $db->prepare("
        SELECT
            v.device_type,
            COUNT(*) as count
        FROM visites v
        WHERE v.created_at >= DATE_SUB(CURDATE(), INTERVAL :periode DAY)
        $adminFilter
        GROUP BY v.device_type
    ");
    $devices->execute([':periode' => $periode]);

    // 5. Répartition par navigateur
    $browsers = $db->prepare("
        SELECT
            v.browser,
            COUNT(*) as count
        FROM visites v
        WHERE v.created_at >= DATE_SUB(CURDATE(), INTERVAL :periode DAY)
        $adminFilter
        GROUP BY v.browser
        ORDER BY count DESC
        LIMIT 5
    ");
    $browsers->execute([':periode' => $periode]);

    // 6. Répartition par OS
    $osList = $db->prepare("
        SELECT
            v.os,
            COUNT(*) as count
        FROM visites v
        WHERE v.created_at >= DATE_SUB(CURDATE(), INTERVAL :periode DAY)
        $adminFilter
        GROUP BY v.os
        ORDER BY count DESC
        LIMIT 5
    ");
    $osList->execute([':periode' => $periode]);

    // 7. Top referrers
    $referrers = $db->prepare("
        SELECT
            CASE
                WHEN v.referer = '' OR v.referer IS NULL THEN 'Direct'
                ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(v.referer, '/', 3), '//', -1)
            END as source,
            COUNT(*) as count
        FROM visites v
        WHERE v.created_at >= DATE_SUB(CURDATE(), INTERVAL :periode DAY)
        $adminFilter
        GROUP BY source
        ORDER BY count DESC
        LIMIT 10
    ");
    $referrers->execute([':periode' => $periode]);

    // 8. Heures de pointe
    $heures = $db->prepare("
        SELECT
            HOUR(v.created_at) as heure,
            COUNT(*) as count
        FROM visites v
        WHERE v.created_at >= DATE_SUB(CURDATE(), INTERVAL :periode DAY)
        $adminFilter
        GROUP BY heure
        ORDER BY heure
    ");
    $heures->execute([':periode' => $periode]);

    // 9. Dernières visites (avec info utilisateur)
    $dernieresVisites = $db->query("
        SELECT v.page, v.device_type, v.browser, v.os, v.created_at, v.user_id,
               u.name as user_name, u.email as user_email, u.is_admin as user_is_admin
        FROM visites v
        LEFT JOIN users u ON v.user_id = u.id
        WHERE 1=1
        $adminFilter
        ORDER BY v.created_at DESC
        LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Calculer la tendance (vs période précédente)
    $prevPeriod = $db->prepare("
        SELECT COUNT(*) as count
        FROM visites v
        WHERE v.created_at >= DATE_SUB(CURDATE(), INTERVAL :periode2 DAY)
          AND v.created_at < DATE_SUB(CURDATE(), INTERVAL :periode DAY)
        $adminFilter
    ");
    $prevPeriod->execute([':periode' => $periode, ':periode2' => $periode * 2]);
    $prevCount = $prevPeriod->fetch()['count'];
    
    $currentCount = $thisMonth['count'];
    $tendance = $prevCount > 0 ? round((($currentCount - $prevCount) / $prevCount) * 100, 1) : 0;

    sendJSON([
        'success' => true,
        'periode' => $periode,
        'summary' => [
            'today' => [
                'visites' => (int)$today['count'],
                'sessions' => (int)$today['sessions']
            ],
            'yesterday' => [
                'visites' => (int)$yesterday['count']
            ],
            'week' => [
                'visites' => (int)$thisWeek['count'],
                'sessions' => (int)$thisWeek['sessions']
            ],
            'month' => [
                'visites' => (int)$thisMonth['count'],
                'sessions' => (int)$thisMonth['sessions']
            ],
            'allTime' => [
                'visites' => (int)$allTime['count'],
                'sessions' => (int)$allTime['sessions']
            ],
            'tendance' => $tendance
        ],
        'graphData' => $graphData,
        'topPages' => $topPages->fetchAll(PDO::FETCH_ASSOC),
        'devices' => $devices->fetchAll(PDO::FETCH_ASSOC),
        'browsers' => $browsers->fetchAll(PDO::FETCH_ASSOC),
        'os' => $osList->fetchAll(PDO::FETCH_ASSOC),
        'referrers' => $referrers->fetchAll(PDO::FETCH_ASSOC),
        'heures' => $heures->fetchAll(PDO::FETCH_ASSOC),
        'dernieresVisites' => $dernieresVisites
    ]);

} catch (PDOException $e) {
    error_log("Erreur stats: " . $e->getMessage());
    sendJSON(['error' => 'Erreur lors de la récupération des statistiques'], 500);
}
