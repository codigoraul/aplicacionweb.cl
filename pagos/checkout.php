<?php
/**
 * Checkout unificado — muestra las 3 opciones de pago
 * Redirige a la pasarela elegida por el cliente
 */

session_start();

require_once __DIR__ . '/FlowPayment.php';
require_once __DIR__ . '/MercadoPagoPayment.php';
require_once __DIR__ . '/WebpayPayment.php';

$config = require __DIR__ . '/config.php';

// ─── Datos del pedido (en producción vendrían de tu base de datos / sesión) ──
$order = [
    'amount'   => $_GET['amount']  ?? 15000,
    'title'    => $_GET['service'] ?? 'Servicio barbería',
    'email'    => $_GET['email']   ?? '',
    'order_id' => $_GET['order_id'] ?? 'ORD-' . strtoupper(uniqid()),
];

// ─── Si ya eligió pasarela → redirigir ───────────────────────────────────────
if (!empty($_POST['gateway'])) {
    $gateway = $_POST['gateway'];
    $error   = null;
    $result  = null;

    try {
        switch ($gateway) {
            case 'flow':
                $payment = new FlowPayment(array_merge($config['flow'], [
                    'return_url'  => $config['return_url'],
                    'webhook_url' => $config['webhook_url'],
                ]));
                break;
            case 'mercadopago':
                $payment = new MercadoPagoPayment(array_merge($config['mercadopago'], [
                    'return_url'  => $config['return_url'],
                    'webhook_url' => $config['webhook_url'],
                ]));
                break;
            case 'webpay':
                $payment = new WebpayPayment(array_merge($config['webpay'], [
                    'return_url' => $config['return_url'],
                ]));
                break;
            default:
                $error = 'Pasarela no válida';
        }

        if (!isset($error) && isset($payment)) {
            $result = $payment->createOrder([
                'amount'   => (int) $_POST['amount'],
                'title'    => $_POST['title']    ?? 'Servicio',
                'subject'  => $_POST['title']    ?? 'Servicio',
                'email'    => $_POST['email']    ?? '',
                'order_id' => $_POST['order_id'] ?? '',
            ]);

            if ($result['success']) {
                // Guardar en sesión para verificar al retornar
                $_SESSION['pending_payment'] = [
                    'gateway'  => $gateway,
                    'order_id' => $result['order_id'],
                    'amount'   => $_POST['amount'],
                ];
                header('Location: ' . $result['redirect_url']);
                exit;
            } else {
                $error = $result['error'] ?? 'Error desconocido';
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagar reserva</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            padding: 36px;
            max-width: 460px;
            width: 100%;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        h1 { font-size: 1.4rem; margin-bottom: 4px; color: #1a1a1a; }
        .subtitle { color: #666; font-size: 0.9rem; margin-bottom: 28px; }
        .amount {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 8px;
        }
        .service-name { color: #444; margin-bottom: 28px; font-size: 0.95rem; }
        .divider { border: none; border-top: 1px solid #eee; margin: 20px 0; }
        .gateway-label { font-size: 0.85rem; color: #888; margin-bottom: 14px; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; }
        .gateways { display: flex; flex-direction: column; gap: 12px; }
        .gateway-btn {
            display: flex;
            align-items: center;
            gap: 14px;
            border: 2px solid #e8e8e8;
            border-radius: 12px;
            padding: 16px 20px;
            background: #fff;
            cursor: pointer;
            width: 100%;
            text-align: left;
            transition: border-color 0.2s, background 0.2s;
            font-size: 0.95rem;
            font-weight: 600;
            color: #1a1a1a;
        }
        .gateway-btn:hover { border-color: #333; background: #fafafa; }
        .gateway-btn .logo { width: 40px; height: 40px; object-fit: contain; }
        .gateway-btn .desc { font-size: 0.78rem; color: #888; font-weight: 400; }
        .error {
            background: #fff0f0;
            border: 1px solid #ffcccc;
            color: #cc0000;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .secure { text-align: center; color: #aaa; font-size: 0.78rem; margin-top: 20px; }
    </style>
</head>
<body>
<div class="card">
    <h1>Confirmar pago</h1>
    <p class="subtitle">Selecciona tu método de pago preferido</p>

    <div class="amount">$<?= number_format((int)$order['amount'], 0, ',', '.') ?> CLP</div>
    <div class="service-name"><?= htmlspecialchars($order['title']) ?></div>

    <hr class="divider">

    <?php if (!empty($error)): ?>
        <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="gateway-label">Pagar con</div>

    <form method="POST" class="gateways">
        <!-- Campos ocultos del pedido -->
        <input type="hidden" name="amount"   value="<?= (int)$order['amount'] ?>">
        <input type="hidden" name="title"    value="<?= htmlspecialchars($order['title']) ?>">
        <input type="hidden" name="email"    value="<?= htmlspecialchars($order['email']) ?>">
        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']) ?>">

        <!-- Flow -->
        <button type="submit" name="gateway" value="flow" class="gateway-btn">
            <svg class="logo" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="40" height="40" rx="8" fill="#0064D2"/>
                <text x="50%" y="56%" dominant-baseline="middle" text-anchor="middle" fill="white" font-size="11" font-weight="bold" font-family="sans-serif">FLOW</text>
            </svg>
            <div>
                <div>Flow</div>
                <div class="desc">Débito, crédito, prepago</div>
            </div>
        </button>

        <!-- MercadoPago -->
        <button type="submit" name="gateway" value="mercadopago" class="gateway-btn">
            <svg class="logo" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="40" height="40" rx="8" fill="#00B1EA"/>
                <text x="50%" y="56%" dominant-baseline="middle" text-anchor="middle" fill="white" font-size="7.5" font-weight="bold" font-family="sans-serif">MP</text>
            </svg>
            <div>
                <div>MercadoPago</div>
                <div class="desc">Tarjetas, cuotas, QR</div>
            </div>
        </button>

        <!-- Webpay Plus -->
        <button type="submit" name="gateway" value="webpay" class="gateway-btn">
            <svg class="logo" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="40" height="40" rx="8" fill="#E4002B"/>
                <text x="50%" y="56%" dominant-baseline="middle" text-anchor="middle" fill="white" font-size="7" font-weight="bold" font-family="sans-serif">WEBPAY</text>
            </svg>
            <div>
                <div>Webpay Plus</div>
                <div class="desc">RedCompra, crédito Transbank</div>
            </div>
        </button>
    </form>

    <p class="secure">🔒 Pago 100% seguro y encriptado</p>
</div>
</body>
</html>
