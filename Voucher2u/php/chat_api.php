<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../vendor/autoload.php'; // Composer autoload
require_once __DIR__ . '/chatbot_functions.php'; // Include chatbot database functions

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../'); // Go up to project root
$dotenv->load();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unexpected error occurred.', 'chat_response' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $userMessage = $input['message'] ?? '';
    $userId = $input['userId'] ?? null; // Get userId from frontend
    $apiKey = $_ENV['GEMMA_API_KEY'] ?? null; // Assuming API key is stored in .env

    $augmentedPrompt = $userMessage;
    $dbResultContext = '';

    // Keyword detection and database lookup
    $userMessageLower = strtolower($userMessage);

    if (str_contains($userMessageLower, 'available vouchers') || str_contains($userMessageLower, 'all vouchers')) {
        $availableVouchers = get_available_vouchers();
        if (!empty($availableVouchers)) {
            $dbResultContext .= "\n\nAvailable Vouchers from Database:\n";
            foreach ($availableVouchers as $voucher) {
                $dbResultContext .= "- Title: " . $voucher['Title'] . ", Description: " . $voucher['Description'] . ", Points: " . $voucher['Points'] . "\n";
            }
        } else {
            $dbResultContext .= "\n\nNo available vouchers found in the database.\n";
        }
    } else if ((str_contains($userMessageLower, 'my vouchers') || str_contains($userMessageLower, 'my rewards')) && $userId) {
        $userVouchers = get_user_vouchers($userId);
        if (!empty($userVouchers)) {
            $dbResultContext .= "\n\nYour Vouchers from Database:\n";
            foreach ($userVouchers as $voucher) {
                $dbResultContext .= "- Title: " . $voucher['Title'] . ", Description: " . $voucher['Description'] . ", Points: " . $voucher['Points'] . "\n";
            }
        } else {
            $dbResultContext .= "\n\nYou don't have any vouchers yet.\n";
        }
    } else if (str_contains($userMessageLower, 'my points')) {
        // TODO: Implement get_user_points() in chatbot_functions.php and call it here.
        // For now, we'll just add a placeholder context.
        $dbResultContext .= "\n\n(Points information would be fetched here if implemented.)\n";
    }

    // Augment the user message with database context
    $systemInstruction = "You are Optima Bot, a helpful assistant for Optima Loyalty Program. Your purpose is to answer questions related to vouchers and loyalty programs. If a user asks a question unrelated to loyalty programs or vouchers, please respond with: 'Sorry, I am Optima Bot, your personal assistant for the Optima Loyalty Program. Please ask me questions related to vouchers or loyalty programs.'\n\n";
    $augmentedPrompt = $systemInstruction . "User: " . $userMessage . $dbResultContext;

    if (empty($userMessage)) {
        $response['message'] = 'No message provided.';
        echo json_encode($response);
        exit;
    }

    if (empty($apiKey)) {
        $response['message'] = 'API Key not configured.';
        echo json_encode($response);
        exit;
    }

    $apiEndpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemma-3-12b-it:generateContent?key=" . $apiKey;

    $postData = [
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $augmentedPrompt] // Use the augmented prompt here
                ]
            ]
        ]
    ];

    $ch = curl_init($apiEndpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

    $apiResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($apiResponse === false) {
        $response['message'] = 'cURL error: ' . $curlError;
    } else {
        $apiResponseData = json_decode($apiResponse, true);
        
        // Simplified response handling: no function calling expected for now
        if ($httpCode === 200 && isset($apiResponseData['candidates'][0]['content']['parts'][0]['text'])) {
            $response['success'] = true;
            $response['chat_response'] = $apiResponseData['candidates'][0]['content']['parts'][0]['text'];
        } else {
            $response['message'] = 'API Error: ' . ($apiResponseData['error']['message'] ?? 'Unknown API error');
            error_log('Gemma API Error: ' . $apiResponse);
        }
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);

?>
