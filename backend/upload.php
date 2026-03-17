<?php
require 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $upload_dir = __DIR__ . "/../uploads/";
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
    $file = $_FILES['file'];
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        http_response_code(400); echo json_encode(['message' => 'File is not a valid image.']); exit;
    }
    if ($file['size'] > 5000000) {
        http_response_code(400); echo json_encode(['message' => 'File is too large. Max 5MB.']); exit;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) {
        http_response_code(400); echo json_encode(['message' => 'Only JPG, PNG, GIF, WEBP files are allowed.']); exit;
    }
    $new_name = uniqid('tour_', true) . '.' . $ext;
    $target = $upload_dir . $new_name;
    if (move_uploaded_file($file['tmp_name'], $target)) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $url = $protocol . '://' . $host . '/Tour_Guide_System/uploads/' . $new_name;
        echo json_encode(['url' => $url]);
    } else {
        http_response_code(500); echo json_encode(['message' => 'Error saving file. Check uploads folder permissions.']);
    }
} else {
    http_response_code(400); echo json_encode(['message' => 'No file uploaded.']);
}