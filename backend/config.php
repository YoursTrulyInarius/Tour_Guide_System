<?php
// config.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

$host = 'localhost';
$db_name = 'tour_guide_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Check if database exists, if not, wait for it to be created or fail gracefully
    // Ideally, the database.sql should be imported first.
    http_response_code(500);
    echo json_encode(["message" => "Connection failed: " . $e->getMessage()]);
    exit;
}
?>
