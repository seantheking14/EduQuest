<?php
/**
 * Database Configuration & Connection
 * Uses PDO with prepared statements to prevent SQL injection.
 */

// Set PHP timezone to UTC
date_default_timezone_set('UTC');

define('DB_HOST', 'mysql-24e761da-mymail-4f8f.j.aivencloud.com');
define('DB_PORT', '18185');
define('DB_NAME', 'defaultdb');
define('DB_USER', 'avnadmin');       // change for production
define('DB_PASS', 'AVNS_Rd0v9GCE8ryXi3vCnWc');           // change for production
define('DB_CHARSET', 'utf8mb4');

function getDBConnection(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            // Set MySQL session timezone to UTC
            $pdo->exec("SET SESSION time_zone = '+00:00'");
        } catch (PDOException $e) {
            http_response_code(503);
            echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
            exit;
        }
    }
    return $pdo;
}
