<?php

// Include necessary files and configurations

// Connection to MySQL database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "umdb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Define functions for user management

function registerUser($data)
{
    global $conn;

    // Validate input
    if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
        return json_response(400, "Invalid input");
    }

    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return json_response(400, "Invalid email format");
    }

    // Check for unique username
    $existingUser = getUserByUsername($data['username']);
    if ($existingUser) {
        return json_response(400, "Username already exists");
    }

    // Hash the password before storing it
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    // Insert user into the database
    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $data['username'], $data['email'], $hashedPassword);

    if ($stmt->execute()) {
        return json_response(201, "User registered successfully");
    } else {
        return json_response(500, "Internal Server Error");
    }
}

function loginUser($data)
{
    global $conn;

    // Validate input
    if (!isset($data['username']) || !isset($data['password'])) {
        return json_response(400, "Invalid input");
    }

    // Retrieve user from the database
    $user = getUserByUsername($data['username']);

    // Check if the user exists and verify the password
    if ($user && password_verify($data['password'], $user['password'])) {
        // Generate a JWT token (you may need to use a JWT library)
        $token = generateToken($user['user_id']);
        return json_response(200, "Login successful", ['token' => $token]);
    } else {
        return json_response(401, "Invalid credentials");
    }
}

function getUserByUsername($username)
{
    global $conn;

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return null;
    }
}

function getProfile($userId)
{
    global $conn;

    $sql = "SELECT username, email FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return json_response(200, "Profile retrieved successfully", $result->fetch_assoc());
    } else {
        return json_response(404, "User not found");
    }
}

function logoutUser()
{
    // You may want to implement additional logic for logout, such as invalidating the token
    return json_response(200, "Logout successful");
}

function json_response($status, $message, $data = [])
{
    header("Content-Type: application/json");
    http_response_code($status);

    $response = ['status' => $status, 'message' => $message, 'data' => $data];

    echo json_encode($response);
    exit();
}

// Handle API requests

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($_SERVER['REQUEST_URI']) {
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
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get user profile
    if ($_SERVER['REQUEST_URI'] === '/api/profile') {
        // Check for token in headers
        $headers = apache_request_headers();
        $token = isset($headers['Authorization']) ? $headers['Authorization'] : null;

        if ($token) {
            // Decode and verify the JWT token (you may need to use a JWT library)
            $decodedToken = decodeToken($token);

            if ($decodedToken) {
                // Get user profile using user ID from the decoded token
                echo getProfile($decodedToken['user_id']);
            } else {
                echo json_response(401, "Invalid token");
            }
        } else {
            echo json_response(401, "Token not provided");
        }
    } else {
        echo json_response(404, "Not Found");
    }
} else {
    echo json_response(405, "Method Not Allowed");
}

// Close the database connection
$conn->close();

?>
