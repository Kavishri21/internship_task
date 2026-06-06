<?php
// php/session.php
// Shared Session Verification Helper utilizing Redis

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
        // Connect to local Redis instance (Standard port 6379)
        $redis->connect('127.0.0.1', 6379);
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
?>
