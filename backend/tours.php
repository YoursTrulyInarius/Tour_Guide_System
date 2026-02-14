<?php
require 'config.php';
session_start();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        get_tours($pdo);
        break;
    case 'POST':
        create_tour($pdo);
        break;
    case 'PUT':
        if (isset($_GET['action']) && $_GET['action'] === 'approve') {
            approve_tour($pdo);
        } else {
            update_tour($pdo);
        }
        break;
    case 'DELETE':
        delete_tour($pdo);
        break;
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
        break;
}

function get_tours($pdo)
{
    $sql = "SELECT tours.*, users.username as guide_name FROM tours JOIN users ON tours.guide_id = users.id";
    $params = [];
    $where = [];

    if (isset($_GET['id'])) {
        $where[] = "tours.id = ?";
        $params[] = $_GET['id'];
    } elseif (isset($_GET['guide_id'])) {
        $where[] = "tours.guide_id = ?";
        $params[] = $_GET['guide_id'];
    } elseif (isset($_GET['search'])) {
        $search = "%" . $_GET['search'] . "%";
        $where[] = "(title LIKE ? OR location LIKE ?)";
        $params[] = $search;
        $params[] = $search;
    }

    // Only show approved tours to public/tourists, unless it's a guide viewing their own or admin
    $is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    $is_guide_own = isset($_SESSION['role']) && $_SESSION['role'] === 'guide' && isset($_GET['guide_id']) && $_GET['guide_id'] == $_SESSION['user_id'];

    if (!$is_admin && !$is_guide_own) {
        $where[] = "is_approved = 1";
    }

    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tours = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($tours);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to fetch tours: ' . $e->getMessage()]);
    }
}

function approve_tour($pdo)
{
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['message' => 'Unauthorized']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Tour ID required']);
        return;
    }

    try {
        $stmt = $pdo->prepare("UPDATE tours SET is_approved = 1 WHERE id = ?");
        $stmt->execute([$input['id']]);
        echo json_encode(['message' => 'Tour approved successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to approve tour']);
    }
}

function create_tour($pdo)
{
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guide') {
        http_response_code(403);
        echo json_encode(['message' => 'Unauthorized']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    // Basic validation
    if (!isset($input['title'], $input['price'], $input['duration'], $input['location'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Missing required fields']);
        return;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO tours (guide_id, title, description, price, duration, location, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $input['title'],
            $input['description'] ?? '',
            $input['price'],
            $input['duration'],
            $input['location'],
            $input['image'] ?? null
        ]);
        echo json_encode(['message' => 'Tour created successfully', 'id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to create tour: ' . $e->getMessage()]);
    }
}

function update_tour($pdo)
{
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guide') {
        http_response_code(403);
        echo json_encode(['message' => 'Unauthorized']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Tour ID required']);
        return;
    }

    // Check ownership
    $stmt = $pdo->prepare("SELECT guide_id FROM tours WHERE id = ?");
    $stmt->execute([$input['id']]);
    $tour = $stmt->fetch();

    if (!$tour || $tour['guide_id'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['message' => 'Unauthorized to update this tour']);
        return;
    }

    $fields = [];
    $params = [];
    foreach (['title', 'description', 'price', 'duration', 'location', 'image'] as $field) {
        if (isset($input[$field])) {
            $fields[] = "$field = ?";
            $params[] = $input[$field];
        }
    }

    if (empty($fields)) {
        echo json_encode(['message' => 'No changes provided']);
        return;
    }

    $params[] = $input['id'];
    $sql = "UPDATE tours SET " . implode(', ', $fields) . " WHERE id = ?";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['message' => 'Tour updated successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to update tour: ' . $e->getMessage()]);
    }
}

function delete_tour($pdo)
{
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guide') {
        http_response_code(403);
        echo json_encode(['message' => 'Unauthorized']);
        return;
    }

    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['message' => 'Tour ID required']);
        return;
    }

    // Check ownership
    $stmt = $pdo->prepare("SELECT guide_id FROM tours WHERE id = ?");
    $stmt->execute([$id]);
    $tour = $stmt->fetch();

    if (!$tour || $tour['guide_id'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['message' => 'Unauthorized to delete this tour']);
        return;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM tours WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['message' => 'Tour deleted successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to delete tour']);
    }
}
?>