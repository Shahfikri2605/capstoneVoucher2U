<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../databaseConnection/db_config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unexpected error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['Email'] ?? '';
    $username = $_POST['Username'] ?? '';
    $password = $_POST['Password'] ?? '';
    $phoneNumber = $_POST['Phone_number'] ?? null;
    $address = $_POST['Address'] ?? null;
    $profileImage = null;

    // Handle profile image upload
    // if (isset($_FILES['Profile_image']) && $_FILES['Profile_image']['error'] === UPLOAD_ERR_OK) {
    //     $target_dir = "../../uploads/profile_images/"; // Ensure this directory exists and is writable
    //     $target_file = $target_dir . basename($_FILES['Profile_image']['name']);
    //     $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    //     // Allow certain file formats
    //     $extensions_arr = array("jpg", "jpeg", "png", "gif");
    //     if (in_array($imageFileType, $extensions_arr)) {
    //         if (move_uploaded_file($_FILES['Profile_image']['tmp_name'], $target_file)) {
    //             $profileImage = $target_file;
    //         } else {
    //             $response['message'] = 'Failed to upload profile image.';
    //             echo json_encode($response);
    //             exit;
    //         }
    //     } else {
    //         $response['message'] = 'Invalid image file type. Only JPG, JPEG, PNG, GIF are allowed.';
    //         echo json_encode($response);
    //         exit;
    //     }
    // }

    // Input validation
    if (empty($email) || empty($username) || empty($password)) {
        $response['message'] = 'Please fill in all required fields: email, username, and password.';
        echo json_encode($response);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format.';
        echo json_encode($response);
        exit;
    }

   

    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT Id FROM User WHERE Email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $response['message'] = 'An account with this email already exists.';
            echo json_encode($response);
            exit;
        }

        // Generate random points between 3000 and 5000
        $points = rand(10000, 25000);

        // Insert new user into the database
        $stmt = $pdo->prepare("INSERT INTO User (Email, Username, Phone_number, Password, Profile_image, Address, Points) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$email, $username, $phoneNumber, $password, $profileImage, $address, $points]);

        // Get the last inserted userID
        $last_id = $pdo->lastInsertId();

        // Start session and set session variables
        session_start();
        $_SESSION['Id'] = $last_id;
        $_SESSION['userName'] = $username;
        $_SESSION['userEmail'] = $email;

        $response['success'] = true;
        $response['message'] = 'Registration successful.';
        $response['redirect'] = '../html/HomePage.html';

    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        error_log('Registration PDO Error: ' . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
