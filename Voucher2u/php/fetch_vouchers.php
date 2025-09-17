<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../databaseConnection/db_config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unexpected error occurred.', 'vouchers' => []];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $categoryName = $_GET['name'] ?? '';

    $categoryMap = [
        'travel' => 1,
        'food' => 2,
        'shopping' => 3
    ];

    $categoryId = $categoryMap[strtolower($categoryName)] ?? null;

    if ($categoryId === null) {
        $response['message'] = 'Invalid category provided.';
        echo json_encode($response);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT Id, Category_id, Points, Title, Image, Description, Terms_and_condition FROM Voucher WHERE Category_id = ?");
        $stmt->execute([$categoryId]);
        $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $processedVouchers = [];
        foreach ($vouchers as $voucher) {
            $imageName = $voucher['Image'] ?? null;
            if (empty($imageName)) {
                // Derive image name from title if not provided
                $derivedImageName = strtolower(str_replace(' ', '-', $voucher['Title']));
                $imagePath = '../assets/voucher_images/' . $derivedImageName . '.jpg'; // Default to .png
                // You might want to check if the file actually exists here
            } else {
                $imagePath = '../assets/voucher_images/' . $imageName; // Use provided image name
            }
            
            $processedVouchers[] = [
                'id' => $voucher['Id'],
                'category_id' => $voucher['Category_id'],
                'points' => $voucher['Points'],
                'title' => $voucher['Title'],
                'image' => $imagePath,
                'description' => $voucher['Description'],
                'terms_and_condition' => $voucher['Terms_and_condition']
            ];
        }

        $response['success'] = true;
        $response['message'] = 'Vouchers fetched successfully.';
        $response['vouchers'] = $processedVouchers;

    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        error_log('Fetch Vouchers PDO Error: ' . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
