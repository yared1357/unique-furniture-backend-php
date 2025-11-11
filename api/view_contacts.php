<?php
require_once 'config.php';
header("Access-Control-Allow-Origin: *");

$result = $conn->query("SELECT * FROM contacts ORDER BY submitted_at DESC");
$contacts = [];

while ($row = $result->fetch_assoc()) {
    $contacts[] = $row;
}

echo json_encode($contacts);
?>