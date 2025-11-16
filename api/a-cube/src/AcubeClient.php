<?php
declare(strict_types=1);
require_once __DIR__ . '/TokenProvider.php';

final class AcubeClient {
    private string $baseUrl;
    private TokenProvider $tokenProvider;

    public function __construct(string $baseUrl, TokenProvider $tokenProvider) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->tokenProvider = $tokenProvider;
    }

    public function sendReceipt(array $payload, ?string $idempotencyKey = null): array {
        return $this->request('POST', '/receipts', $payload, $idempotencyKey);
    }
    public function getReceiptDetails(string $uuid): array {
        return $this->request('GET', "/receipts/{$uuid}/details");
    }
    public function getReceiptPdf(string $uuid): string {
        return $this->requestRaw('GET', "/receipts/{$uuid}/details", 'application/pdf');
    }
    public function voidReceipt(string $uuid): void {
        $this->request('DELETE', "/receipts/{$uuid}");
    }
    public function returnItems(string $uuid, array $items): array {
        return $this->request('POST', "/receipts/{$uuid}/return", ['items' => $items]);
    }

    /*private function request(string $method, string $path, array $body = null, ?string $idempotencyKey = null): array {
        $auth = $this->tokenProvider->getAuthHeader();
        $headers = ['Authorization: ' . $auth, 'Accept: application/json'];
        if ($body !== null) $headers[] = 'Content-Type: application/json';
        if ($idempotencyKey) $headers[] = 'X-Idempotency-Key: ' . $idempotencyKey;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . $path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION));
        }
        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) throw new RuntimeException("CURL error: $err");
        if ($http >= 200 && $http < 300) {
            return $resp !== '' ? (json_decode($resp, true) ?? []) : [];
        }
        $decoded = json_decode($resp, true);
        $msg = $decoded['title'] ?? $decoded['detail'] ?? "HTTP $http";
        throw new RuntimeException("A-Cube error ($http): " . $msg, $http);
    }
*/
    private function request(string $method, string $path, array $body = null, ?string $idempotencyKey = null): array {
    $url = $this->baseUrl . $path;
    $auth = $this->tokenProvider->getAuthHeader();
    $headers = ['Authorization: ' . $auth, 'Accept: application/json'];
    if ($body !== null) $headers[] = 'Content-Type: application/json';
    if ($idempotencyKey) $headers[] = 'X-Idempotency-Key: ' . $idempotencyKey;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
    ]);
    if ($body !== null) {
        $json = json_encode($body, JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION);
        // 🔎 LOG uscita verso A-Cube (redatto)
        error_log("[WRAP→ACUBE] $method $url Idk=" . ($idempotencyKey ?: '(none)'));
        error_log("[WRAP→ACUBE] BodySHA256=" . hash('sha256', $json));
        error_log("[WRAP→ACUBE] Body=" . $json);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    } else {
        error_log("[WRAP→ACUBE] $method $url Idk=" . ($idempotencyKey ?: '(none)'));
    }

    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    // 🔎 LOG risposta A-Cube
    error_log("[ACUBE→WRAP] HTTP $http");
    if (is_string($resp)) error_log("[ACUBE→WRAP] Body: " . $resp);

    if ($err) throw new RuntimeException("CURL error: $err");

    if ($http >= 200 && $http < 300) {
        return $resp !== '' ? (json_decode($resp, true) ?? []) : [];
    }

    // Migliora messaggio d’errore con detail/violations se presenti
    $decoded = json_decode($resp, true);
    $detail = is_array($decoded) ? ($decoded['detail'] ?? null) : null;
    $title  = is_array($decoded) ? ($decoded['title']  ?? null) : null;
    $viol   = is_array($decoded) ? ($decoded['violations'] ?? null) : null;

    $msg = $title ?: $detail ?: "HTTP $http";
    if (is_array($viol)) {
        $chunks = [];
        foreach ($viol as $v) {
            $pp  = $v['propertyPath'] ?? '';
            $ms  = $v['message'] ?? '';
            $chunks[] = ($pp ? "$pp: " : '') . $ms;
        }
        if ($chunks) $msg .= ' | ' . implode(' | ', $chunks);
    }

    throw new RuntimeException("A-Cube error ($http): " . $msg, $http);
}

    private function requestRaw(string $method, string $path, string $accept): string {
        $auth = $this->tokenProvider->getAuthHeader();
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . $path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => ['Authorization: ' . $auth, 'Accept: ' . $accept],
        ]);
        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) throw new RuntimeException("CURL error: $err");
        if ($http >= 200 && $http < 300) return $resp;
        throw new RuntimeException("A-Cube error ($http)");
    }
}

