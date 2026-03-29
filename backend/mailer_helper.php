<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'config_mail.php';

function sendTicketEmail($pdo, $booking_id) {
    try {
        // Fetch booking details
        $stmt = $pdo->prepare("SELECT b.*, s.slot_name FROM bookings b JOIN time_slots s ON b.time_slot_id = s.id WHERE b.id = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) return false;

        // Fetch tickets
        $stmt = $pdo->prepare("SELECT t.*, tt.name as type_name FROM tickets t JOIN ticket_types tt ON t.ticket_type_id = tt.id WHERE t.booking_id = ?");
        $stmt->execute([$booking_id]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch add-ons
        $stmt = $pdo->prepare("SELECT ba.*, ao.name, ao.price FROM booking_add_ons ba JOIN add_ons ao ON ba.add_on_id = ao.id WHERE ba.booking_id = ?");
        $stmt->execute([$booking_id]);
        $addons = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($booking['visitor_email'], $booking['visitor_name']);

        $mail->isHTML(true);
        $mail->Subject = 'Your Booking for Tour Guide System - ' . $booking_id;
        
        $ticketList = "";
        foreach ($tickets as $t) {
            $ticketList .= "<li><strong>{$t['type_name']} Ticket:</strong> Hash: <code>{$t['qr_code']}</code></li>";
        }

        $addonList = "";
        if (count($addons) > 0) {
            $addonList = "<h3>Your Add-ons:</h3><ul>";
            foreach ($addons as $a) {
                $addonList .= "<li>{$a['name']} x {$a['quantity']}</li>";
            }
            $addonList .= "</ul>";
        }

        $mail->Body = "
        <div style='font-family: sans-serif; color: #1f2937; line-height: 1.6;'>
            <h1 style='color: #2563eb;'>Thank you for your booking!</h1>
            <p>Hi <strong>{$booking['visitor_name']}</strong>,</p>
            <p>Your booking for <strong>Tour Guide System</strong> has been confirmed.</p>
            
            <div style='background: #f3f4f6; padding: 20px; border-radius: 8px;'>
                <p><strong>Booking ID:</strong> {$booking_id}</p>
                <p><strong>Visit Date:</strong> {$booking['visit_date']}</p>
                <p><strong>Time Slot:</strong> {$booking['slot_name']}</p>
                <p><strong>Total Paid:</strong> ₱{$booking['total_amount']}</p>
            </div>

            <h3>Your Tickets:</h3>
            <ul>
                $ticketList
            </ul>

            $addonList

            <p style='color: #6b7280; font-size: 0.9em; margin-top: 30px;'>
                Please present this email at the entrance. Each QR hash corresponds to one ticket.
            </p>
        </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

function sendReminderEmail($booking) {
    global $pdo;
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($booking['visitor_email'], $booking['visitor_name']);

        $mail->isHTML(true);
        $mail->Subject = 'Reminder: Your Tour Guide System booking is tomorrow!';
        
        $mail->Body = "
        <div style='font-family: sans-serif; color: #1f2937;'>
            <h1 style='color: #2563eb;'>Get Ready!</h1>
            <p>Hi <strong>{$booking['visitor_name']}</strong>,</p>
            <p>This is a friendly reminder that your visit is scheduled for tomorrow, <strong>{$booking['visit_date']}</strong>.</p>
            <p><strong>Slot:</strong> {$booking['slot_name']}</p>
            <p>Please have your QR codes ready from your previous confirmation email.</p>
            <p>See you soon!</p>
        </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function sendSurveyEmail($booking) {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($booking['visitor_email'], $booking['visitor_name']);

        $mail->isHTML(true);
        $mail->Subject = 'How was your experience with Tour Guide System?';
        
        $mail->Body = "
        <div style='font-family: sans-serif; color: #1f2937;'>
            <h1 style='color: #f59e0b;'>We value your feedback!</h1>
            <p>Hi <strong>{$booking['visitor_name']}</strong>,</p>
            <p>We hope you enjoyed your visit today.</p>
            <p>Could you take a minute to tell us about your experience? Your feedback helps us grow.</p>
            <a href='#' style='display: inline-block; padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px;'>Share Feedback</a>
        </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
