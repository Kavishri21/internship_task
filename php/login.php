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

// 2. Extract and sanitize credential inputs
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Basic checks
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

// 3. Import Database connection pools
require_once 'db.php';
require_once 'session.php';

try {
    // 4. Retrieve credentials using MySQL prepared statements
    $query = "SELECT id, name, email, password FROM users WHERE email = ?";
    $stmt  = $pdo->prepare($query);
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // 5. Verify email presence and verify the hashed password using BCrypt
    if (!$user || !password_verify($password, $user['password'])) {
        // Return 401 Unauthorized with generic message to prevent email enumeration
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
        exit;
    }

    // 6. Generate cryptographically secure 64-character session token
    $token = bin2hex(random_bytes(32));

    // Construct session payload matching Phase 4 specs
    $sessionPayload = [
        'id'            => (int) $user['id'],
        'name'          => $user['name'],
        'email'         => $user['email'],
        'creation_time' => time()
    ];

    // 7. Connect to Redis and store session with 3600 seconds (1 hour) TTL
    $redis = getRedisConnection();
    $redis->setex("session:$token", 3600, json_encode($sessionPayload));

    // 8. Return JSON payload containing token
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
