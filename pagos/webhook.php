<?php
/**
 * Webhook / IPN — recibe notificaciones automáticas de las pasarelas
 * Este endpoint debe ser público y accesible por HTTPS
 * URL: https://tudominio.cl/pagos/webhook.php
 */

require_once __DIR__ . '/FlowPayment.php';
require_once __DIR__ . '/MercadoPagoPayment.php';
require_once __DIR__ . '/WebpayPayment.php';

$config = require __DIR__ . '/config.php';

// Leer body del request
$body    = file_get_contents('php://input');
$payload = json_decode($body, true) ?? [];

// Detectar de qué pasarela viene la notificación
$gateway = $_GET['gateway'] ?? detectGateway($payload);

$result = null;

try {
    switch ($gateway) {
        case 'flow':
            $payment = new FlowPayment(array_merge($config['flow'], [
                'return_url'  => $config['return_url'],
                'webhook_url' => $config['webhook_url'],
            ]));
            $token  = $_POST['token'] ?? $payload['token'] ?? '';
            $result = $payment->verifyPayment(['token' => $token]);
            break;

        case 'mercadopago':
            $payment = new MercadoPagoPayment(array_merge($config['mercadopago'], [
                'return_url'  => $config['return_url'],
                'webhook_url' => $config['webhook_url'],
            ]));
            $result = $payment->handleWebhook($payload);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Pasarela no identificada']);
            exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// ─── Aquí conectas con tu base de datos ──────────────────────────────────────
if ($result['success'] && $result['paid']) {
    // TODO: actualizar estado de la reserva en la BD
    // updateReservation($result['order_id'], 'pagado');
    // sendConfirmationEmail($result['order_id']);
    error_log("[Webhook] Pago confirmado - Orden: " . ($result['order_id'] ?? 'N/A'));
} else {
    error_log("[Webhook] Pago NO confirmado - Orden: " . ($result['order_id'] ?? 'N/A'));
}

http_response_code(200);
echo json_encode(['received' => true]);

// ─── Helper ──────────────────────────────────────────────────────────────────
function detectGateway(array $payload): string
{
    // MercadoPago envía 'type' en el payload
    if (isset($payload['type']) && isset($payload['data']['id'])) {
        return 'mercadopago';
    }
    // Flow envía 'token'
    if (isset($_POST['token']) || isset($payload['token'])) {
        return 'flow';
    }
    return '';
}
