<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require 'vendor/autoload.php'; // Include Composer autoloader for firebase/php-jwt

// Include necessary files and configurations

// Connection to MySQL database
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "umdb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// all custom functions
require 'function.php';

// Handle API requests

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    switch (strtolower($_SERVER['REQUEST_URI'])) {
        case '/api/register':
            echo registerUser($input);
            break;

        case '/api/login':
            echo loginUser($input);
            break;

        case '/api/logout':
            // Implement logout logic, if necessary
            echo logoutUser();
            break;

        default:
            echo json_response(404, "Not Found");
            break;
    }
} elseif ($_SERVER['REQUEST_METHOD']==='GET') {
    // Get user profile
    if (strtolower($_SERVER['REQUEST_URI']) === '/api/profile') {
       return "gautam dheer";
    } else {
        echo json_response(404, "Not Found");
    }
} 
else {
    echo json_response(405, "Method Not Allowed");
}

// Close the database connection
$conn->close();
?>
