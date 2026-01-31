<?php
/**
 * API Checkout - Enregistre les commandes
 */

// Capturer tout output
ob_start();

// Désactiver l'affichage des erreurs PHP
ini_set('display_errors', 0);
ini_set('html_errors', 0);
error_reporting(0);

header('Content-Type: application/json');

// Fonction pour retourner une erreur JSON proprement
function returnError($message) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

// Fonction pour retourner un succès
function returnSuccess($data) {
    ob_end_clean();
    echo json_encode($data);
    exit;
}

try {
    require_once 'config.php';
    // Re-désactiver les erreurs (config.php les réactive)
    ini_set('display_errors', 0);
    ini_set('html_errors', 0);
    error_reporting(0);
} catch (Throwable $e) {
    returnError('Config error: ' . $e->getMessage());
}

// Récupérer l'utilisateur connecté (null pour guest checkout)
$userId = $_SESSION['user_id'] ?? null;

// Envoyer l'email de confirmation de commande
function sendConfirmationEmail($orderId, $data) {
    $domain = $_SERVER['HTTP_HOST'] ?? 'antonin.luthi.eu';

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: PROJET CRIMP. <noreply@" . $domain . ">\r\n";
    $headers .= "Reply-To: contact@" . $domain . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $subject = "Confirmation de commande " . $orderId;

    // Construire les lignes d'articles
    $itemsRows = '';
    foreach ($data['items'] as $item) {
        $lineTotal = number_format($item['price'] * $item['quantity'], 2, ',', ' ');
        $unitPrice = number_format($item['price'], 2, ',', ' ');
        $itemsRows .= '
        <tr>
            <td style="padding: 12px 16px; border-bottom: 1px solid #2a2a2a; color: #ffffff; font-size: 14px;">
                ' . htmlspecialchars($item['name']) . '
                <br><span style="color: #888888; font-size: 12px;">Taille: ' . htmlspecialchars($item['size']) . '</span>
            </td>
            <td style="padding: 12px 16px; border-bottom: 1px solid #2a2a2a; color: #888888; text-align: center; font-size: 14px;">' . (int)$item['quantity'] . '</td>
            <td style="padding: 12px 16px; border-bottom: 1px solid #2a2a2a; color: #888888; text-align: center; font-size: 14px;">' . $unitPrice . ' &euro;</td>
            <td style="padding: 12px 16px; border-bottom: 1px solid #2a2a2a; color: #ffffff; text-align: right; font-size: 14px; font-weight: 600;">' . $lineTotal . ' &euro;</td>
        </tr>';
    }

    $subtotal = number_format($data['subtotal'], 2, ',', ' ');
    $shipping = $data['shipping'] == 0 ? 'Gratuite' : number_format($data['shipping'], 2, ',', ' ') . ' &euro;';
    $total = number_format($data['total'], 2, ',', ' ');

    $htmlMessage = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="utf-8"></head>
    <body style="margin: 0; padding: 0; background-color: #0a0a0a; font-family: Arial, Helvetica, sans-serif;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #0a0a0a; padding: 20px 0;">
            <tr><td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">

                    <!-- Header -->
                    <tr><td style="padding: 30px 24px; text-align: center;">
                        <h1 style="margin: 0; color: #ffffff; font-size: 20px; letter-spacing: 3px; text-transform: uppercase;">PROJET CRIMP.</h1>
                    </td></tr>

                    <!-- Confirmation -->
                    <tr><td style="padding: 0 24px 24px;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #181818; border-radius: 12px; border: 1px solid #2a2a2a;">
                            <tr><td style="padding: 24px; text-align: center;">
                                <div style="color: #4ade80; font-size: 28px; margin-bottom: 12px;">&#10003;</div>
                                <h2 style="margin: 0 0 8px; color: #ffffff; font-size: 18px;">Commande confirmee</h2>
                                <p style="margin: 0; color: #888888; font-size: 14px;">Numero: <strong style="color: #ffffff;">' . htmlspecialchars($orderId) . '</strong></p>
                            </td></tr>
                        </table>
                    </td></tr>

                    <!-- Articles -->
                    <tr><td style="padding: 0 24px 24px;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #181818; border-radius: 12px; border: 1px solid #2a2a2a;">
                            <tr><td style="padding: 20px 0 0;">
                                <h3 style="margin: 0 16px 12px; color: #ffffff; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;">Articles commandes</h3>
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <th style="padding: 8px 16px; text-align: left; color: #888888; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #2a2a2a;">Produit</th>
                                        <th style="padding: 8px 16px; text-align: center; color: #888888; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #2a2a2a;">Qte</th>
                                        <th style="padding: 8px 16px; text-align: center; color: #888888; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #2a2a2a;">Prix</th>
                                        <th style="padding: 8px 16px; text-align: right; color: #888888; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #2a2a2a;">Total</th>
                                    </tr>
                                    ' . $itemsRows . '
                                </table>
                            </td></tr>
                            <tr><td style="padding: 16px;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="padding: 4px 0; color: #888888; font-size: 14px;">Sous-total</td>
                                        <td style="padding: 4px 0; color: #ffffff; font-size: 14px; text-align: right;">' . $subtotal . ' &euro;</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 4px 0; color: #888888; font-size: 14px;">Livraison</td>
                                        <td style="padding: 4px 0; color: #ffffff; font-size: 14px; text-align: right;">' . $shipping . '</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px 0 4px; color: #ffffff; font-size: 16px; font-weight: 700; border-top: 1px solid #2a2a2a;">Total</td>
                                        <td style="padding: 12px 0 4px; color: #ffffff; font-size: 16px; font-weight: 700; text-align: right; border-top: 1px solid #2a2a2a;">' . $total . ' &euro;</td>
                                    </tr>
                                </table>
                            </td></tr>
                        </table>
                    </td></tr>

                    <!-- Paiement -->
                    <tr><td style="padding: 0 24px 24px;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #181818; border-radius: 12px; border: 1px solid #2a2a2a;">
                            <tr><td style="padding: 24px;">
                                <h3 style="margin: 0 0 16px; color: #ffffff; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;">Informations de paiement</h3>
                                <p style="margin: 0 0 12px; color: #888888; font-size: 14px;">Veuillez effectuer un virement bancaire avec les informations suivantes :</p>
                                <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #111111; border-radius: 8px;">
                                    <tr><td style="padding: 16px;">
                                        <p style="margin: 0 0 8px; color: #888888; font-size: 13px;">IBAN</p>
                                        <p style="margin: 0 0 16px; color: #ffffff; font-size: 15px; font-weight: 600; letter-spacing: 1px;">BE65 0018 1297 8496</p>
                                        <p style="margin: 0 0 8px; color: #888888; font-size: 13px;">BIC</p>
                                        <p style="margin: 0 0 16px; color: #ffffff; font-size: 15px; font-weight: 600;">GEBABEBB</p>
                                        <p style="margin: 0 0 8px; color: #888888; font-size: 13px;">Communication</p>
                                        <p style="margin: 0; color: #4ade80; font-size: 15px; font-weight: 600;">' . htmlspecialchars($orderId) . '</p>
                                    </td></tr>
                                </table>
                            </td></tr>
                        </table>
                    </td></tr>

                    <!-- Adresse de livraison -->
                    <tr><td style="padding: 0 24px 24px;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #181818; border-radius: 12px; border: 1px solid #2a2a2a;">
                            <tr><td style="padding: 24px;">
                                <h3 style="margin: 0 0 12px; color: #ffffff; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;">Adresse de livraison</h3>
                                <p style="margin: 0; color: #cccccc; font-size: 14px; line-height: 1.6;">
                                    ' . htmlspecialchars($data['firstName'] . ' ' . $data['lastName']) . '<br>
                                    ' . htmlspecialchars($data['address']) . '<br>
                                    ' . (!empty($data['address2']) ? htmlspecialchars($data['address2']) . '<br>' : '') . '
                                    ' . htmlspecialchars($data['postalCode'] . ' ' . $data['city']) . '<br>
                                    ' . htmlspecialchars($data['country']) . '
                                </p>
                            </td></tr>
                        </table>
                    </td></tr>

                    <!-- Footer -->
                    <tr><td style="padding: 24px; text-align: center;">
                        <p style="margin: 0; color: #888888; font-size: 12px;">Votre commande sera expediee des reception du paiement.</p>
                        <p style="margin: 8px 0 0; color: #555555; font-size: 11px;">PROJET CRIMP. - Train Anywhere.</p>
                    </td></tr>

                </table>
            </td></tr>
        </table>
    </body>
    </html>';

    return mail($data['email'], $subject, $htmlMessage, $headers);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    returnError('Method not allowed');
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    returnError('Invalid data');
}

