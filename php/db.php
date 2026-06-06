<?php
// php/db.php
// Centralized Database Connection Helper using PDO

$host = '127.0.0.1';
$db   = 'internship_db';
$user = 'root';
$pass = 'Kavi@2110';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // Ensure true prepared statements
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // Return clean JSON error on database failure
     header('Content-Type: application/json', true, 500);
     echo json_encode(['status' => 'error', 'message' => 'Database connection failed. Please try again later.']);
     exit;
}
?>
