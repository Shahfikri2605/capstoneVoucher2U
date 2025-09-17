<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../databaseConnection/db_config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unexpected error occurred.', 'product' => null];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $productId = $_GET['id'] ?? '';

    if (empty($productId) || !is_numeric($productId)) {
        $response['message'] = 'Invalid product ID provided.';
        echo json_encode($response);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT Id, Category_id, Points, Title, Image, Description, Terms_and_condition FROM Voucher WHERE Id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $imageName = $product['Image'] ?? null;
            if (empty($imageName)) {
                $derivedImageName = strtolower(str_replace(' ', '-', $product['Title']));
                $imagePath = '../assets/voucher_images/' . $derivedImageName . '.jpg'; // Default to .jpg based on user's recent change
            } else {
                $imagePath = '../assets/voucher_images/' . $imageName;
            }
            
            $processedProduct = [
                'id' => $product['Id'],
                'category_id' => $product['Category_id'],
                'points' => $product['Points'],
                'title' => $product['Title'],
                'image' => $imagePath,
                'description' => $product['Description'],
                'terms_and_condition' => $product['Terms_and_condition']
            ];

            $response['success'] = true;
            $response['message'] = 'Product fetched successfully.';
            $response['product'] = $processedProduct;
        } else {
            $response['message'] = 'Product not found.';
        }

    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        error_log('Fetch Product Details PDO Error: ' . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
