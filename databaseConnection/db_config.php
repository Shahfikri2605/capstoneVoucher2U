<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Loads composer packages

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../'); // Go up to project root
$dotenv->load();

$host     = $_ENV['DB_HOST'];
$port     = $_ENV['DB_PORT'];
$dbname   = $_ENV['DB_NAME'];
$user     = $_ENV['DB_USER'];
$password = $_ENV['DB_PASSWORD'];

try {
    // Create a DSN for MySQL (PDO)
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4;";
    $options = [
        PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_SSL_CA     => __DIR__ . '/azure_mysql_ssl.crt',
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false // Set to true in production if you have all certs
    ];
    $pdo = new PDO($dsn, $user, $password, $options);

    // Log connection success to error log
    error_log("Database connection successful.");

    // Optional: Debug output if requested via URL param (only for manual test)
    if (isset($_GET['debug']) && $_GET['debug'] == 1) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Database connection successful.'
        ]);
        exit;
    }

    // Test the connection (fetching current database name)
    $stmt = $pdo->query("SELECT DATABASE()");
    error_log("Connected to database: " . $stmt->fetchColumn());

    // Check if the 'users' table exists and retrieve its structure
   // $stmt = $pdo->query("DESCRIBE users");
    //error_log("Users table structure: " . print_r($stmt->fetchAll(), true));

} catch (PDOException $e) {
    // Handle connection error
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed.',
        'error' => $e->getMessage()
    ]);
    exit;
}
?>
