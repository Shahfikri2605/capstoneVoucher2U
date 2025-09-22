<?php

require_once __DIR__ . '/../../vendor/autoload.php'; // Composer autoload
require_once '../../databaseConnection/db_config.php';

use Dompdf\Dompdf;
use Dompdf\Options;

    $input = json_decode(file_get_contents('php://input'), true);
    $redeemedVouchersInput = $input['redeemedVouchers'] ?? [];
    $newPointsBalance = $input['newPointsBalance'] ?? 0;

    // Validate input
    if (empty($redeemedVouchersInput)) {
        echo json_encode(['success' => false, 'message' => 'No redeemed vouchers provided.']);
        exit;
    }

    // Database connection
    $database = new Database();
    $conn = $database->getConnection();

    $redeemedVouchers = [];
    $totalPointsCost = 0;
    $termsAndConditions = ''; // Initialize terms and conditions

    foreach ($redeemedVouchersInput as $voucherInput) {
        $voucherId = $voucherInput['voucher_id'] ?? null;
        $quantity = $voucherInput['quantity'] ?? 1;

        if ($voucherId) {
            $query = "SELECT title, description, image, points_cost, term_and_condition FROM vouchers WHERE voucher_id = :voucher_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':voucher_id', $voucherId);
            $stmt->execute();
            $voucherDetails = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($voucherDetails) {
                // Generate a simple unique voucher code (you might want a more robust solution)
                $voucherCode = strtoupper(bin2hex(random_bytes(5))); 

                $redeemedVouchers[] = [
                    'title' => $voucherDetails['title'],
                    'description' => $voucherDetails['description'],
                    'quantity' => $quantity,
                    'voucher_code' => $voucherCode,
                    'points_cost' => $voucherDetails['points_cost'],
                    'image' => $voucherDetails['image']
                ];
                $totalPointsCost += ($voucherDetails['points_cost'] * $quantity);
                // Assuming terms and conditions are the same for all vouchers or we take the first one found
                if (empty($termsAndConditions) && !empty($voucherDetails['term_and_condition'])) {
                    $termsAndConditions = $voucherDetails['term_and_condition'];
                }
            }
        }
    }

    $conn = null; // Close connection

    $dompdf = new Dompdf();
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf->setOptions($options);

    $html = '
    <h1>Voucher Redemption Confirmation</h1>
    <p>Thank you for redeeming your vouchers. Below is a summary of your redemption:</p>
    <h2>Redeemed Vouchers</h2>
    <ul>';

    foreach ($redeemedVouchers as $voucher) {
        $html .= '<li>';
        $html .= '<strong>Voucher:</strong> ' . htmlspecialchars($voucher['title']) . '<br>';
        $html .= '<strong>Description:</strong> ' . htmlspecialchars($voucher['description']) . '<br>';
        $html .= '<strong>Quantity:</strong> ' . htmlspecialchars($voucher['quantity']) . '<br>';
        $html .= '<strong>Voucher Code:</strong> ' . htmlspecialchars($voucher['voucher_code']) . '<br>';
        $html .= '<strong>Points Cost:</strong> ' . htmlspecialchars($voucher['points_cost']) . '<br>';
        if (!empty($voucher['image'])) {
            $html .= '<img src="../' . htmlspecialchars($voucher['image']) . '" alt="Voucher Image" style="max-width: 100px; height: auto;">';
        }
        $html .= '</li>';
    }

    $html .= '</ul>';
    $html .= '<h2>Total Points Deducted: ' . htmlspecialchars($totalPointsCost) . '</h2>';
    $html .= '<h2>Terms and Conditions</h2>';
    $html .= '<p>' . htmlspecialchars($termsAndConditions) . '</p>';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream('vouchers_redeemed.pdf', ['Attachment' => false]);
