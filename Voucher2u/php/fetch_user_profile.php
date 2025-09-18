<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once '../../databaseConnection/db_config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unexpected error occurred.', 'profile' => null];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = $_GET['user_id'] ?? null;

    if (empty($userId)) {
        $response['message'] = 'User ID is required.';
        echo json_encode($response);
        exit;
    }

    try {
        // Fetch user general information and loyalty points
        $stmt = $pdo->prepare("SELECT Username, email, Phone_number, address, Points FROM User WHERE Id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $profileData = [
                'name' => $user['Username'],
                'email' => $user['email'],
                'phone' => $user['Phone_number'],
                'address' => $user['address'],
                'loyalty_points_balance' => $user['Points'],
                // Placeholder for payment history (can be expanded later)
                'payment_history' => [
                    ['voucher' => '#29359', 'amount' => '$30', 'status' => 'Incomplete', 'time' => '03:09 AM', 'date' => 'Mar 15, 2023', 'device' => 'MAC', 'ip_address' => '192.168.0.1'],
                    ['voucher' => '#29350', 'amount' => '$30', 'status' => 'Succeeded', 'time' => '02:26 AM', 'date' => 'Feb 15, 2023', 'device' => 'MAC', 'ip_address' => '192.168.0.1'],
                    ['voucher' => '#29340', 'amount' => '$30', 'status' => 'Succeeded', 'time' => '12:09 AM', 'date' => 'Jan 15, 2023', 'device' => 'MAC', 'ip_address' => '192.168.0.1'],
                ],
                'payment_history_total' => 3,
                // Placeholder for loyalty program progress
                'loyalty_progress' => [
                    'current_tier' => 'Silver',
                    'progress_percent' => 65,
                    'points_to_next_tier' => 3500,
                    'next_tier' => 'Gold Tier',
                ],
                // Placeholder for activities
                'activities' => [
                    ['description' => 'Redeem 30% travel voucher succeeded', 'timestamp' => '08:02 PM - Feb 15, 2023'],
                    ['description' => 'A new voucher added to cart', 'timestamp' => '07:02 PM - May 02, 2023'],
                ]
            ];

            $response['success'] = true;
            $response['message'] = 'User profile fetched successfully.';
            $response['profile'] = $profileData;
        } else {
            $response['message'] = 'User not found.';
        }

    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        error_log('Fetch User Profile PDO Error: ' . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
