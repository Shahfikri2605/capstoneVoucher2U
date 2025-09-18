<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../databaseConnection/db_config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unexpected error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $voucherId = $input['voucher_id'] ?? null;
    $userId = $input['user_id'] ?? null; // Assume user ID is passed from frontend

    if (empty($voucherId) || empty($userId)) {
        $response['message'] = 'Invalid input data.';
        echo json_encode($response);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE Voucher_id = ? AND User_id = ?");
        $stmt->execute([$voucherId, $userId]);

        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Cart item removed successfully.';
        } else {
            $response['message'] = 'Cart item not found.';
        }

    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        error_log('Delete Cart Item PDO Error: ' . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
