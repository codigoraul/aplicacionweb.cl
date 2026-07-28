<?php
require_once __DIR__ . '/PaymentGateway.php';

/**
 * Integración con MercadoPago
 * Documentación: https://www.mercadopago.cl/developers/es/docs
 */
class MercadoPagoPayment extends PaymentGateway
{
    private string $baseUrl = 'https://api.mercadopago.com';

    public function getName(): string
    {
        return 'MercadoPago';
    }

    /**
     * Crea una preferencia de pago y retorna la URL de checkout
     *
     * @param array $order [
     *   'amount'      => 15000,
     *   'title'       => 'Corte de pelo + barba',
     *   'email'       => 'cliente@mail.com',
     *   'order_id'    => 'ORD-001',    // opcional
     * ]
     */
    public function createOrder(array $order): array
    {
        $orderId = $order['order_id'] ?? $this->generateOrderId();

        $body = [
            'items' => [
                [
                    'title'      => $order['title'] ?? 'Servicio barbería',
                    'quantity'   => 1,
                    'unit_price' => (float) $order['amount'],
                    'currency_id' => 'CLP',
                ],
            ],
            'payer' => [
                'email' => $order['email'],
            ],
            'external_reference' => $orderId,
            'back_urls' => [
                'success' => ($this->config['return_url'] ?? '') . '?gateway=mercadopago&status=success',
                'failure' => ($this->config['return_url'] ?? '') . '?gateway=mercadopago&status=failure',
                'pending' => ($this->config['return_url'] ?? '') . '?gateway=mercadopago&status=pending',
            ],
            'auto_return'         => 'approved',
            'notification_url'    => $this->config['webhook_url'] ?? '',
        ];

        $response = $this->httpRequest(
            $this->baseUrl . '/checkout/preferences',
            $body,
            'POST',
            [
                'Authorization: Bearer ' . $this->config['access_token'],
                'Content-Type: application/json',
            ]
        );

        if (!$response['success'] || empty($response['data']['id'])) {
            return [
                'success' => false,
                'error'   => $response['data']['message'] ?? 'Error al crear preferencia en MercadoPago',
            ];
        }

        // En sandbox usa sandbox_init_point, en producción usa init_point
        $key = $this->config['sandbox'] ? 'sandbox_init_point' : 'init_point';
        $redirectUrl = $response['data'][$key];

        return [
            'success'        => true,
            'redirect_url'   => $redirectUrl,
            'preference_id'  => $response['data']['id'],
            'order_id'       => $orderId,
            'gateway'        => 'mercadopago',
        ];
    }

    /**
     * Verifica el estado del pago
     * MercadoPago envía payment_id en la URL de retorno
     *
     * @param array $params ['payment_id' => '...', 'status' => 'approved']
     */
    public function verifyPayment(array $params): array
    {
        if (empty($params['payment_id'])) {
            return ['success' => false, 'error' => 'payment_id no recibido'];
        }

        $response = $this->httpRequest(
            $this->baseUrl . '/v1/payments/' . $params['payment_id'],
            [],
            'GET',
            [
                'Authorization: Bearer ' . $this->config['access_token'],
            ]
        );

        if (!$response['success']) {
            return ['success' => false, 'error' => 'Error verificando pago en MercadoPago'];
        }

        $data   = $response['data'];
        $status = $data['status'] ?? '';

        return [
            'success'    => $status === 'approved',
            'status'     => $status,
            'paid'       => $status === 'approved',
            'order_id'   => $data['external_reference'] ?? '',
            'amount'     => $data['transaction_amount'] ?? 0,
            'gateway'    => 'mercadopago',
            'raw'        => $data,
        ];
    }

    /**
     * Procesa notificación IPN/Webhook de MercadoPago
     */
    public function handleWebhook(array $payload): array
    {
        if (($payload['type'] ?? '') !== 'payment') {
            return ['success' => false, 'error' => 'Tipo de notificación no soportado'];
        }

        return $this->verifyPayment(['payment_id' => $payload['data']['id'] ?? '']);
    }
}
