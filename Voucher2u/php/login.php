<?php
require_once '../../databaseConnection/db_config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unexpected error occurred.'];

error_log("Login request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Login POST data: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Input validation
    if (empty($email) || empty($password)) {
        $response['message'] = 'Please enter both email and password.';
        echo json_encode($response);
        exit;
    }

    try {
        // Fetch user from database
        $stmt = $pdo->prepare("SELECT Id, Username, Password FROM User WHERE Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $password === $user['Password']) {
            // Password is correct, start a new session
            session_start();
            $_SESSION['Id'] = $user['Id'];
            $_SESSION['userName'] = $user['Username'];
            $_SESSION['userEmail'] = $email;

            $response['success'] = true;
            $response['message'] = 'Login successful.';
            $response['redirect'] = '../html/HomePage.html'; // Redirect to home page

        } else {
            $response['message'] = 'Invalid email or password.';
        }

    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        error_log('Login PDO Error: ' . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
