<?php
require_once 'config.php';

if (!isAdmin()) die(json_encode(['success' => false, 'message' => 'Unauthorized']));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadDir = '../uploads/posts/';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

    $images = [];
    $errors = [];

    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        $file_name = $_FILES['images']['name'][$key];
        $file_tmp = $tmp_name;
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $new_name = uniqid() . '.' . strtolower($file_ext);

        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($file_tmp, $uploadDir . $new_name)) {
                $images[] = 'uploads/posts/' . $new_name;
            } else {
                $errors[] = "Failed to upload $file_name";
            }
        } else {
            $errors[] = "$file_name: Invalid format";
        }
    }

    if (empty($errors)) {
        echo json_encode(['success' => true, 'images' => $images]);
    } else {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    }
}
?>