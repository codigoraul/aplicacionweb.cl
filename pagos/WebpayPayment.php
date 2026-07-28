<?php
require_once __DIR__ . '/PaymentGateway.php';

/**
 * Integración con Webpay Plus (Transbank)
 * API REST directa — sin SDK externo requerido
 * Documentación: https://www.transbankdevelopers.cl/referencia/webpay
 */
class WebpayPayment extends PaymentGateway
{
    public function getName(): string
    {
        return 'Webpay Plus';
    }

    /**
     * Inicia una transacción Webpay Plus
     *
     * @param array $order [
     *   'amount'   => 15000,
     *   'order_id' => 'ORD-001',   // opcional
     *   'session_id' => 'SES-001', // opcional
     * ]
     */
    public function createOrder(array $order): array
    {
        $orderId   = $order['order_id']   ?? $this->generateOrderId();
        $sessionId = $order['session_id'] ?? session_id() ?: uniqid('ses_');

        $body = [
            'buy_order'   => $orderId,
            'session_id'  => $sessionId,
            'amount'      => (int) $order['amount'],
            'return_url'  => $this->config['return_url'] ?? '',
        ];

        $response = $this->httpRequest(
            $this->config['api_url'] . '/rswebpaytransaction/api/webpay/v1.2/transactions',
            $body,
            'POST',
            $this->getHeaders()
        );

        if (!$response['success'] || empty($response['data']['token'])) {
            return [
                'success' => false,
                'error'   => $response['data']['error_message'] ?? 'Error al iniciar transacción Webpay',
            ];
        }

        return [
            'success'      => true,
            'redirect_url' => $response['data']['url'] . '?token_ws=' . $response['data']['token'],
            'token'        => $response['data']['token'],
            'order_id'     => $orderId,
            'gateway'      => 'webpay',
        ];
    }

    /**
     * Confirma la transacción Webpay (OBLIGATORIO llamar después de que el usuario regresa)
     * Transbank requiere confirmar con PUT para que el pago sea efectivo
     *
     * @param array $params ['token_ws' => '...']
     */
    public function verifyPayment(array $params): array
    {
        $token = $params['token_ws'] ?? $params['token'] ?? '';

        if (empty($token)) {
            return ['success' => false, 'error' => 'token_ws no recibido'];
        }

        // Confirmar la transacción (PUT)
        $response = $this->httpRequest(
            $this->config['api_url'] . '/rswebpaytransaction/api/webpay/v1.2/transactions/' . $token,
            [],
            'PUT',
            $this->getHeaders()
        );

        if (!$response['success']) {
            return ['success' => false, 'error' => 'Error confirmando transacción Webpay'];
        }

        $data           = $response['data'];
        $responseCode   = $data['response_code'] ?? -1;
        $status         = $data['status'] ?? '';

        /*
         * response_code = 0 → aprobado
         * Cualquier otro valor → rechazado
         */
        return [
            'success'       => $responseCode === 0 && $status === 'AUTHORIZED',
            'paid'          => $responseCode === 0,
            'status'        => $status,
            'response_code' => $responseCode,
            'order_id'      => $data['buy_order'] ?? '',
            'amount'        => $data['amount'] ?? 0,
            'card_last4'    => $data['card_detail']['card_number'] ?? '',
            'gateway'       => 'webpay',
            'raw'           => $data,
        ];
    }

    // ─── Privados ────────────────────────────────────────────────────────────

    /**
     * Headers requeridos por Transbank API
     */
    private function getHeaders(): array
    {
        return [
            'Tbk-Api-Key-Id: '     . $this->config['commerce_code'],
            'Tbk-Api-Key-Secret: ' . $this->config['api_key'],
            'Content-Type: application/json',
        ];
    }

    /**
     * Override para soportar método PUT
     */
    protected function httpRequest(string $url, array $data = [], string $method = 'POST', array $headers = []): array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => $error, 'data' => []];
        }

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'data'    => json_decode($response, true) ?? [],
        ];
    }
}
