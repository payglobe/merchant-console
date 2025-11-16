<?php

// =========================================
// 3) satispay_client.php – HTTP Signature client
// =========================================

namespace App; 

class SatispayClient
{
    private string $env;

    public function __construct(?string $env = null)
    {
        $this->env = $env ?: Config::DEFAULT_ENV;
    }

    private function privateKeyPem(): string
    {
        $pem = @file_get_contents(Config::SATISPAY_PRIVATE_KEY_PEM_PATH);
        if ($pem === false) {
            throw new \RuntimeException('Cannot read private key PEM');
        }
        return $pem;
    }

    private static function buildDigest(string $body): string
    {
        $hash = hash('sha256', $body, true);
        return 'SHA-256=' . base64_encode($hash);
    }

    private static function signatureInput(string $method, string $path, string $host, string $date, string $digest): string
    {
        $lines = [];
        $lines[] = '(request-target): ' . strtolower($method) . ' ' . $path;
        $lines[] = 'host: ' . $host;
        $lines[] = 'date: ' . $date;
        $lines[] = 'digest: ' . $digest;
        return implode("\n", $lines);
    }

    private static function buildAuthorization(string $keyId, string $signingString, string $privateKeyPem): string
    {
        $pkey = openssl_pkey_get_private($privateKeyPem);
        if (!$pkey) {
            throw new \RuntimeException('Invalid private key');
        }
        $ok = openssl_sign($signingString, $signature, $pkey, OPENSSL_ALGO_SHA256);
        openssl_pkey_free($pkey);
        if (!$ok) {
            throw new \RuntimeException('Unable to sign request');
        }
        $sigB64 = base64_encode($signature);
        $headers = '(request-target) host date digest';
        return sprintf(
            'Signature keyId="%s",algorithm="rsa-sha256",headers="%s",signature="%s"',
            Config::SATISPAY_KEY_ID,
            $headers,
            $sigB64
        );
    }

    /** POST /merchants */
    public function createMerchant(array $payload): array
    {
        $host = Config::host($this->env);
        $base = Config::baseUrl($this->env);
        $path = '/g_provider/v1/merchants';

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new \RuntimeException('Invalid JSON payload');
        }
        $digest = self::buildDigest($body);
        $date = gmdate('D, d M Y H:i:s') . ' GMT';
        $signing = self::signatureInput('POST', $path, $host, $date, $digest);
        $auth = self::buildAuthorization(Config::SATISPAY_KEY_ID, $signing, $this->privateKeyPem());

        $headers = [
            'Host: ' . $host,
            'Date: ' . $date,
            'Digest: ' . $digest,
            'Authorization: ' . $auth,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $ch = curl_init($base . '/merchants');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => Config::HTTP_TIMEOUT_SEC,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            throw new \RuntimeException('cURL error: ' . $err);
        }
        $json = json_decode($resp, true);
        if ($code < 200 || $code >= 300) {
            $msg = $json['error'] ?? $resp;
            throw new \RuntimeException('Satispay HTTP ' . $code . ': ' . $msg);
        }
        return $json ?: [];
    }

    /** GET /merchants/{registration-id} */
    public function getRegistration(string $registrationId): array
    {
        $host = Config::host($this->env);
        $path = '/g_provider/v1/merchants/' . rawurlencode($registrationId);
        $base = 'https://' . $host;

        $date = gmdate('D, d M Y H:i:s') . ' GMT';
        $digest = 'SHA-256=' . base64_encode(hash('sha256', '', true));
        $signing = self::signatureInput('GET', $path, $host, $date, $digest);
        $auth = self::buildAuthorization(Config::SATISPAY_KEY_ID, $signing, $this->privateKeyPem());

        $headers = [
            'Host: ' . $host,
            'Date: ' . $date,
            'Digest: ' . $digest,
            'Authorization: ' . $auth,
            'Accept: application/json',
        ];

        $ch = curl_init($base . $path);
        curl_setopt_array($ch, [
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => Config::HTTP_TIMEOUT_SEC,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            throw new \RuntimeException('cURL error: ' . $err);
        }
        $json = json_decode($resp, true);
        if ($code < 200 || $code >= 300) {
            $msg = $json['error'] ?? $resp;
            throw new \RuntimeException('Satispay HTTP ' . $code . ': ' . $msg);
        }
        return $json ?: [];
    }
}

?>