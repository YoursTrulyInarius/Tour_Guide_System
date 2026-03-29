<?php
require_once 'config.php';
require_once 'mailer_helper.php';

// Helper to generate a unique QR code hash
function generateQRCode($booking_id) {
    return hash('sha256', $booking_id . time() . 'tour_guide_salt');
}

session_start();

// Simple Rate Limiter (Throttle: 10 requests per minute)
if (!isset($_SESSION['request_count'])) {
    $_SESSION['request_count'] = 0;
    $_SESSION['first_request_time'] = time();
}
if (time() - $_SESSION['first_request_time'] > 60) {
    $_SESSION['request_count'] = 1;
    $_SESSION['first_request_time'] = time();
} else {
    $_SESSION['request_count']++;
}
if ($_SESSION['request_count'] > 10) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many requests. Please slow down.']);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'get_availability':
        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        $attraction_id = isset($_GET['attraction_id']) ? intval($_GET['attraction_id']) : 1;

        try {
            // Get all time slots for the attraction
            $stmt = $pdo->prepare("SELECT id, slot_name, start_time, end_time, max_capacity FROM time_slots WHERE attraction_id = ?");
            $stmt->execute([$attraction_id]);
            $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($slots as &$slot) {
                // Calculate current bookings for this slot on this date
                $check_stmt = $pdo->prepare("
                    SELECT SUM(tt_count) as total_booked 
                    FROM (
                        SELECT COUNT(*) as tt_count 
                        FROM tickets t 
                        JOIN bookings b ON t.booking_id = b.id 
                        WHERE b.visit_date = ? AND b.time_slot_id = ? AND b.status IN ('paid', 'pending')
                        GROUP BY b.id
                    ) as sub
                ");
                // Note: The above is a bit simplified. A better way would be counting ticket entries.
                $ticket_count_stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM tickets t 
                    JOIN bookings b ON t.booking_id = b.id 
                    WHERE b.visit_date = ? AND b.time_slot_id = ? AND b.status IN ('paid', 'pending')
                ");
                $ticket_count_stmt->execute([$date, $slot['id']]);
                $booked = $ticket_count_stmt->fetchColumn();

                $slot['available_capacity'] = $slot['max_capacity'] - $booked;

                // Check for seasonal multiplier
                $seasonal_stmt = $pdo->prepare("
                    SELECT price_multiplier 
                    FROM seasonal_pricing 
                    WHERE attraction_id = ? AND ? BETWEEN start_date AND end_date
                    LIMIT 1
                ");
                $seasonal_stmt->execute([$attraction_id, $date]);
                $multiplier = $seasonal_stmt->fetchColumn();
                $slot['price_multiplier'] = $multiplier ? floatval($multiplier) : 1.00;
            }

            echo json_encode(['success' => true, 'slots' => $slots]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

        break;

    case 'get_addons':
        $attraction_id = isset($_GET['attraction_id']) ? intval($_GET['attraction_id']) : 1;
        try {
            $stmt = $pdo->prepare("SELECT id, name, price, description FROM add_ons WHERE attraction_id = ?");
            $stmt->execute([$attraction_id]);
            $addons = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'addons' => $addons]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_ticket_types':
        $attraction_id = isset($_GET['attraction_id']) ? intval($_GET['attraction_id']) : 1;
        try {
            $stmt = $pdo->prepare("SELECT id, name, base_price as price, description FROM ticket_types WHERE attraction_id = ?");
            $stmt->execute([$attraction_id]);
            $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'ticket_types' => $types]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'reserve':
        // Expecting JSON: { attraction_id, date, time_slot_id, visitor_email, visitor_name, visitor_phone, tickets: [{type_id, quantity}, ...] }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            break;
        }

        $booking_id = bin2hex(random_bytes(16)); // Simple UUID placeholder
        $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        try {
            $pdo->beginTransaction();

            // Calculate total amount from tickets
            $total_amount = 0;
            $ticket_entries = [];

            foreach ($data['tickets'] as $t) {
                $stmt = $pdo->prepare("SELECT tt.base_price, tt.attraction_id FROM ticket_types tt WHERE tt.id = ?");
                $stmt->execute([$t['type_id']]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $base_price = $row['base_price'];
                $attraction_id = $row['attraction_id'];

                // Check for seasonal multiplier
                $seasonal_stmt = $pdo->prepare("
                    SELECT price_multiplier 
                    FROM seasonal_pricing 
                    WHERE attraction_id = ? AND ? BETWEEN start_date AND end_date
                    LIMIT 1
                ");
                $seasonal_stmt->execute([$attraction_id, $data['date']]);
                $multiplier = $seasonal_stmt->fetchColumn();
                $multiplier = $multiplier ? floatval($multiplier) : 1.00;
                
                $final_price = $base_price * $multiplier;

                for ($i = 0; $i < $t['quantity']; $i++) {
                    $total_amount += $final_price;
                    $ticket_entries[] = $t['type_id'];
                }
            }

            // Calculate total amount from add-ons
            $addon_entries = [];
            if (isset($data['addons']) && is_array($data['addons'])) {
                foreach ($data['addons'] as $a) {
                    if ($a['quantity'] > 0) {
                        $stmt = $pdo->prepare("SELECT price, name FROM add_ons WHERE id = ?");
                        $stmt->execute([$a['id']]);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $total_amount += $row['price'] * $a['quantity'];
                        $addon_entries[] = ['id' => $a['id'], 'quantity' => $a['quantity']];
                    }
                }
            }

            // Insert Booking
            $stmt = $pdo->prepare("INSERT INTO bookings (id, visitor_email, visitor_name, visitor_phone, visit_date, time_slot_id, total_amount, status, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
            $stmt->execute([
                $booking_id,
                $data['visitor_email'],
                $data['visitor_name'],
                $data['visitor_phone'],
                $data['date'],
                $data['time_slot_id'],
                $total_amount,
                $expires_at
            ]);

            // Insert placeholder tickets
            foreach ($ticket_entries as $type_id) {
                $qr = generateQRCode($booking_id);
                $stmt = $pdo->prepare("INSERT INTO tickets (booking_id, ticket_type_id, qr_code) VALUES (?, ?, ?)");
                $stmt->execute([$booking_id, $type_id, $qr]);
            }

            // Insert booking add-ons
            foreach ($addon_entries as $ae) {
                $stmt = $pdo->prepare("INSERT INTO booking_add_ons (booking_id, add_on_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$booking_id, $ae['id'], $ae['quantity']]);
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'booking_id' => $booking_id, 'total_amount' => $total_amount]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'confirm':
        // Expecting JSON: { booking_id, paypal_order_id }
        $data = json_decode(file_get_contents('php://input'), true);
        $booking_id = $data['booking_id'];
        $paypal_order_id = isset($data['paypal_order_id']) ? $data['paypal_order_id'] : null;

        try {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'paid', paypal_order_id = ? WHERE id = ? AND status = 'pending'");
            $stmt->execute([$paypal_order_id, $booking_id]);

            if ($stmt->rowCount() > 0) {
                // Send Ticket Email
                sendTicketEmail($pdo, $booking_id);
                echo json_encode(['success' => true, 'message' => 'Booking confirmed and email sent']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Booking not found or already paid/expired']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_booking':
        $id = $_GET['id'];
        try {
            $stmt = $pdo->prepare("SELECT b.*, s.slot_name FROM bookings b JOIN time_slots s ON b.time_slot_id = s.id WHERE b.id = ?");
            $stmt->execute([$id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($booking) {
                $stmt = $pdo->prepare("SELECT t.*, tt.name as type_name FROM tickets t JOIN ticket_types tt ON t.ticket_type_id = tt.id WHERE t.booking_id = ?");
                $stmt->execute([$id]);
                $booking['tickets'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'booking' => $booking]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Booking not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'validate':
        $qr = isset($_GET['qr']) ? $_GET['qr'] : '';
        try {
            $stmt = $pdo->prepare("SELECT t.*, b.visitor_name, tt.name as type_name, b.visit_date FROM tickets t JOIN bookings b ON t.booking_id = b.id JOIN ticket_types tt ON t.ticket_type_id = tt.id WHERE t.qr_code = ?");
            $stmt->execute([$qr]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ticket) {
                echo json_encode(['success' => false, 'message' => 'Invalid Ticket']);
            } else if ($ticket['visit_date'] !== date('Y-m-d')) {
                echo json_encode(['success' => false, 'message' => 'Ticket is for a different date: ' . $ticket['visit_date']]);
            } else if ($ticket['is_scanned']) {
                echo json_encode(['success' => false, 'message' => 'Ticket already used at ' . $ticket['scanned_at']]);
            } else {
                $upd = $pdo->prepare("UPDATE tickets SET is_scanned = TRUE, scanned_at = NOW() WHERE id = ?");
                $upd->execute([$ticket['id']]);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Valid Ticket', 
                    'visitor' => $ticket['visitor_name'],
                    'type' => $ticket['type_name']
                ]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'refund':
        $data = json_decode(file_get_contents('php://input'), true);
        $booking_id = $data['booking_id'];
        try {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'refunded' WHERE id = ? AND status = 'paid'");
            $stmt->execute([$booking_id]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Booking refunded successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Booking not found or already refunded/cancelled']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_all_bookings':
        // For admin dashboard
        try {
            $stmt = $pdo->prepare("SELECT b.*, s.slot_name FROM bookings b JOIN time_slots s ON b.time_slot_id = s.id ORDER BY b.created_at DESC");
            $stmt->execute();
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'bookings' => $bookings]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_all_attractions':
        try {
            $stmt = $pdo->prepare("SELECT id, name, description, location, image_url FROM attractions WHERE is_active = TRUE");
            $stmt->execute();
            $attractions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'attractions' => $attractions]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
