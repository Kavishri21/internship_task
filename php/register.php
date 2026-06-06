<?php
// php/register.php
// Backend API registration handler

header('Content-Type: application/json');

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

// Read and decode JSON inputs or form inputs
$name             = trim($_POST['name'] ?? '');
$email            = trim($_POST['email'] ?? '');
$password         = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// 1. Basic Server-side validations
if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Please provide a valid email address.']);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters long.']);
    exit;
}

if ($password !== $confirm_password) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
    exit;
}

// 2. Connect to MySQL Database
require_once 'db.php';

try {
    // 3. Check if email already exists using a Prepared Statement
    $checkQuery = "SELECT id FROM users WHERE email = ?";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$email]);
    
    if ($checkStmt->fetch()) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'This email address is already registered.']);
        exit;
    }
    
    // 4. Secure Password Hashing via BCrypt
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    // 5. Insert Account into Database using a Prepared Statement
    $insertQuery = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
    $insertStmt = $pdo->prepare($insertQuery);
    $insertStmt->execute([$name, $email, $hashedPassword]);
    
    // Success Response
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Registration successful! Redirecting to login...'
    ]);
    exit;

} catch (\PDOException $e) {
    // Log exception details locally (not exposed to client for security reasons)
    error_log("Database error during registration: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An internal database error occurred. Please try again.']);
    exit;
}
?>
