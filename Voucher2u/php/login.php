<?php
require_once '../../databaseConnection/db_config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unexpected error occurred.'];

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
        $stmt = $pdo->prepare("SELECT UserID, Name, Password FROM User WHERE Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $password === $user['Password']) {
            // Password is correct, start a new session
            session_start();
            $_SESSION['userID'] = $user['UserID'];
            $_SESSION['userName'] = $user['Name'];
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
