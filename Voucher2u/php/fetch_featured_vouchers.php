<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../databaseConnection/db_config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unexpected error occurred.', 'vouchers' => []];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Fetch a larger set of vouchers, ordered by points ascending, then randomly
        // This prioritizes lower-point vouchers while still introducing some randomness
        $stmt = $pdo->prepare("SELECT Id, Title, Description, Points, Image FROM Voucher ORDER BY Points ASC, RAND() LIMIT 4");
        $stmt->execute();
        $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($vouchers) {
            $processedVouchers = [];
            foreach ($vouchers as $voucher) {
                // Construct image path
                if (empty($voucher['Image'])) {
                    // If Image is NULL or empty, derive from Title
                    $imageFileName = strtolower(str_replace(' ', '-', $voucher['Title'])) . '.jpg';
                    $voucher['Image_Path'] = '../assets/voucher_images/' . $imageFileName;
                } else {
                    // Use existing image name if available
                    $voucher['Image_Path'] = '../assets/voucher_images/' . $voucher['Image'];
                }
                $processedVouchers[] = $voucher;
            }

            $response['success'] = true;
            $response['message'] = 'Featured vouchers fetched successfully.';
            $response['vouchers'] = $processedVouchers;
        } else {
            $response['message'] = 'No featured vouchers found.';
        }

    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        error_log('Fetch Featured Vouchers PDO Error: ' . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
