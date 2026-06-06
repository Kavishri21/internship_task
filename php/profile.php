<?php
// php/profile.php
// Backend profile controller supporting GET (retrieval) and POST (update) via MongoDB

header('Content-Type: application/json');

require_once 'session.php';

// 1. Authenticate request using Redis session helper
$user = requireAuth(true);

$method = $_SERVER['REQUEST_METHOD'];

try {
    // 2. Establish connection to local MongoDB
    $manager = new MongoDB\Driver\Manager("mongodb://127.0.0.1:27017");

    if ($method === 'GET') {
        // --- READ OPERATION ---
        $filter = ['mysql_user_id' => (int)$user['id']];
        $query  = new MongoDB\Driver\Query($filter);
        $cursor = $manager->executeQuery('internship_db.user_profiles', $query);
        $profiles = iterator_to_array($cursor);

        $age     = null;
        $dob     = '';
        $contact = '';

        if (!empty($profiles)) {
            $profile = $profiles[0];
            $age     = isset($profile->age) ? (int)$profile->age : null;
            $dob     = isset($profile->dob) ? $profile->dob : '';
            $contact = isset($profile->contact) ? $profile->contact : '';
        }

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'data'   => [
                'name'    => $user['name'],
                'email'   => $user['email'],
                'age'     => $age,
                'dob'     => $dob,
                'contact' => $contact
            ]
        ]);
        exit;

    } elseif ($method === 'POST') {
        // --- WRITE OPERATION ---
        $age     = filter_var($_POST['age'] ?? null, FILTER_VALIDATE_INT);
        $dob     = trim($_POST['dob'] ?? '');
        $contact = trim($_POST['contact'] ?? '');

        // Server-side validation
        if ($age === false || $age < 1 || $age > 120) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Please enter a valid age (1-120).']);
            exit;
        }

        // Validate date of birth matches Gregorian calendar date
        $dobDate = DateTime::createFromFormat('Y-m-d', $dob);
        $errors = DateTime::getLastErrors();
        $isValidDate = $dobDate && 
                       ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0)) && 
                       $dobDate->format('Y-m-d') === $dob;

        if (!$isValidDate) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Please enter a valid date of birth (YYYY-MM-DD).']);
            exit;
        }

        $today = new DateTime('today');
        $minDate = (new DateTime('today'))->modify('-120 years');

        if ($dobDate > $today) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Date of birth cannot be in the future.']);
            exit;
        }

        if ($dobDate < $minDate) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Date of birth cannot be more than 120 years ago.']);
            exit;
        }

        // Validate contact using strict phone regex
        if (!preg_match('/^\+?[0-9\s\-()]{7,20}$/', $contact)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Please enter a valid contact number (7-20 characters, containing only numbers, spaces, hyphens, parentheses, or a leading plus sign).']);
            exit;
        }

        // Prepare bulk update with upsert capability
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update(
            ['mysql_user_id' => (int)$user['id']],
            ['$set' => [
                'mysql_user_id' => (int)$user['id'],
                'email'         => $user['email'],
                'age'           => (int)$age,
                'dob'           => $dob,
                'contact'       => $contact
            ]],
            ['upsert' => true] // Create document if it does not exist
        );

        $manager->executeBulkWrite('internship_db.user_profiles', $bulk);

        http_response_code(200);
        echo json_encode([
            'status'  => 'success',
            'message' => 'Profile updated successfully.'
        ]);
        exit;
    } else {
        // Other methods are disallowed
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
        exit;
    }

} catch (\MongoDB\Driver\Exception\Exception $e) {
    error_log("MongoDB driver error in profile.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A profile database error occurred.']);
    exit;
} catch (\Exception $e) {
    error_log("General error in profile.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An internal server error occurred.']);
    exit;
}
?>
