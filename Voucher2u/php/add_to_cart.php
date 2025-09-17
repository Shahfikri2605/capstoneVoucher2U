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
    $quantity = $input['quantity'] ?? 1; // Default to 1 if not provided

    if (empty($voucherId) || empty($userId) || !is_numeric($quantity) || $quantity < 1) {
        $response['message'] = 'Invalid input data.';
        echo json_encode($response);
        exit;
    }

    try {
        // Check if the item already exists in the cart
        $stmt = $pdo->prepare("SELECT Quantity FROM cart_items WHERE Voucher_id = ? AND User_id = ?");
        $stmt->execute([$voucherId, $userId]);
        $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingItem) {
            // Update quantity if item exists
            $newQuantity = $existingItem['Quantity'] + $quantity;
            $updateStmt = $pdo->prepare("UPDATE cart_items SET Quantity = ? WHERE Voucher_id = ? AND User_id = ?");
            $updateStmt->execute([$newQuantity, $voucherId, $userId]);
            $response['message'] = 'Cart item quantity updated successfully.';
        } else {
            // Insert new item if it doesn't exist
            $insertStmt = $pdo->prepare("INSERT INTO cart_items (Voucher_id, User_id, Quantity) VALUES (?, ?, ?)");
            $insertStmt->execute([$voucherId, $userId, $quantity]);
            $response['message'] = 'Item added to cart successfully.';
        }

        $response['success'] = true;

    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        error_log('Add to Cart PDO Error: ' . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
