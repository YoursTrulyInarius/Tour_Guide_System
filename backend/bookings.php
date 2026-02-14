<?php
require 'config.php';
session_start();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        get_bookings($pdo);
        break;
    case 'POST':
        create_booking($pdo);
        break;
    case 'PUT':
        update_booking_status($pdo);
        break;
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
        break;
}

function get_bookings($pdo)
{
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['message' => 'Unauthorized']);
        return;
    }

    $role = $_SESSION['role'];
    $user_id = $_SESSION['user_id'];

    $sql = "SELECT bookings.*, tours.title as tour_title, users.username as tourist_name 
            FROM bookings 
            JOIN tours ON bookings.tour_id = tours.id 
            JOIN users ON bookings.tourist_id = users.id";

    if ($role === 'tourist') {
        $sql .= " WHERE bookings.tourist_id = ?";
        $params = [$user_id];
    } elseif ($role === 'guide') {
        $sql .= " WHERE tours.guide_id = ?";
        $params = [$user_id];
    } elseif ($role === 'admin') {
        $params = [];
    } else {
        http_response_code(403);
        echo json_encode(['message' => 'Forbidden']);
        return;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($bookings);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to fetch bookings: ' . $e->getMessage()]);
    }
}

function create_booking($pdo)
{
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'tourist') {
        http_response_code(403);
        echo json_encode(['message' => 'Only tourists can book tours']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['tour_id'], $input['booking_date'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Missing required fields']);
        return;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO bookings (tour_id, tourist_id, booking_date) VALUES (?, ?, ?)");
        $stmt->execute([$input['tour_id'], $_SESSION['user_id'], $input['booking_date']]);
        echo json_encode(['message' => 'Booking created successfully', 'id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Booking failed: ' . $e->getMessage()]);
    }
}

function update_booking_status($pdo)
{
    if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'guide' && $_SESSION['role'] !== 'admin')) {
        http_response_code(403);
        echo json_encode(['message' => 'Unauthorized']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['id'], $input['status'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Missing required fields']);
        return;
    }

    // Guides can only update bookings for their tours
    if ($_SESSION['role'] === 'guide') {
        $stmt = $pdo->prepare("SELECT tours.guide_id FROM bookings JOIN tours ON bookings.tour_id = tours.id WHERE bookings.id = ?");
        $stmt->execute([$input['id']]);
        $booking = $stmt->fetch();

        if (!$booking || $booking['guide_id'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['message' => 'Unauthorized to update this booking']);
            return;
        }
    }

    try {
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->execute([$input['status'], $input['id']]);
        echo json_encode(['message' => 'Booking status updated successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to update booking']);
    }
}
?>