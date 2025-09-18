<?php
session_start();

header('Content-Type: application/json');

$response = ['loggedIn' => false, 'userName' => ''];

if (isset($_SESSION['Id']) && isset($_SESSION['userName'])) {
    $response['loggedIn'] = true;
    $response['userName'] = $_SESSION['userName'];
    $response['userId'] = $_SESSION['Id']; // Add User_id to the response
}

echo json_encode($response);
?>
