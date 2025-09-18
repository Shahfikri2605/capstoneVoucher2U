<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../databaseConnection/db_config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unexpected error occurred.', 'vouchers' => []];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $searchQuery = $_GET['query'] ?? '';

    if (empty($searchQuery)) {
        $response['success'] = true;
        $response['message'] = 'No search query provided. Returning all active vouchers.';
        // Optionally return all active vouchers if no query, or simply an empty list
        $sql = "SELECT Id, Title, Description, Points, Image FROM Voucher ORDER BY Title ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $sql = "SELECT Id, Title, Description, Points, Image FROM Voucher WHERE (Title LIKE ? OR Description LIKE ?) ORDER BY Title ASC";
        $stmt = $pdo->prepare($sql);
        $searchTerm = '%' . $searchQuery . '%';
        $stmt->execute([$searchTerm, $searchTerm]);
        $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($vouchers)) {
            $response['message'] = 'No vouchers found matching your search.';
        } else {
            $response['message'] = 'Vouchers fetched successfully.';
        }
    }

    if (isset($vouchers)) {
        $processedVouchers = [];
        foreach ($vouchers as $voucher) {
            // Construct image path
            if (empty($voucher['Image'])) {
                $imageFileName = strtolower(str_replace(' ', '-', $voucher['Title'])) . '.jpg';
                $voucher['Image_Path'] = '../assets/voucher_images/' . $imageFileName;
            } else {
                $voucher['Image_Path'] = '../assets/voucher_images/' . $voucher['Image'];
            }
            $processedVouchers[] = $voucher;
        }
        $response['success'] = true;
        $response['vouchers'] = $processedVouchers;
    }

} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
