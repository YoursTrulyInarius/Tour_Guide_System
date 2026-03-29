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

<div class="container main-content-wrapper" style="display: flex; justify-content: center; align-items: center; min-height: 80vh; padding: 2rem;">
    <div class="card animate-fade-in" style="max-width: 600px; width: 100%; border-radius: 2rem; padding: 0; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);">
        
        <!-- Header -->
        <div style="background: #10b981; padding: 3rem 2rem; text-align: center; color: white;">
            <div style="width: 70px; height: 70px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem auto; font-size: 2.5rem; backdrop-filter: blur(10px);">
                <i class="fa-solid fa-check"></i>
            </div>
            <h1 style="color: white; margin-bottom: 0.5rem; font-size: 2.5rem; letter-spacing: -1px;">Booking Confirmed! 🎉</h1>
            <p style="opacity: 0.9; font-size: 1.1rem;">Thank you, Your adventure awaits at <?php echo htmlspecialchars($booking['attraction_name']); ?>.</p>
        </div>

        <div style="padding: 2.5rem;">
            <!-- Reference Section -->
            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 1.25rem; border: 1px dashed var(--border); margin-bottom: 2.5rem; text-align: center;">
                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 1px; margin-bottom: 0.5rem;">Booking Reference</div>
                <div style="font-size: 1.75rem; font-weight: 800; color: var(--primary); letter-spacing: 2px; font-family: 'Courier New', Courier, monospace;"><?php echo htmlspecialchars($booking['id']); ?></div>
            </div>

            <!-- Details Grid -->
            <div style="display: grid; gap: 2rem;">
                
                <!-- Location -->
                <div>
                    <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-location-dot" style="color: var(--primary);"></i> Location
                    </div>
                    <div style="font-weight: 600; font-size: 1.1rem; color: var(--secondary);"><?php echo htmlspecialchars($booking['attraction_name']); ?></div>
                </div>

                <!-- Customer Info -->
                <div>
                    <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-user" style="color: var(--primary);"></i> Customer
                    </div>
                    <div style="display: grid; gap: 0.5rem;">
                        <div style="display: flex; justify-content: space-between;"><span style="color: var(--text-muted);">Name:</span> <span style="font-weight: 600;"><?php echo htmlspecialchars($booking['visitor_name']); ?></span></div>
                        <div style="display: flex; justify-content: space-between;"><span style="color: var(--text-muted);">Email:</span> <span style="font-weight: 600;"><?php echo htmlspecialchars($booking['visitor_email']); ?></span></div>
                        <div style="display: flex; justify-content: space-between;"><span style="color: var(--text-muted);">Phone:</span> <span style="font-weight: 600;"><?php echo htmlspecialchars($booking['visitor_phone']); ?></span></div>
                    </div>
                </div>

                <!-- Booking Info -->
                <div>
                    <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-regular fa-calendar-check" style="color: var(--primary);"></i> Booking Info
                    </div>
                    <div style="display: grid; gap: 0.5rem;">
                        <div style="display: flex; justify-content: space-between;"><span style="color: var(--text-muted);">Date:</span> <span style="font-weight: 600;"><?php echo date('F j, Y', strtotime($booking['visit_date'])); ?></span></div>
                        <div style="display: flex; justify-content: space-between;"><span style="color: var(--text-muted);">Time Slot:</span> <span style="font-weight: 600;"><?php echo htmlspecialchars($booking['slot_name']); ?></span></div>
                    </div>
                </div>

                <hr style="border: none; border-top: 1px solid var(--border);">

                <!-- Payment Status -->
                <div style="background: #fcfcfc; border-radius: 1rem;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-credit-card" style="color: var(--primary);"></i> Payment
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <span style="font-size: 1.5rem; font-weight: 800; color: var(--secondary);">Total Paid</span>
                        <span style="font-size: 1.5rem; font-weight: 800; color: var(--primary);">₱<?php echo number_format($booking['total_amount'], 2); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.85rem; color: var(--text-muted);"><?php echo strtoupper($booking['payment_method'] ?? 'PAYPAL'); ?> TRANSACTION</span>
                        <span style="background: #dcfce7; color: #166534; font-size: 0.7rem; font-weight: 800; padding: 0.25rem 0.75rem; border-radius: 1rem; text-transform: uppercase;">
                            <i class="fa-solid fa-circle-check"></i> <?php echo $booking['status']; ?>
                        </span>
                    </div>
                    
                    <?php if(!empty($booking['gcash_reference'])): ?>
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed var(--border); font-size: 0.85rem;">
                        <span style="color: var(--text-muted);">GCash Reference:</span>
                        <span style="font-family: monospace; font-weight: 600; color: var(--secondary);"><?php echo htmlspecialchars($booking['gcash_reference']); ?></span>
                    </div>
                    <?php elseif(!empty($booking['paypal_order_id'])): ?>
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed var(--border); font-size: 0.85rem;">
                        <span style="color: var(--text-muted);">PayPal Order ID:</span>
                        <span style="font-family: monospace; font-weight: 600; color: var(--secondary);"><?php echo htmlspecialchars($booking['paypal_order_id']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="margin-top: 3rem; text-align: center;">
                <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 2rem;">
                    A confirmation email with your e-tickets has been sent to your email address.
                </p>
                <a href="index.php" class="btn btn-primary" style="width: 100%; padding: 1.25rem; font-weight: 700; border-radius: 1rem; box-shadow: 0 10px 20px rgba(79, 70, 229, 0.2);">
                    Done & Return Home
                </a>
                <button onclick="window.print()" class="btn btn-ghost" style="margin-top: 1rem; width: 100%; border: 1px solid var(--border); border-radius: 1rem;">
                    <i class="fa-solid fa-print"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
