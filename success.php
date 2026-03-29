<?php
require_once 'includes/db.php';

$booking_id = isset($_GET['booking_id']) ? $_GET['booking_id'] : null;

if (!$booking_id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT b.*, t.slot_name, a.name as attraction_name 
    FROM bookings b 
    JOIN time_slots t ON b.time_slot_id = t.id 
    JOIN attractions a ON t.attraction_id = a.id 
    WHERE b.id = ?
");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    echo "Invalid Tracking ID.";
    exit;
}

require 'includes/header.php';
?>

<div class="container main-content-wrapper" style="display: flex; justify-content: center; align-items: center; min-height: 70vh;">
    <div class="card animate-fade-in" style="max-width: 600px; width: 100%; text-align: center; border-radius: 2rem; padding: 4rem 2rem;">
        <div style="width: 80px; height: 80px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 2rem auto; color: white; font-size: 2.5rem; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);">
            <i class="fa-solid fa-check"></i>
        </div>
        
        <h1 style="color: var(--secondary); margin-bottom: 0.5rem; font-size: 2.5rem;">Booking Confirmed!</h1>
        <p style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 2rem;">Thank you for choosing YoursTruly Tours to explore <?php echo htmlspecialchars($booking['attraction_name']); ?>.</p>
        
        <div style="background: rgba(248, 250, 252, 0.6); padding: 1.5rem; border-radius: 1rem; border: 1px dashed var(--border); margin-bottom: 2.5rem;">
            <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 1px; margin-bottom: 0.5rem;">Booking Reference</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary); letter-spacing: 2px; font-family: monospace;"><?php echo htmlspecialchars($booking['id']); ?></div>
        </div>
        
        <div style="text-align: left; margin-bottom: 2.5rem;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
                <span style="color: var(--text-muted);">Name</span>
                <span style="font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($booking['visitor_name']); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
                <span style="color: var(--text-muted);">Date</span>
                <span style="font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($booking['visit_date']); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
                <span style="color: var(--text-muted);">Time Slot</span>
                <span style="font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($booking['slot_name']); ?></span>
            </div>
        </div>
        
        <p style="font-size: 0.95rem; color: var(--text-muted); margin-bottom: 2rem;">
            We have sent your e-tickets to <strong style="color: var(--secondary);"><?php echo htmlspecialchars($booking['visitor_email']); ?></strong>.<br>
            Please present the QR codes attached to the email to the staff on the day of your visit.
        </p>
        
        <a href="index.php" class="btn btn-primary" style="width: 100%;">Return to Home</a>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
