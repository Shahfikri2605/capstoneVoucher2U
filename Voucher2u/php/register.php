<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../databaseConnection/db_config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unexpected error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $name = $_POST['name'] ?? '';
    $password = $_POST['password'] ?? '';
    $cardNumber = $_POST['cardNumber'] ?? null;
    $bankAccNum = $_POST['bankAccNum'] ?? null;

    // Input validation
    if (empty($email) || empty($name) || empty($password)) {
        $response['message'] = 'Please fill in all required fields: email, name, and password.';
        echo json_encode($response);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format.';
        echo json_encode($response);
        exit;
    }

   

    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT UserID FROM User WHERE Email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $response['message'] = 'An account with this email already exists.';
            echo json_encode($response);
            exit;
        }

        // Insert new user into the database
        $stmt = $pdo->prepare("INSERT INTO User (Email, Name, Password, CardNumber, BankAccNum) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$email, $name, $password, $cardNumber, $bankAccNum]);

        $response['success'] = true;
        $response['message'] = 'Registration successful.';

    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        error_log('Registration PDO Error: ' . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
