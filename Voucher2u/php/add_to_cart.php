<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once '../../databaseConnection/db_config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unexpected error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $voucherId = $input['voucher_id'] ?? null;
    $userId = $input['user_id'] ?? null;
    $quantity = $input['quantity'] ?? 1;

    if (empty($voucherId) || empty($userId)) {
        $response['message'] = 'Voucher ID and User ID are required.';
        echo json_encode($response);
        exit;
    }

    if (!is_numeric($quantity) || $quantity < 1) {
        $response['message'] = 'Invalid quantity.';
        echo json_encode($response);
        exit;
    }

    try {
        // Check if the item already exists in the cart for the user
        $stmt = $pdo->prepare("SELECT Quantity FROM cart_items WHERE User_id = ? AND Voucher_id = ?");
        $stmt->execute([$userId, $voucherId]);
        $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingItem) {
            // Item exists, update quantity
            $newQuantity = $existingItem['Quantity'] + $quantity;
            $updateStmt = $pdo->prepare("UPDATE cart_items SET Quantity = ? WHERE User_id = ? AND Voucher_id = ?");
            if ($updateStmt->execute([$newQuantity, $userId, $voucherId])) {
                $response['success'] = true;
                $response['message'] = 'Voucher quantity updated in cart.';
            } else {
                $response['message'] = 'Failed to update cart item quantity.';
            }
        } else {
            // Item does not exist, insert new item
            $insertStmt = $pdo->prepare("INSERT INTO cart_items (User_id, Voucher_id, Quantity) VALUES (?, ?, ?)");
            if ($insertStmt->execute([$userId, $voucherId, $quantity])) {
                $response['success'] = true;
                $response['message'] = 'Voucher added to cart successfully.';
            } else {
                $response['message'] = 'Failed to add voucher to cart.';
                error_log("SQL Error: " . print_r($insertStmt->errorInfo(), true));
            }
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        error_log('Add to Cart PDO Error: ' . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
