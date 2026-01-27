<?php
/**
 * API Admin - Vérification et correction des doublons
 */

require_once '../config.php';

// Vérifier que l'utilisateur est admin
requireAdmin();

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// GET - Lister les doublons
if ($method === 'GET') {
    try {
        // Trouver les produits avec le même nom
        $stmt = $db->query("
            SELECT nom, COUNT(*) as count, GROUP_CONCAT(id ORDER BY created_at DESC) as ids
            FROM produits
            GROUP BY nom
            HAVING COUNT(*) > 1
        ");
        $duplicates = $stmt->fetchAll();

        // Pour chaque groupe de doublons, récupérer les détails
        $duplicateDetails = [];
        foreach ($duplicates as $dup) {
            $ids = explode(',', $dup['ids']);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            $detailStmt = $db->prepare("
                SELECT id, nom, prix, actif, created_at, updated_at
                FROM produits
                WHERE id IN ($placeholders)
                ORDER BY created_at DESC
            ");
            $detailStmt->execute($ids);
            $details = $detailStmt->fetchAll();

            $duplicateDetails[] = [
                'nom' => $dup['nom'],
                'count' => (int)$dup['count'],
                'produits' => $details
            ];
        }

        sendJSON([
            'success' => true,
            'duplicates_found' => count($duplicates),
            'duplicates' => $duplicateDetails
        ]);

    } catch (PDOException $e) {
        error_log("Erreur vérification doublons: " . $e->getMessage());
        sendJSON(['error' => 'Erreur lors de la vérification'], 500);
    }
}

// DELETE - Supprimer les doublons (garder le plus récent)
if ($method === 'DELETE') {
    try {
        // Supprimer les doublons en gardant celui avec l'ID le plus élevé (le plus récent)
        $stmt = $db->prepare("
            DELETE p1 FROM produits p1
            INNER JOIN produits p2
            WHERE p1.id < p2.id
            AND p1.nom = p2.nom
        ");
        $stmt->execute();

        $deletedCount = $stmt->rowCount();

        sendJSON([
            'success' => true,
            'message' => "Doublons supprimés avec succès",
            'deleted_count' => $deletedCount
        ]);

    } catch (PDOException $e) {
        error_log("Erreur suppression doublons: " . $e->getMessage());
        sendJSON(['error' => 'Erreur lors de la suppression'], 500);
    }
}