// Validate required fields
$required = ['email', 'firstName', 'lastName', 'address', 'postalCode', 'city', 'country', 'items', 'total'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        returnError("Missing field: $field");
    }
}

try {
    $pdo = getDB();

    // Generate order ID
    $orderId = 'PC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

    // Insert order
    $stmt = $pdo->prepare("
        INSERT INTO commandes (
            order_id, user_id, email, phone, first_name, last_name,
            address, address2, postal_code, city, country,
            payment_method, subtotal, shipping, total, status, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, 'pending', NOW()
        )
    ");

    $stmt->execute([
        $orderId,
        $userId,
        $data['email'],
        $data['phone'] ?? '',
        $data['firstName'],
        $data['lastName'],
        $data['address'],
        $data['address2'] ?? '',
        $data['postalCode'],
        $data['city'],
        $data['country'],
        $data['paymentMethod'],
        $data['subtotal'],
        $data['shipping'],
        $data['total']
    ]);

    $commandeId = $pdo->lastInsertId();

    // Insert order items
    $stmtItem = $pdo->prepare("
        INSERT INTO commande_items (commande_id, product_name, product_size, quantity, price)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($data['items'] as $item) {
        $stmtItem->execute([
            $commandeId,
            $item['name'],
            $item['size'],
            $item['quantity'],
            $item['price']
        ]);
    }

    // Envoyer l'email de confirmation (ne bloque pas la commande en cas d'échec)
    try {
        sendConfirmationEmail($orderId, $data);
    } catch (Exception $emailError) {
        error_log("Email confirmation error for order $orderId: " . $emailError->getMessage());
    }

    returnSuccess([
        'success' => true,
        'orderId' => $orderId,
        'message' => 'Commande enregistrée avec succès'
    ]);

} catch (PDOException $e) {
    error_log("Checkout error: " . $e->getMessage());
    http_response_code(500);
    returnError('Erreur base de données: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("Checkout error: " . $e->getMessage());
    http_response_code(500);
    returnError('Erreur: ' . $e->getMessage());
}
