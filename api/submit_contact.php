<?php
require_once 'config.php';

// Enable CORS for React (localhost:3000)
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Read JSON input from React
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Sanitize inputs
$name    = trim($conn->real_escape_string($input['name']));
$email   = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
$phone   = trim($conn->real_escape_string($input['phone']));
$service = trim($conn->real_escape_string($input['service']));
$message = trim($conn->real_escape_string($input['message']));

// Validation
if (!$name || !$email || !$message) {
    echo json_encode(['success' => false, 'message' => 'Name, email, and message are required']);
    exit;
}

// Insert into DB
$sql = "INSERT INTO contacts (name, email, phone, service, message) 
        VALUES ('$name', '$email', '$phone', '$service', '$message')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(['success' => true, 'message' => 'Message sent successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save message']);
}

$conn->close();
?>