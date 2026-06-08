<?php
// php/session.php
// Shared Session Verification and Security Utilities utilizing Redis

require_once __DIR__ . '/env.php';

/**
 * Returns a configured Redis connection instance.
 *
 * @return Redis
 */
function getRedisConnection(): Redis
{
    static $redis = null;
    if ($redis === null) {
        $redis = new Redis();
        
        $redisHost = getenv('REDIS_HOST') ?: '127.0.0.1';
        $redisPort = (int)(getenv('REDIS_PORT') ?: 6379);
        $redisPass = getenv('REDIS_PASSWORD') ?: null;

        // Connect to Redis instance
        $redis->connect($redisHost, $redisPort);
        
        if ($redisPass !== null && $redisPass !== '') {
            $redis->auth($redisPass);
        }
    }
    return $redis;
}

/**
 * Extracts the Bearer token from the HTTP Authorization header.
 *
 * @return string|null The token string, or null if missing or malformed.
 */
function getBearerToken(): ?string
{
    $headers = null;

    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(
            array_map('ucwords', array_keys($requestHeaders)),
            array_values($requestHeaders)
        );
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }

    // Match "Bearer <token>" format case-insensitively
    if ($headers !== null && preg_match('/^Bearer\s+(\S+)$/i', $headers, $matches)) {
        return $matches[1];
    }

    return null;
}

/**
 * Validates the session token from the request against Redis.
 *
 * @param bool $abortOnFail If true, aborts request with a JSON 401 Unauthorized status on failure.
 * @return array|null The decoded session payload, or null if validation fails and abortOnFail is false.
 */
function requireAuth(bool $abortOnFail = true): ?array
{
    $token = getBearerToken();

    if ($token !== null) {
        try {
            $redis = getRedisConnection();
            $sessionRaw = $redis->get("session:$token");

            if ($sessionRaw) {
                $sessionData = json_decode($sessionRaw, true);
                if (is_array($sessionData)) {
                    // Sliding session expiration (refresh TTL on activity)
                    $redis->expire("session:$token", 3600);
                    return $sessionData;
                }
            }
        } catch (\Exception $e) {
            // Log local server-side error, do not expose internal driver state to public API
            error_log("Redis connection or lookup failed: " . $e->getMessage());
        }
    }

    if ($abortOnFail) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Unauthorized access. Please log in.'
        ]);
        exit;
    }

    return null;
}

/**
 * Resolves the client IP address securely.
 *
 * @return string The client IP address.
 */
function getClientIP(): string
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim(end($ips));
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
}

/**
 * Checks if the client IP is currently blocked due to previous rate limit violations.
 *
 * @param string $ip The client IP.
 * @return bool True if currently blocked (rate limited), false otherwise.
 */
function isLoginRateLimited(string $ip): bool
{
    try {
        $redis = getRedisConnection();
        return (bool)$redis->get("rate:block:login:$ip");
    } catch (\Exception $e) {
        error_log("Redis read failure during rate checking: " . $e->getMessage());
        return false;
    }
}

/**
 * Increments the failure count for the client IP and blocks it if thresholds are reached.
 *
 * @param string $ip The client IP.
 * @return bool True if IP is blocked after this increment, false otherwise.
 */
function recordFailedLogin(string $ip): bool
{
    try {
        $redis = getRedisConnection();
        $key = "rate:login:$ip";
        $attempts = $redis->get($key);
        
        if ($attempts === false) {
            // Set first count with 15-minute (900s) window
            $redis->setex($key, 900, 1);
            return false;
        } else {
            $currentAttempts = $redis->incr($key);
            if ($currentAttempts >= 5) {
                // Block IP for 15 minutes (900s)
                $redis->setex("rate:block:login:$ip", 900, 1);
                return true;
            }
        }
    } catch (\Exception $e) {
        error_log("Redis failed write during rate registration: " . $e->getMessage());
    }
    return false;
}

/**
 * Clears recorded failed login attempts for a specific IP.
 *
 * @param string $ip The client IP.
 */
function clearLoginAttempts(string $ip): void
{
    try {
        $redis = getRedisConnection();
        $redis->del("rate:login:$ip");
        $redis->del("rate:block:login:$ip");
    } catch (\Exception $e) {
        error_log("Redis failed attempts reset failure: " . $e->getMessage());
    }
}
?>
