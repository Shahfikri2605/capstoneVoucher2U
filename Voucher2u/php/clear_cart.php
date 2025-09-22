<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

header('Content-Type: application/json');

require_once '../../databaseConnection/db_config.php';

$response = [
    'success' => false,
    'message' => 'An unknown error occurred.'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? null;

    if ($userId) {
        try {
            global $pdo; // Declare intent to use the global $pdo object
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $userId);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Cart cleared successfully!';
            } else {
                $response['message'] = 'Failed to clear cart in database.';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'User ID not provided.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
