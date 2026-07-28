<?php
/**
 * Clase base abstracta para todas las pasarelas de pago
 */
abstract class PaymentGateway
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Inicia una orden de pago y retorna la URL de redirección
     */
    abstract public function createOrder(array $order): array;

    /**
     * Verifica el estado de un pago (llamado desde retorno o webhook)
     */
    abstract public function verifyPayment(array $params): array;

    /**
     * Retorna el nombre de la pasarela
     */
    abstract public function getName(): string;

    /**
     * Helper: genera una orden de ID única
     */
    protected function generateOrderId(): string
    {
        return 'ORD-' . strtoupper(uniqid());
    }

    /**
     * Helper: hace una petición HTTP POST/GET
     */
    protected function httpRequest(string $url, array $data = [], string $method = 'POST', array $headers = []): array
    {
        $ch = curl_init();

        $defaultHeaders = ['Content-Type: application/json'];
        $allHeaders = array_merge($defaultHeaders, $headers);

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $allHeaders,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => $error];
        }

        $decoded = json_decode($response, true);
        return [
            'success'   => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'data'      => $decoded,
            'raw'       => $response,
        ];
    }
}
