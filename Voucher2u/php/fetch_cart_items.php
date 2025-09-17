<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../databaseConnection/db_config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unexpected error occurred.', 'cart_items' => []];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = $_GET['user_id'] ?? null;

    if (empty($userId)) {
        $response['message'] = 'User ID is required.';
        echo json_encode($response);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT ci.Voucher_id, ci.Quantity, v.Title, v.Points, v.Image, v.Description FROM cart_items ci JOIN Voucher v ON ci.Voucher_id = v.Id WHERE ci.User_id = ?");
        $stmt->execute([$userId]);
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $processedCartItems = [];
        foreach ($cartItems as $item) {
            $imageName = $item['Image'] ?? null;
            if (empty($imageName)) {
                $derivedImageName = strtolower(str_replace(' ', '-', $item['Title']));
                $imagePath = '../assets/voucher_images/' . $derivedImageName . '.jpg';
            } else {
                $imagePath = '../assets/voucher_images/' . $imageName;
            }

            $processedCartItems[] = [
                'voucher_id' => $item['Voucher_id'],
                'quantity' => $item['Quantity'],
                'title' => $item['Title'],
                'points' => $item['Points'],
                'image' => $imagePath,
                'description' => $item['Description']
            ];
        }

        $response['success'] = true;
        $response['message'] = 'Cart items fetched successfully.';
        $response['cart_items'] = $processedCartItems;

    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        error_log('Fetch Cart Items PDO Error: ' . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
