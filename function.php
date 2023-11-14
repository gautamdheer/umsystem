<?php
use Firebase\JWT\JWT;

    // Define functions for user management
    function generateToken($userId)
        {
            $key = 'abcdefghijklmnopqrst'; // Replace with a secure, random key
            $payload = [
                'user_id' => $userId,
                'exp' => time() + (60 * 60), // Token expiration time (1 hour)
            ];

            return JWT::encode($payload, $key, 'HS256');
        }
    
    function decodeToken($token)
        {
            $key = 'abcdefghijklmnopqrst'; // Replace with the same key used for encoding
            try {
                return (array) JWT::decode($token, $key, ['HS256']);
            } 
            catch (Exception $e) {
                return null;
            }
        }


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

?>