<?php
declare(strict_types=1);

final class TokenProvider {
    private array $cfg;
    private string $cacheFile;

    public function __construct(array $config, string $cacheDir = __DIR__ . '/../_cache') {
        $this->cfg = $config;
        if (!is_dir($cacheDir)) @mkdir($cacheDir, 0775, true);
        $this->cacheFile = rtrim($cacheDir, '/').'/acube_token.json';
    }

    /** Ritorna header Authorization completo, es. "Bearer abc..." */
    public function getAuthHeader(): string {
        $mode = $this->cfg['AUTH_MODE'] ?? 'static';
        if ($mode === 'static') {
            return $this->cfg['ACUBE_API_KEY']; // già completo (es. "Bearer xxx")
        }
        // prova cache
        $tok = $this->readCache();
        if ($tok && ($tok['expires_at'] ?? 0) - 30 > time() && !empty($tok['access_token'])) {
            return 'Bearer ' . $tok['access_token'];
        }
        // rinnova
        $new = ($mode === 'login_json') ? $this->loginJson() : $this->oauth2Password();
        $this->writeCache($new);
        return 'Bearer ' . $new['access_token'];
    }

private function loginJson(): array {
    $url = $this->cfg['ACUBE_AUTH_URL'];
    // il tuo endpoint vuole email/password
    $payload = [
        'email'    => $this->cfg['ACUBE_USERNAME'],
        'password' => $this->cfg['ACUBE_PASSWORD'],
    ];
    [$status, $body] = $this->httpJson('POST', $url, $payload);
    if ($status < 200 || $status >= 300) {
        throw new RuntimeException("Auth failed ($status): $body");
    }
    $json = json_decode($body, true) ?: [];

    $tokenField   = $this->cfg['LOGIN_JSON_TOKEN_FIELD'] ?? 'token';
    $expiresField = $this->cfg['LOGIN_JSON_EXPIRES_FIELD'] ?? null;

    $token   = $json[$tokenField] ?? null;
    // se non c'è expires_in nella risposta, default 3600s
    $expires = $expiresField && isset($json[$expiresField])
                ? (int)$json[$expiresField]
                : 3600;

    if (!$token) {
        throw new RuntimeException('Auth response missing token');
    }
    return [
        'access_token' => $token,
        'expires_at'   => time() + max(60, $expires),
        'raw'          => $json,
    ];
}


    private function oauth2Password(): array {
        $url = $this->cfg['OAUTH_TOKEN_URL'];
        $data = [
            'grant_type' => 'password',
            'client_id'  => $this->cfg['OAUTH_CLIENT_ID'],
            'client_secret' => $this->cfg['OAUTH_CLIENT_SECRET'],
            'username'   => $this->cfg['OAUTH_USERNAME'],
            'password'   => $this->cfg['OAUTH_PASSWORD'],
        ];
        if (!empty($this->cfg['OAUTH_SCOPE'])) $data['scope'] = $this->cfg['OAUTH_SCOPE'];

        [$status, $body] = $this->httpForm('POST', $url, $data);
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException("OAuth failed ($status): $body");
        }
        $json = json_decode($body, true) ?: [];
        $token   = $json['access_token'] ?? null;
        $expires = (int)($json['expires_in'] ?? 3600);
        if (!$token) throw new RuntimeException('OAuth response missing access_token');
        return [
            'access_token' => $token,
            'expires_at'   => time() + max(60, $expires),
            'raw'          => $json,
        ];
    }

    private function readCache(): ?array {
        if (!is_file($this->cacheFile)) return null;
        $raw = @file_get_contents($this->cacheFile);
        if ($raw === false) return null;
        return json_decode($raw, true);
    }

    private function writeCache(array $data): void {
        // lock file per concorrenza
        $tmp = $this->cacheFile . '.tmp';
        file_put_contents($tmp, json_encode($data));
        @rename($tmp, $this->cacheFile);
    }

    private function httpJson(string $method, string $url, array $payload): array {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($err) throw new RuntimeException("CURL error: $err");
        return [$code, (string)$resp];
    }

    private function httpForm(string $method, string $url, array $data): array {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
            CURLOPT_POSTFIELDS => http_build_query($data),
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($err) throw new RuntimeException("CURL error: $err");
        return [$code, (string)$resp];
    }
}

