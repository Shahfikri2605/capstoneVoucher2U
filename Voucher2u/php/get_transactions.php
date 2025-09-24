<?php
session_start();
require_once '../../databaseConnection/db_config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unexpected error occurred.'];

// Require logged in user
if (!isset($_SESSION['Id'])) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit;
}

$userId = $_SESSION['Id'];

try {
    // Fetch recent transactions for the user, joining voucher title
    $stmt = $pdo->prepare(
        "SELECT ch.completed_date AS completed_date, ch.voucher_id, ch.quantity, ch.points_cost, v.Title AS title
         FROM cart_item_history ch
         LEFT JOIN Voucher v ON v.Id = ch.voucher_id
         WHERE ch.user_id = ?
         ORDER BY ch.completed_date DESC
         LIMIT 100"
    );
    $stmt->execute([$userId]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalize data
    $normalized = [];
    foreach ($transactions as $t) {
        $normalized[] = [
            'title' => $t['title'] ?? ('Voucher #' . $t['voucher_id']),
            'voucher_id' => $t['voucher_id'],
            'quantity' => (int) $t['quantity'],
            'points_cost' => (int) $t['points_cost'],
            'completed_date' => $t['completed_date'] ?? null
        ];
    }

    $response['success'] = true;
    $response['transactions'] = $normalized;
    $response['message'] = 'Transactions fetched.';

} catch (PDOException $e) {
    error_log('get_transactions PDO Error: ' . $e->getMessage());
    $response['message'] = 'Database error while fetching transactions.';
}

echo json_encode($response);
?>
