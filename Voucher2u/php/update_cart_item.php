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
    $quantity = $input['quantity'] ?? null;

    if (empty($voucherId) || empty($userId) || !is_numeric($quantity) || $quantity < 1) {
        $response['message'] = 'Invalid input data.';
        echo json_encode($response);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE cart_items SET Quantity = ? WHERE Voucher_id = ? AND User_id = ?");
        $stmt->execute([$quantity, $voucherId, $userId]);

        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Cart item quantity updated successfully.';
        } else {
            $response['message'] = 'Cart item not found or quantity not changed.';
        }

    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        error_log('Update Cart Item PDO Error: ' . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
