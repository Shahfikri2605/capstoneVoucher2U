<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../databaseConnection/db_config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unexpected error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? null;
    $itemsToCheckout = $input['items'] ?? [];

    if (empty($userId)) {
        $response['message'] = 'User ID is required.';
        echo json_encode($response);
        exit;
    }

    if (empty($itemsToCheckout)) {
        $response['message'] = 'No items selected for checkout.';
        echo json_encode($response);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Fetch user's current points
        $stmt = $pdo->prepare("SELECT Points FROM user WHERE Id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $pdo->rollBack();
            $response['message'] = 'User not found.';
            echo json_encode($response);
            exit;
        }

        $userPoints = $user['Points'];
        $totalPointsRequired = 0;

        // Calculate total points required and validate item existence/quantity
        foreach ($itemsToCheckout as $item) {
            $voucherId = $item['voucher_id'];
            $quantity = $item['quantity'];

            $stmt = $pdo->prepare("SELECT Points FROM Voucher WHERE Id = ?");
            $stmt->execute([$voucherId]);
            $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$voucher) {
                $pdo->rollBack();
                $response['message'] = "Voucher with ID {$voucherId} not found.";
                echo json_encode($response);
                exit;
            }
            $totalPointsRequired += $voucher['Points'] * $quantity;
        }

        if ($userPoints < $totalPointsRequired) {
            $pdo->rollBack();
            $response['message'] = 'Insufficient points for checkout. Total required: ' . $totalPointsRequired . ', Available: ' . $userPoints;
            echo json_encode($response);
            exit;
        }

        $newPoints = $userPoints - $totalPointsRequired;
        $stmt = $pdo->prepare("UPDATE user SET Points = ? WHERE Id = ?"); // Corrected table name to 'user'
        $stmt->execute([$newPoints, $userId]);

        $redeemedVouchersDetails = [];

        // Remove items from cart and add to cart_item_history table
        foreach ($itemsToCheckout as $item) {
            $voucherId = $item['voucher_id'];
            $quantity = $item['quantity'];

            // Fetch voucher details for history and PDF generation
            $stmt = $pdo->prepare("SELECT Title, Image, Points, Description FROM Voucher WHERE Id = ?");
            $stmt->execute([$voucherId]);
            $voucherDetails = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$voucherDetails) {
                // This should ideally not happen if initial validation is robust
                $pdo->rollBack();
                $response['message'] = "Voucher details for ID {$voucherId} not found during history insertion.";
                echo json_encode($response);
                exit;
            }

            $voucherCode = '';
            do {
                // Generate a random 10-digit integer. Max for signed 32-bit INT is 2147483647.
                $voucherCode = mt_rand(1000000000, 2147483647); 
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM cart_item_history WHERE voucher_code = ?");
                $stmtCheck->execute([$voucherCode]);
                $isDuplicate = $stmtCheck->fetchColumn() > 0;
            } while ($isDuplicate);

            $pointsCost = $voucherDetails['Points'] * $quantity;

            // Insert into cart_item_history
            $stmt = $pdo->prepare("INSERT INTO cart_item_history (voucher_id, user_id, quantity, completed_date, voucher_code, points_cost) VALUES (?, ?, ?, NOW(), ?, ?)");
            $stmt->execute([$voucherId, $userId, $quantity, $voucherCode, $pointsCost]);

            // Add to response for PDF generation
            $redeemedVouchersDetails[] = [
                'voucher_id' => $voucherId,
                'title' => $voucherDetails['Title'],
                'image' => $voucherDetails['Image'],
                'quantity' => $quantity,
                'voucher_code' => $voucherCode,
                'points_cost' => $pointsCost,
                'description' => $voucherDetails['Description'] // Include description for PDF
            ];

            // Delete from cart_items
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE User_id = ? AND Voucher_id = ?");
            $stmt->execute([$userId, $voucherId]);
        }

        $pdo->commit();
        $response['success'] = true;
        $response['message'] = 'Redemption successful!';
        $response['newPointsBalance'] = $newPoints;
        $response['redeemedVouchers'] = $redeemedVouchersDetails;

    } catch (PDOException $e) {
        $pdo->rollBack();
        $response['message'] = 'Database error during checkout: ' . $e->getMessage();
        error_log('Checkout PDO Error: ' . $e->getMessage());
    } catch (Exception $e) {
        $pdo->rollBack();
        $response['message'] = 'General error during checkout: ' . $e->getMessage();
        error_log('Checkout General Error: ' . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
