<?php
/**
 * Página de retorno — el cliente llega aquí después de pagar
 * Confirma el pago con la pasarela y muestra el resultado
 */

session_start();

require_once __DIR__ . '/FlowPayment.php';
require_once __DIR__ . '/MercadoPagoPayment.php';
require_once __DIR__ . '/WebpayPayment.php';

$config  = require __DIR__ . '/config.php';
$gateway = $_REQUEST['gateway'] ?? $_SESSION['pending_payment']['gateway'] ?? '';
$result  = null;

try {
    switch ($gateway) {
        case 'flow':
            $payment = new FlowPayment(array_merge($config['flow'], [
                'return_url'  => $config['return_url'],
                'webhook_url' => $config['webhook_url'],
            ]));
            $result = $payment->verifyPayment(['token' => $_GET['token'] ?? '']);
            break;

        case 'mercadopago':
            $payment = new MercadoPagoPayment(array_merge($config['mercadopago'], [
                'return_url'  => $config['return_url'],
                'webhook_url' => $config['webhook_url'],
            ]));
            $result = $payment->verifyPayment(['payment_id' => $_GET['payment_id'] ?? '']);
            break;

        case 'webpay':
            $payment = new WebpayPayment(array_merge($config['webpay'], [
                'return_url' => $config['return_url'],
            ]));
            $result = $payment->verifyPayment(['token_ws' => $_POST['token_ws'] ?? $_GET['token_ws'] ?? '']);
            break;

        default:
            $result = ['success' => false, 'error' => 'Pasarela desconocida'];
    }
} catch (Exception $e) {
    $result = ['success' => false, 'error' => $e->getMessage()];
}

// Limpiar sesión
unset($_SESSION['pending_payment']);

$paid = $result['success'] ?? false;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $paid ? 'Pago exitoso' : 'Pago fallido' ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f5f5;
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; padding: 20px;
        }
        .card {
            background: #fff; border-radius: 16px; padding: 40px;
            max-width: 420px; width: 100%;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            text-align: center;
        }
        .icon { font-size: 4rem; margin-bottom: 20px; }
        h1 { font-size: 1.5rem; margin-bottom: 8px; }
        .msg { color: #666; margin-bottom: 28px; font-size: 0.95rem; }
        .detail { background: #f8f8f8; border-radius: 10px; padding: 16px; text-align: left; margin-bottom: 24px; font-size: 0.88rem; color: #444; }
        .detail div { margin-bottom: 6px; }
        .detail strong { color: #1a1a1a; }
        .btn {
            display: inline-block; padding: 14px 28px; border-radius: 10px;
            text-decoration: none; font-weight: 600; font-size: 0.95rem;
        }
        .btn-primary { background: #1a1a1a; color: #fff; }
        .btn-outline { border: 2px solid #1a1a1a; color: #1a1a1a; margin-left: 10px; }
    </style>
</head>
<body>
<div class="card">
    <?php if ($paid): ?>
        <div class="icon">✅</div>
        <h1>¡Pago exitoso!</h1>
        <p class="msg">Tu reserva quedó confirmada. Te enviaremos un correo con los detalles.</p>
        <div class="detail">
            <?php if (!empty($result['order_id'])): ?>
                <div><strong>Orden:</strong> <?= htmlspecialchars($result['order_id']) ?></div>
            <?php endif; ?>
            <?php if (!empty($result['amount'])): ?>
                <div><strong>Monto:</strong> $<?= number_format((int)$result['amount'], 0, ',', '.') ?> CLP</div>
            <?php endif; ?>
            <?php if (!empty($result['gateway'])): ?>
                <div><strong>Pagado con:</strong> <?= htmlspecialchars(ucfirst($result['gateway'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($result['card_last4'])): ?>
                <div><strong>Tarjeta:</strong> **** <?= htmlspecialchars($result['card_last4']) ?></div>
            <?php endif; ?>
        </div>
        <a href="/" class="btn btn-primary">Volver al inicio</a>
    <?php else: ?>
        <div class="icon">❌</div>
        <h1>Pago no completado</h1>
        <p class="msg">
            <?= htmlspecialchars($result['error'] ?? 'El pago fue rechazado o cancelado. No se realizó ningún cargo.') ?>
        </p>
        <a href="javascript:history.back()" class="btn btn-outline">Intentar de nuevo</a>
        <a href="/" class="btn btn-primary">Ir al inicio</a>
    <?php endif; ?>
</div>
</body>
</html>
