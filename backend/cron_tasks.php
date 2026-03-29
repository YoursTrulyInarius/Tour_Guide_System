<?php
require_once 'config.php';
require_once 'mailer_helper.php';

echo "Starting daily tasks...\n";

// 1. Send Reminders (24h before visit)
try {
    $stmt = $pdo->prepare("
        SELECT b.*, s.slot_name 
        FROM bookings b 
        JOIN time_slots s ON b.time_slot_id = s.id 
        WHERE b.visit_date = DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY) 
        AND b.status = 'paid' 
        AND b.is_reminder_sent = FALSE
    ");
    $stmt->execute();
    $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($reminders as $b) {
        if (sendReminderEmail($b)) {
            $pdo->prepare("UPDATE bookings SET is_reminder_sent = TRUE WHERE id = ?")->execute([$b['id']]);
            echo "Sent reminder to {$b['visitor_email']}\n";
        }
    }
} catch (Exception $e) {
    echo "Reminder Task Error: " . $e->getMessage() . "\n";
}

// 2. Send Feedback Surveys (2 hours after slot end)
try {
    // Note: We check if Current Time is at least 2 hours past the slot's end time
    $stmt = $pdo->prepare("
        SELECT b.* 
        FROM bookings b 
        JOIN time_slots s ON b.time_slot_id = s.id 
        WHERE b.visit_date = CURRENT_DATE 
        AND b.status = 'paid' 
        AND b.is_survey_sent = FALSE
        AND CURRENT_TIME >= DATE_ADD(s.end_time, INTERVAL 2 HOUR)
    ");
    $stmt->execute();
    $surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($surveys as $b) {
        if (sendSurveyEmail($b)) {
            $pdo->prepare("UPDATE bookings SET is_survey_sent = TRUE WHERE id = ?")->execute([$b['id']]);
            echo "Sent survey to {$b['visitor_email']}\n";
        }
    }
} catch (Exception $e) {
    echo "Survey Task Error: " . $e->getMessage() . "\n";
}

echo "Tasks complete.\n";
