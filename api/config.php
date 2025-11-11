<?php
// api/config.php
ob_start();
session_start();

$host = 'localhost';
$db   = 'ceilcraft_db';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

header('Content-Type: application/json');
ob_clean();

function isAdmin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

define('BASE_URL', 'http://localhost/ceilcraft');
function absUrl($path) {
    return BASE_URL . '/' . ltrim($path, '/');
}
?>