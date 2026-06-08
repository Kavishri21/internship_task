<?php
// php/env.php
// Lightweight helper to parse and load environment variables from a .env file at the project root

(function() {
    // Determine the path to the .env file (one level up from php/ directory)
    $envPath = dirname(__DIR__) . '/.env';

    // If the file does not exist, do nothing and fall back to system env or defaults
    if (!file_exists($envPath)) {
        return;
    }

    // Read the file lines
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments (lines starting with #)
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        // Only parse lines containing "="
        if (strpos($line, '=') !== false) {
            // Split by the first occurrence of "="
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Strip surrounding single or double quotes
            if (preg_match('/^"(.*)"$/', $value, $matches) || preg_match('/^\'(.*)\'$/', $value, $matches)) {
                $value = $matches[1];
            }

            // Load into getenv(), $_ENV, and $_SERVER if not already set by the system
            if (getenv($key) === false) {
                putenv("$key=$value");
            }
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
            }
            if (!isset($_SERVER[$key])) {
                $_SERVER[$key] = $value;
            }
        }
    }
})();
?>
