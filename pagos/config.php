<?php
/**
 * Configuración de pasarelas de pago
 * Reemplaza los valores con tus credenciales reales
 */

return [

    // ─── FLOW ────────────────────────────────────────────────────────────────
    'flow' => [
        'api_key'    => 'TU_FLOW_API_KEY',
        'secret_key' => 'TU_FLOW_SECRET_KEY',
        'sandbox'    => true, // false en producción
        'api_url'    => 'https://sandbox.flow.cl/api', // producción: https://www.flow.cl/api
    ],

    // ─── MERCADOPAGO ─────────────────────────────────────────────────────────
    'mercadopago' => [
        'access_token'  => 'TU_MP_ACCESS_TOKEN',
        'public_key'    => 'TU_MP_PUBLIC_KEY',
        'sandbox'       => true,
    ],

    // ─── WEBPAY PLUS ─────────────────────────────────────────────────────────
    'webpay' => [
        'commerce_code' => '597055555532',        // código de prueba Transbank
        'api_key'       => '579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C', // llave de prueba
        'sandbox'       => true,
        'api_url'       => 'https://webpay3gint.transbank.cl', // producción: https://webpay3g.transbank.cl
    ],

    // ─── GENERAL ─────────────────────────────────────────────────────────────
    'return_url'  => 'https://tudominio.cl/pagos/retorno.php',
    'webhook_url' => 'https://tudominio.cl/pagos/webhook.php',
    'currency'    => 'CLP',

];
