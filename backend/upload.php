<?php
require 'config.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guide') {
    http_response_code(403);
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $target_dir = "../uploads/";
    $file_name = basename($_FILES["file"]["name"]);
    // Sanitize filename
    $file_name = preg_replace("/[^a-zA-Z0-9.]/", "_", $file_name);
    // Add unique prefix
    $target_file = $target_dir . uniqid() . '_' . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    $check = getimagesize($_FILES["file"]["tmp_name"]);
    if ($check === false) {
        http_response_code(400);
        echo json_encode(['message' => 'File is not an image.']);
        exit;
    }

    if ($_FILES["file"]["size"] > 5000000) { // 5MB
        http_response_code(400);
        echo json_encode(['message' => 'Sorry, your file is too large.']);
        exit;
    }

    if (
        $imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif"
    ) {
        http_response_code(400);
        echo json_encode(['message' => 'Sorry, only JPG, JPEG, PNG & GIF files are allowed.']);
        exit;
    }

    if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
        // Return the path relative to the web root
        // Assuming backend is at /backend/, uploads at /uploads/
        // We need the URL relative to the domain or absolute URL
        // Simple relative path from index.html (which is in /frontend/) to /uploads/
        // ../uploads/filename
        $url = "../uploads/" . basename($target_file);
        echo json_encode(['url' => $url]);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Sorry, there was an error uploading your file.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['message' => 'No file uploaded']);
}
?>