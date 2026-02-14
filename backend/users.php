<?php
require 'config.php';
session_start();

// Only admin can access this module
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        get_users($pdo);
        break;
    case 'PUT':
        update_user_status($pdo);
        break;
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
        break;
}

function get_users($pdo)
{
    try {
        $stmt = $pdo->query("SELECT id, username, email, role, status, created_at FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($users);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to fetch users']);
    }
}

function update_user_status($pdo)
{
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['id'], $input['status'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Missing ID or Status']);
        return;
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$input['status'], $input['id']]);
        echo json_encode(['message' => 'User status updated successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to update user']);
    }
}
?>