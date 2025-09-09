<?php
session_start();

header('Content-Type: application/json');

$response = ['loggedIn' => false, 'userName' => ''];

if (isset($_SESSION['userID']) && isset($_SESSION['userName'])) {
    $response['loggedIn'] = true;
    $response['userName'] = $_SESSION['userName'];
}

echo json_encode($response);
?>
