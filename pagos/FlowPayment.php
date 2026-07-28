<?php
require_once __DIR__ . '/PaymentGateway.php';

/**
 * Integración con Flow.cl
 * Documentación: https://www.flow.cl/docs/api.html
 */
class FlowPayment extends PaymentGateway
{
    public function getName(): string
    {
        return 'Flow';
    }

    /**
     * Crea una orden de pago en Flow y retorna la URL de redirección
     *
     * @param array $order [
     *   'amount'      => 15000,        // monto en CLP
     *   'subject'     => 'Corte de pelo',
     *   'email'       => 'cliente@mail.com',
     *   'order_id'    => 'ORD-001',    // opcional, se genera si no se pasa
     * ]
     */
    public function createOrder(array $order): array
    {
        $orderId = $order['order_id'] ?? $this->generateOrderId();

        $params = [
            'apiKey'      => $this->config['api_key'],
            'commerceOrder' => $orderId,
            'subject'     => $order['subject'] ?? 'Pago servicio',
            'currency'    => 'CLP',
            'amount'      => (int) $order['amount'],
            'email'       => $order['email'],
            'urlConfirmation' => $this->config['webhook_url'] ?? '',
            'urlReturn'   => $this->config['return_url'] ?? '',
        ];

        // Flow requiere firma HMAC-SHA256
        $params['s'] = $this->sign($params);

        $url      = $this->config['api_url'] . '/payment/create';
        $response = $this->httpFormPost($url, $params);

        if (!$response['success'] || empty($response['data']['url'])) {
            return [
                'success' => false,
                'error'   => $response['data']['message'] ?? 'Error al crear orden en Flow',
            ];
        }

        $redirectUrl = $response['data']['url'] . '?token=' . $response['data']['token'];

        return [
            'success'      => true,
            'redirect_url' => $redirectUrl,
            'token'        => $response['data']['token'],
            'order_id'     => $orderId,
            'gateway'      => 'flow',
        ];
    }

    /**
     * Verifica el estado del pago al retornar desde Flow
     *
     * @param array $params ['token' => '...']
     */
    public function verifyPayment(array $params): array
    {
        if (empty($params['token'])) {
            return ['success' => false, 'error' => 'Token no recibido'];
        }

        $data = [
            'apiKey' => $this->config['api_key'],
            'token'  => $params['token'],
        ];
        $data['s'] = $this->sign($data);

        $url      = $this->config['api_url'] . '/payment/getStatus';
        $response = $this->httpFormPost($url, $data);

        if (!$response['success']) {
            return ['success' => false, 'error' => 'Error verificando pago en Flow'];
        }

        $status = $response['data']['status'] ?? 0;

        /*
         * Flow status codes:
         * 1 = pendiente, 2 = pagado, 3 = rechazado, 4 = anulado
         */
        return [
            'success'    => $status === 2,
            'status'     => $status,
            'paid'       => $status === 2,
            'order_id'   => $response['data']['commerceOrder'] ?? '',
            'amount'     => $response['data']['amount'] ?? 0,
            'gateway'    => 'flow',
            'raw'        => $response['data'],
        ];
    }

    // ─── Privados ────────────────────────────────────────────────────────────

    /**
     * Firma los parámetros con HMAC-SHA256 (requerido por Flow)
     */
    private function sign(array $params): string
    {
        ksort($params);
        $chain = '';
        foreach ($params as $key => $value) {
            $chain .= $key . $value;
        }
        return hash_hmac('sha256', $chain, $this->config['secret_key']);
    }

    /**
     * POST con Content-Type: application/x-www-form-urlencoded (Flow lo requiere así)
     */
    private function httpFormPost(string $url, array $data): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'data'    => json_decode($response, true),
        ];
    }
}
