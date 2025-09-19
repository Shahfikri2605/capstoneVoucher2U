<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../databaseConnection/db_config.php';

// You will need to install google/apiclient via Composer:
// composer require google/apiclient:^2.x
require_once '../../vendor/autoload.php'; // Adjust path as necessary

use Dotenv\Dotenv;

// Load environment variables from .env file
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unexpected error occurred.'];

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data) || !isset($data['id_token']) || !isset($data['action'])) {
    $response['message'] = 'Invalid request.';
    echo json_encode($response);
    exit;
}

$id_token = $data['id_token'];
$action = $data['action'];

// Replace with your Google Client ID
$client_id = getenv('GOOGLE_CLIENT_ID'); 

$client = new Google_Client(['client_id' => $client_id]);

try {
    $payload = $client->verifyIdToken($id_token);

    if ($payload) {
        $email = $payload['email'];
        $username = $payload['name'] ?? $payload['email']; // Use name if available, otherwise email
        $google_id = $payload['sub']; // Google User ID

        // Check if user already exists in your database
        $stmt = $pdo->prepare("SELECT Id, Username FROM User WHERE Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($action === 'register') {
            if ($user) {
                $response['message'] = 'An account with this email already exists. Please log in.';
            } else {
                // Register new user
                $points = rand(3000, 5000);
                // For Google registered users, you might not have a phone number or address initially
                $stmt = $pdo->prepare("INSERT INTO User (Email, Username, Password, Profile_image, Address, Points, google_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                // No password for Google sign-in, set to NULL or a placeholder
                $stmt->execute([$email, $username, NULL, NULL, NULL, $points, $google_id]);

                session_start();
                $_SESSION['Id'] = $pdo->lastInsertId();
                $_SESSION['userName'] = $username;
                $_SESSION['userEmail'] = $email;

                $response['success'] = true;
                $response['message'] = 'Registration successful via Google.';
                $response['redirect'] = '../html/HomePage.html';
            }
        } elseif ($action === 'login') {
            if ($user) {
                // Check if the user has previously linked a Google account or if it's a new Google login for existing email
                // You might want to update the google_id if it's a first time Google login for an existing email
                session_start();
                $_SESSION['Id'] = $user['Id'];
                $_SESSION['userName'] = $user['Username'];
                $_SESSION['userEmail'] = $email;

                $response['success'] = true;
                $response['message'] = 'Login successful via Google.';
                $response['redirect'] = '../html/HomePage.html';
            } else {
                $response['message'] = 'No account found with this Google email. Please register.';
            }
        }
    } else {
        $response['message'] = 'Invalid ID token.';
    }
} catch (Exception $e) {
    $response['message'] = 'Authentication error: ' . $e->getMessage();
    error_log('Google Auth Error: ' . $e->getMessage());
}

echo json_encode($response);
?>