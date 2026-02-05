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

try {
    // 1. Visites par jour (pour le graphique)
    $visitesParJour = $db->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as visites,
            COUNT(DISTINCT session_id) as sessions
        FROM visites
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :periode DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $visitesParJour->execute([':periode' => $periode]);
    $graphData = $visitesParJour->fetchAll(PDO::FETCH_ASSOC);

    // 2. Statistiques globales
    $today = $db->query("
        SELECT COUNT(*) as count, COUNT(DISTINCT session_id) as sessions
        FROM visites 
        WHERE DATE(created_at) = CURDATE()
    ")->fetch();

    $yesterday = $db->query("
        SELECT COUNT(*) as count
        FROM visites 
        WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
    ")->fetch();

    $thisWeek = $db->query("
        SELECT COUNT(*) as count, COUNT(DISTINCT session_id) as sessions
        FROM visites 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ")->fetch();

    $thisMonth = $db->query("
        SELECT COUNT(*) as count, COUNT(DISTINCT session_id) as sessions
        FROM visites 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ")->fetch();

    $allTime = $db->query("
        SELECT COUNT(*) as count, COUNT(DISTINCT session_id) as sessions
        FROM visites
    ")->fetch();

    // 3. Top pages
    $topPages = $db->prepare("
        SELECT 
            page,
            COUNT(*) as visites
        FROM visites
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :periode DAY)
        GROUP BY page
        ORDER BY visites DESC
        LIMIT 10
    ");
    $topPages->execute([':periode' => $periode]);

    // 4. Répartition par device
    $devices = $db->prepare("
        SELECT 
            device_type,
            COUNT(*) as count
        FROM visites
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :periode DAY)
        GROUP BY device_type
    ");
    $devices->execute([':periode' => $periode]);

    // 5. Répartition par navigateur
    $browsers = $db->prepare("
        SELECT 
            browser,
            COUNT(*) as count
        FROM visites
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :periode DAY)
        GROUP BY browser
        ORDER BY count DESC
        LIMIT 5
    ");
    $browsers->execute([':periode' => $periode]);

    // 6. Répartition par OS
    $osList = $db->prepare("
        SELECT 
            os,
            COUNT(*) as count
        FROM visites
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :periode DAY)
        GROUP BY os
        ORDER BY count DESC
        LIMIT 5
    ");
    $osList->execute([':periode' => $periode]);

    // 7. Top referrers
    $referrers = $db->prepare("
        SELECT 
            CASE 
                WHEN referer = '' OR referer IS NULL THEN 'Direct'
                ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(referer, '/', 3), '//', -1)
            END as source,
            COUNT(*) as count
        FROM visites
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :periode DAY)
        GROUP BY source
        ORDER BY count DESC
        LIMIT 10
    ");
    $referrers->execute([':periode' => $periode]);

    // 8. Heures de pointe
    $heures = $db->prepare("
        SELECT 
            HOUR(created_at) as heure,
            COUNT(*) as count
        FROM visites
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :periode DAY)
        GROUP BY heure
        ORDER BY heure
    ");
    $heures->execute([':periode' => $periode]);

    // 9. Dernières visites
    $dernieresVisites = $db->query("
        SELECT page, device_type, browser, os, created_at
        FROM visites
        ORDER BY created_at DESC
        LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Calculer la tendance (vs période précédente)
    $prevPeriod = $db->prepare("
        SELECT COUNT(*) as count
        FROM visites 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :periode2 DAY)
          AND created_at < DATE_SUB(CURDATE(), INTERVAL :periode DAY)
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
