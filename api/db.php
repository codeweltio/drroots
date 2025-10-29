<?php
// PDO connection helper
require_once __DIR__ . '/lib.php';

function db_pdo(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    global $__APP_CONFIG;
    $host = $__APP_CONFIG['DB_HOST'] ?? '127.0.0.1';
    $port = (int)($__APP_CONFIG['DB_PORT'] ?? 3306);
    $db   = $__APP_CONFIG['DB_NAME'] ?? '';
    $user = $__APP_CONFIG['DB_USER'] ?? '';
    $pass = $__APP_CONFIG['DB_PASS'] ?? '';
    $charset = $__APP_CONFIG['DB_CHARSET'] ?? 'utf8mb4';

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $db, $charset);
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
    return $pdo;
}

function ip_to_bin(string $ip): string {
    $bin = @inet_pton($ip);
    return $bin !== false ? $bin : inet_pton('0.0.0.0');
}

function client_ip(): string {
    $candidates = [];
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) $candidates[] = $_SERVER['HTTP_CF_CONNECTING_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        foreach ($parts as $p) { $candidates[] = trim($p); }
    }
    if (!empty($_SERVER['REMOTE_ADDR'])) $candidates[] = $_SERVER['REMOTE_ADDR'];
    foreach ($candidates as $ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
    }
    return '0.0.0.0';
}

function user_agent(): string {
    return substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250);
}

function geo_lookup_ip(string $ip): array {
    // Returns ['country'=>?,'region'=>?,'city'=>?,'source'=>?]
    try {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) return ['country'=>null,'region'=>null,'city'=>null,'source'=>null];
        if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            return ['country'=>$_SERVER['HTTP_CF_IPCOUNTRY'], 'region'=>null, 'city'=>null, 'source'=>'cloudflare'];
        }
        // Simple external lookup with short timeout
        $ctx = stream_context_create(['http' => ['timeout' => 1.5]]);
        $resp = @file_get_contents('https://ipapi.co/' . urlencode($ip) . '/json/', false, $ctx);
        if ($resp) {
            $j = json_decode($resp, true);
            if (is_array($j)) {
                return [
                    'country' => $j['country'] ?? null,
                    'region' => $j['region'] ?? null,
                    'city' => $j['city'] ?? null,
                    'source' => 'ipapi',
                ];
            }
        }
    } catch (Throwable $e) {}
    return ['country'=>null,'region'=>null,'city'=>null,'source'=>null];
}
