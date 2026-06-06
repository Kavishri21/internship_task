<?php
// php/login.php
// Backend login authentication handler using MySQL and Redis session store

header('Content-Type: application/json');

// 1. Force POST requests only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

// 2. Import session helper first to resolve client IP and rate limits
require_once 'session.php';
$ip = getClientIP();

// Check if IP is currently rate-limited (blocked)
if (isLoginRateLimited($ip)) {
    http_response_code(429);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Too many login attempts. Please try again after 15 minutes.'
    ]);
    exit;
}

// 3. Extract and sanitize credential inputs
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Basic shape checks
if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Please provide both email and password.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Please provide a valid email address.']);
    exit;
}

// 4. Import Database connection pools
require_once 'db.php';

try {
    // 5. Retrieve credentials using MySQL prepared statements
    $query = "SELECT id, name, email, password FROM users WHERE email = ?";
    $stmt  = $pdo->prepare($query);
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // 6. Verify email presence and verify the hashed password using BCrypt
    if (!$user || !password_verify($password, $user['password'])) {
        // Register failed attempt for rate limiting
        recordFailedLogin($ip);

        // Return 401 Unauthorized with generic message to prevent email enumeration
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
        exit;
    }

    // 7. Success path: clear previous failed login attempts from Redis
    clearLoginAttempts($ip);

    // 8. Generate cryptographically secure 64-character session token
    $token = bin2hex(random_bytes(32));

    // Construct session payload matching Phase 4 specs
    $sessionPayload = [
        'id'            => (int) $user['id'],
        'name'          => $user['name'],
        'email'         => $user['email'],
        'creation_time' => time()
    ];

    // 9. Connect to Redis and store session with 3600 seconds (1 hour) TTL
    $redis = getRedisConnection();
    $redis->setex("session:$token", 3600, json_encode($sessionPayload));

    // 10. Return JSON payload containing token
    http_response_code(200);
    echo json_encode([
        'status'        => 'success',
        'message'       => 'Authentication successful! Redirecting...',
        'session_token' => $token
    ]);
    exit;

} catch (\PDOException $e) {
    // Log PDO connection error locally
    error_log("Database error during login: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An internal database error occurred.']);
    exit;
} catch (\Exception $e) {
    // Log generic exceptions (e.g. Redis failures)
    error_log("General server error during login: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A session error occurred. Please try again.']);
    exit;
}
?>
