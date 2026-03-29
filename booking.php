<?php
require_once 'includes/db.php';

$attraction_id = isset($_GET['attraction_id']) ? intval($_GET['attraction_id']) : 0;
if (!$attraction_id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM attractions WHERE id = ?");
$stmt->execute([$attraction_id]);
$spot = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$spot) {
    echo "Attraction not found.";
    exit;
}

$enable_paypal = true;
require 'includes/header.php';
?>

<div class="hero" style="height: 35vh; min-height: 300px;">
    <img src="<?php echo htmlspecialchars($spot['image_url']); ?>" alt="<?php echo htmlspecialchars($spot['name']); ?>" style="object-fit: cover; width:100%; height:100%; position: absolute; z-index: 1;">
    <div style="position: absolute; width: 100%; height: 100%; background: rgba(15,23,42,0.6); z-index: 2;"></div>
    <div class="hero-content" style="z-index: 3; position: relative;">
        <h1 style="font-size: 3rem;"><?php echo htmlspecialchars($spot['name']); ?></h1>
        <p style="font-size: 1.2rem;"><?php echo htmlspecialchars($spot['description']); ?></p>
        <div style="margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: center;">
            <?php if(!empty($spot['lat']) && !empty($spot['lng'])): ?>
                <a href="https://www.google.com/maps?q=<?php echo $spot['lat']; ?>,<?php echo $spot['lng']; ?>" target="_blank" class="btn btn-sm" style="background: rgba(255,255,255,0.2); color:white; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3);">
                    <i class="fa-solid fa-location-dot"></i> View on Map
                </a>
            <?php endif; ?>
            <?php if(!empty($spot['video_url'])): ?>
                <button onclick="document.getElementById('videoModal').style.display='flex'" class="btn btn-sm" style="background: rgba(255,255,255,0.2); color:white; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3);">
                    <i class="fa-solid fa-play"></i> Watch Video
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Video Modal -->
<?php if(!empty($spot['video_url'])): ?>
<div id="videoModal" class="modal-overlay" onclick="if(event.target==this) this.style.display='none'">
    <div class="modal-box" style="max-width: 800px; background: black; padding: 0; line-height: 0;">
        <?php 
            $video_url = $spot['video_url'];
            if(strpos($video_url, 'youtube.com/watch?v=') !== false) {
                $video_url = str_replace('watch?v=', 'embed/', $video_url);
            } elseif(strpos($video_url, 'youtu.be/') !== false) {
                $video_url = str_replace('youtu.be/', 'youtube.com/embed/', $video_url);
            }
        ?>
        <iframe width="100%" height="450" src="<?php echo htmlspecialchars($video_url); ?>" frameborder="0" allowfullscreen></iframe>
    </div>
</div>
<?php endif; ?>

<main class="container main-content-wrapper">
    <div class="booking-container">
        
        <!-- Left Column: Booking Steps -->
        <div class="booking-steps">
            <div class="section-header">
                <a href="index.php" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left"></i> Back to Destinations
                </a>
                <h1 class="page-title">Book Your Experience</h1>
            </div>

            <!-- STEP 1: Date & Time -->
            <div class="card animate-fade-in" style="animation-delay: 0.1s;">
                <h2 class="card-title"><i class="fa-regular fa-calendar" style="color: var(--primary); margin-right: 0.5rem;"></i> 1. Select Date & Time</h2>
                
                <div class="form-group">
                    <label>Date of Visit</label>
                    <input type="date" id="visitDate" min="<?php echo date('Y-m-d'); ?>" onchange="fetchSlots()">
                </div>
                
                <div id="slotsContainer" class="slot-grid" style="margin-top: 1rem; display: none;">
                    <!-- Slots will be rendered here via JS -->
                </div>
            </div>

            <!-- STEP 2: Tickets -->
            <div class="card animate-fade-in" id="ticketSection" style="animation-delay: 0.2s; display: none;">
                <h2 class="card-title"><i class="fa-solid fa-ticket" style="color: var(--primary); margin-right: 0.5rem;"></i> 2. Select Tickets</h2>
                <div id="ticketsContainer">
                    <!-- Tickets via JS -->
                </div>
            </div>

            <!-- STEP 3: Add-ons -->
            <div class="card animate-fade-in" id="addonSection" style="animation-delay: 0.3s; display: none;">
                <h2 class="card-title"><i class="fa-solid fa-plus-circle" style="color: var(--primary); margin-right: 0.5rem;"></i> 3. Optional Experiences</h2>
                <div id="addonsContainer">
                    <!-- Addons via JS -->
                </div>
            </div>

            <!-- STEP 4: Checkout Details -->
            <div class="card animate-fade-in" id="checkoutSection" style="animation-delay: 0.4s; display: none;">
                <h2 class="card-title"><i class="fa-regular fa-user" style="color: var(--primary); margin-right: 0.5rem;"></i> 4. Visitor Details</h2>
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="visitorName" placeholder="John Doe" required onchange="validateForm()">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" id="visitorEmail" placeholder="john@example.com" required onchange="validateForm()">
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" id="visitorPhone" placeholder="+1 234 567 8900" required onchange="validateForm()">
                </div>

                <!-- Anti-Fraud: Honeypot -->
                <div class="form-group" style="display:none;" aria-hidden="true">
                    <label>Website (Leave Empty)</label>
                    <input type="text" id="website_url" name="website_url" tabindex="-1" autocomplete="off">
                </div>

                <!-- Anti-Fraud: Simulated reCAPTCHA -->
                <div class="form-group" style="display: flex; margin-top: 1.5rem; align-items: center;">
                    <div style="border: 1px solid #d3d3d3; padding: 0.5rem 1rem; border-radius: 3px; display: flex; align-items: center; gap: 1rem; background: #f9f9f9; width: 100%; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" id="recaptchaCheck" required onchange="validateForm()" style="width: 24px; height: 24px;">
                            <span style="font-family: Roboto, sans-serif; font-size: 14px; font-weight: 500;">I'm not a robot</span>
                        </div>
                        <img src="https://www.gstatic.com/recaptcha/api2/logo_48.png" style="height: 32px;" alt="reCAPTCHA logo">
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Summary & PayPal -->
        <!-- Right Column: Summary & Payment -->
        <div class="summary-sidebar animate-fade-in" style="animation-delay: 0.5s;">
            <div class="card" style="border: 2px solid var(--primary); padding: 1.5rem;">
                <h2 class="card-title" style="border-bottom: 2px solid var(--border); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; font-size: 1.5rem;">
                    <i class="fa-solid fa-receipt" style="color: var(--primary);"></i> Booking Details
                </h2>
                
                <div id="summaryContent" style="color: var(--text-main);">
                    <div style="text-align: center; padding: 2rem 0; color: var(--text-muted);">
                        Select a date and time to begin.
                    </div>
                </div>

                <div id="paymentSelection" style="margin-top: 2rem; display: none; border-top: 1px solid var(--border); padding-top: 1.5rem;">
                    <label style="margin-bottom: 1rem; display: block; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; color: var(--text-muted); letter-spacing: 1px;">Select Payment Method</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                        <div class="payment-opt" id="optGcash" onclick="selectPayment('gcash')" style="border: 2px solid var(--border); padding: 1rem; border-radius: 1rem; text-align: center; cursor: pointer; transition: all 0.2s;">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/cb/GCash_logo.svg/1200px-GCash_logo.svg.png" style="height: 20px; margin-bottom: 0.5rem; object-fit: contain;" alt="GCash">
                            <div style="font-size: 0.8rem; font-weight: 600;">GCash</div>
                        </div>
                        <div class="payment-opt" id="optPaypal" onclick="selectPayment('paypal')" style="border: 2px solid var(--border); padding: 1rem; border-radius: 1rem; text-align: center; cursor: pointer; transition: all 0.2s;">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b5/PayPal.svg/1200px-PayPal.svg.png" style="height: 20px; margin-bottom: 0.5rem; object-fit: contain;" alt="PayPal">
                            <div style="font-size: 0.8rem; font-weight: 600;">PayPal</div>
                        </div>
                    </div>

                    <!-- GCash Payment UI -->
                    <div id="gcashUI" style="display: none;">
                        <div style="background: #f1f5f9; padding: 1rem; border-radius: 1rem; margin-bottom: 1.5rem;">
                            <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem; background: #e2e8f0; padding: 0.25rem; border-radius: 0.5rem;">
                                <button onclick="toggleGcashMode('scan')" id="modeScan" style="flex: 1; padding: 0.5rem; border: none; border-radius: 0.35rem; font-size: 0.8rem; font-weight: 600; cursor: pointer; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">Scan QR</button>
                                <button onclick="toggleGcashMode('number')" id="modeNumber" style="flex: 1; padding: 0.5rem; border: none; border-radius: 0.35rem; font-size: 0.8rem; font-weight: 600; cursor: pointer; background: transparent;">Send to Number</button>
                            </div>

                            <div id="gcashScan" style="text-align: center; margin-bottom: 1rem;">
                                <div style="background: white; padding: 1rem; border-radius: 0.5rem; display: inline-block; margin-bottom: 0.5rem;">
                                    <!-- Use a generic QR placeholder for now -->
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=GCash-09123456789" style="width: 150px; height: 150px;" alt="GCash QR">
                                </div>
                                <p style="font-size: 0.75rem; color: var(--text-muted);">Scan this QR code using your GCash app to pay.</p>
                            </div>

                            <div id="gcashNumber" style="display: none; text-align: center; margin-bottom: 1rem; padding: 1rem 0;">
                                <div style="font-size: 1.25rem; font-weight: 800; color: #1e293b; margin-bottom: 0.25rem;">0912 345 6789</div>
                                <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Account Name: YoursTruly Tours</div>
                                <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 1rem;">Send the exact total amount to this GCash number.</p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label style="font-size: 0.75rem; text-transform: uppercase;">Transaction Reference Number</label>
                            <input type="text" id="gcashRef" placeholder="12-digit number" style="font-family: monospace; letter-spacing: 1px;">
                        </div>

                        <button onclick="confirmGcashPayment()" class="btn btn-primary" style="width: 100%; padding: 1rem;">
                            <i class="fa-solid fa-check-circle"></i> Confirm Payment
                        </button>
                    </div>

                    <!-- PayPal UI -->
                    <div id="paypalUI" style="display: none;">
                        <div id="paypal-button-container"></div>
                    </div>
                </div>
                
                <div id="loadingOverlay" style="display: none; text-align: center; padding: 2rem 0;">
                    <div class="spinner"></div><p style="margin-top: 1rem; font-weight: 600;">Processing your booking...</p>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
const attractionId = <?php echo $attractionId = $spot['id']; ?>;
let selectedSlot = null;
let currentTickets = [];
let currentAddons = [];
let quantities = {};
let addonSelections = {};
let selectedPaymentMethod = null;
let gcashMode = 'scan';

async function fetchSlots() {
    const date = document.getElementById('visitDate').value;
    if(!date) return;
    
    document.getElementById('slotsContainer').style.display = 'grid';
    document.getElementById('slotsContainer').innerHTML = '<div style="grid-column: 1/-1; text-align: center;">Loading availability...</div>';
    
    const res = await fetch(`backend/api_bookings.php?action=get_availability&attraction_id=${attractionId}&date=${date}`);
    const data = await res.json();
    
    if(!data.success) {
        document.getElementById('slotsContainer').innerHTML = `<div style="grid-column: 1/-1; text-align: center; color: #ef4444; padding: 1rem; background: #fef2f2; border-radius: 0.5rem; border: 1px solid #fee2e2;">
            <i class="fa-solid fa-circle-exclamation"></i> ${data.message}
        </div>`;
        return;
    }
    
    if(data.success) {
        let html = '';
        data.slots.forEach(slot => {
            const isSoldOut = slot.available_capacity <= 0;
            html += `
            <div class="slot-item ${isSoldOut ? 'disabled' : ''}" 
                 onclick="${isSoldOut ? '' : `selectSlot(${JSON.stringify(slot).replace(/"/g, '&quot;')})`}"
                 id="slot_${slot.id}">
                <div style="font-weight: bold; font-size: 1.1rem; color: var(--text-main);">${slot.slot_name}</div>
                <div style="font-size: 0.85rem; color: var(--text-muted);">${slot.start_time.substring(0,5)} - ${slot.end_time.substring(0,5)}</div>
                <div style="margin-top: 0.5rem; font-size: 0.8rem; font-weight: 600; color: ${isSoldOut ? '#ef4444' : '#10b981'};">
                    ${isSoldOut ? 'Sold Out' : `${slot.available_capacity} spots left`}
                </div>
                ${slot.price_multiplier !== 1 ? `<div style="font-size: 0.75rem; color: var(--accent); margin-top: 0.2rem;"><i class="fa-solid fa-bolt"></i> Peak Pricing</div>` : ''}
            </div>`;
        });
        document.getElementById('slotsContainer').innerHTML = html;
        if(selectedSlot) { // keep selection visual if still valid
            selectSlot(selectedSlot);
        }
    }
}

async function selectSlot(slotObj) {
    selectedSlot = slotObj;
    document.querySelectorAll('.slot-item').forEach(el => el.classList.remove('selected'));
    document.getElementById(`slot_${slotObj.id}`).classList.add('selected');
    
    // Load Tickets & Addons
    document.getElementById('ticketSection').style.display = 'block';
    document.getElementById('addonSection').style.display = 'block';
    
    if(currentTickets.length === 0) {
        const resT = await fetch(`backend/api_bookings.php?action=get_ticket_types&attraction_id=${attractionId}`);
        const dataT = await resT.json();
        currentTickets = dataT.ticket_types;
        
        const resA = await fetch(`backend/api_bookings.php?action=get_addons&attraction_id=${attractionId}`);
        const dataA = await resA.json();
        currentAddons = dataA.addons;
    }
    
    renderTickets();
    renderAddons();
    updateSummary();
}

function renderTickets() {
    let html = '';
    currentTickets.forEach(t => {
        const qty = quantities[t.id] || 0;
        const adjustedPrice = t.price * (selectedSlot ? selectedSlot.price_multiplier : 1);
        html += `
        <div class="ticket-type-item">
            <div>
                <h3 style="font-size: 1.1rem; margin:0;">${t.name}</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin:0;">${t.description}</p>
                <div style="font-weight: bold; color: var(--primary); margin-top: 0.25rem;">₱${adjustedPrice.toFixed(2)}</div>
            </div>
            <div class="stepper">
                <button onclick="changeQty(${t.id}, -1)">-</button>
                <span style="font-weight: 600; width: 1.5rem; text-align: center;">${qty}</span>
                <button onclick="changeQty(${t.id}, 1)">+</button>
            </div>
        </div>`;
    });
    document.getElementById('ticketsContainer').innerHTML = html;
}

function renderAddons() {
    let html = '';
    currentAddons.forEach(a => {
        const qty = addonSelections[a.id] || 0;
        html += `
        <div class="ticket-type-item" style="background: rgba(248, 250, 252, 0.5); padding: 1rem; border-radius: 0.75rem; margin-bottom: 0.5rem; border: 1px solid var(--border);">
            <div>
                <h3 style="font-size: 1.1rem; margin:0;">${a.name}</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin:0;">${a.description}</p>
                <div style="font-weight: bold; color: var(--accent); margin-top: 0.25rem;">₱${parseFloat(a.price).toFixed(2)}</div>
            </div>
            <div class="stepper">
                <button onclick="changeAddon(${a.id}, -1)">-</button>
                <span style="font-weight: 600; width: 1.5rem; text-align: center;">${qty}</span>
                <button onclick="changeAddon(${a.id}, 1)">+</button>
            </div>
        </div>`;
    });
    if(currentAddons.length === 0) html = "<p>No add-ons available.</p>";
    document.getElementById('addonsContainer').innerHTML = html;
}

function changeQty(id, delta) {
    if(!quantities[id]) quantities[id] = 0;
    quantities[id] += delta;
    if(quantities[id] < 0) quantities[id] = 0;
    renderTickets();
    updateSummary();
}

function changeAddon(id, delta) {
    if(!addonSelections[id]) addonSelections[id] = 0;
    addonSelections[id] += delta;
    if(addonSelections[id] < 0) addonSelections[id] = 0;
    renderAddons();
    updateSummary();
}

function updateSummary() {
    let totalTickets = 0;
    let totalPrice = 0;
    let summaryHtml = '';
    
    // 1. Location Section
    summaryHtml += `
    <div style="margin-bottom: 1.5rem;">
        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
            <i class="fa-solid fa-location-dot" style="color: var(--primary);"></i> Location
        </div>
        <div style="font-weight: 600; font-size: 1.1rem; color: var(--secondary);"><?php echo htmlspecialchars($spot['name']); ?></div>
    </div>`;

    // 2. Booking Info Section
    summaryHtml += `
    <div style="margin-bottom: 1.5rem;">
        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
            <i class="fa-regular fa-calendar-check" style="color: var(--primary);"></i> Booking Info
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
            <span style="color: var(--text-muted);">Date:</span>
            <span style="font-weight: 600;">${document.getElementById('visitDate').value}</span>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span style="color: var(--text-muted);">Time Slot:</span>
            <span style="font-weight: 600;">${selectedSlot.slot_name} (${selectedSlot.start_time.substring(0,5)})</span>
        </div>
    </div>`;

    summaryHtml += `<hr style="border: none; border-top: 1px solid var(--border); margin: 1.5rem 0;">`;

    // 3. Ticket Breakdown Section
    let breakdownHtml = '';
    currentTickets.forEach(t => {
        const qty = quantities[t.id] || 0;
        if(qty > 0) {
            totalTickets += qty;
            const adjustedPrice = t.price * selectedSlot.price_multiplier;
            const lineTotal = qty * adjustedPrice;
            totalPrice += lineTotal;
            breakdownHtml += `
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: var(--text-muted);">${qty}x ${t.name}</span>
                <span style="font-weight: 600;">₱${lineTotal.toFixed(2)}</span>
            </div>`;
        }
    });

    if (totalTickets > 0) {
        summaryHtml += `
        <div style="margin-bottom: 1.5rem;">
            <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
                <i class="fa-solid fa-ticket" style="color: var(--primary);"></i> Ticket Breakdown
            </div>
            ${breakdownHtml}
        </div>`;
    }

    // 4. Addons
    let addonHtml = '';
    currentAddons.forEach(a => {
        const qty = addonSelections[a.id] || 0;
        if(qty > 0) {
            const lineTotal = qty * parseFloat(a.price);
            totalPrice += lineTotal;
            addonHtml += `
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.85rem;">
                <span style="color: var(--text-muted);">+ ${qty}x ${a.name}</span>
                <span style="font-weight: 600;">₱${lineTotal.toFixed(2)}</span>
            </div>`;
        }
    });

    if (addonHtml) {
        summaryHtml += `
        <div style="margin-bottom: 1.5rem;">
            ${addonHtml}
        </div>`;
    }

    // 5. Payment Section (Total)
    if(totalTickets > 0) {
        document.getElementById('checkoutSection').style.display = 'block';
        
        const taxRate = 0.12; 
        const serviceChargeRate = 0.10;
        // Assuming totalPrice already includes everything or is the base
        // Let's just show it nicely
        
        summaryHtml += `
        <hr style="border: none; border-top: 1px solid var(--border); margin: 1.5rem 0;">
        <div style="margin-top: 1rem;">
            <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.4rem;">
                <i class="fa-solid fa-credit-card" style="color: var(--primary);"></i> Payment
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 1.5rem; font-weight: 800; color: var(--secondary);">
                <span>Total Paid</span>
                <span style="color: var(--primary);">₱${totalPrice.toFixed(2)}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted);">
                <span>Inc. 12% Tax & 10% Service Charge</span>
            </div>
        </div>`;

        document.getElementById('summaryContent').innerHTML = summaryHtml;
        document.getElementById('summaryContent').style.textAlign = 'left';
        validateForm();
    } else {
        document.getElementById('checkoutSection').style.display = 'none';
        document.getElementById('paymentSelection').style.display = 'none';
        document.getElementById('summaryContent').innerHTML = '<div style="color: var(--text-muted); text-align: center; padding: 2rem 0;">Select tickets to continue.</div>';
    }
}

function validateForm() {
    const name = document.getElementById('visitorName').value;
    const email = document.getElementById('visitorEmail').value;
    const phone = document.getElementById('visitorPhone').value;
    const recaptcha = document.getElementById('recaptchaCheck') ? document.getElementById('recaptchaCheck').checked : true;
    
    let totalTickets = 0;
    Object.values(quantities).forEach(v => totalTickets+=v);

    if(name && email && phone && totalTickets > 0 && recaptcha) {
        document.getElementById('paymentSelection').style.display = 'block';
    } else {
        document.getElementById('paymentSelection').style.display = 'none';
    }
}

function selectPayment(method) {
    selectedPaymentMethod = method;
    document.querySelectorAll('.payment-opt').forEach(opt => {
        opt.style.borderColor = 'var(--border)';
        opt.style.background = 'transparent';
    });
    
    if (method === 'gcash') {
        document.getElementById('optGcash').style.borderColor = 'var(--primary)';
        document.getElementById('optGcash').style.background = 'rgba(79, 70, 229, 0.05)';
        document.getElementById('gcashUI').style.display = 'block';
        document.getElementById('paypalUI').style.display = 'none';
    } else {
        document.getElementById('optPaypal').style.borderColor = 'var(--primary)';
        document.getElementById('optPaypal').style.background = 'rgba(79, 70, 229, 0.05)';
        document.getElementById('gcashUI').style.display = 'none';
        document.getElementById('paypalUI').style.display = 'block';
    }
}

function toggleGcashMode(mode) {
    gcashMode = mode;
    if (mode === 'scan') {
        document.getElementById('modeScan').style.background = 'white';
        document.getElementById('modeScan').style.boxShadow = '0 2px 4px rgba(0,0,0,0.05)';
        document.getElementById('modeNumber').style.background = 'transparent';
        document.getElementById('modeNumber').style.boxShadow = 'none';
        document.getElementById('gcashScan').style.display = 'block';
        document.getElementById('gcashNumber').style.display = 'none';
    } else {
        document.getElementById('modeScan').style.background = 'transparent';
        document.getElementById('modeScan').style.boxShadow = 'none';
        document.getElementById('modeNumber').style.background = 'white';
        document.getElementById('modeNumber').style.boxShadow = '0 2px 4px rgba(0,0,0,0.05)';
        document.getElementById('gcashScan').style.display = 'none';
        document.getElementById('gcashNumber').style.display = 'block';
    }
}

// PayPal Integration
paypal.Buttons({
    createOrder: function(data, actions) {
        // Calculate dynamic total here as source of truth for PayPal popup
        let tp = 0;
        currentTickets.forEach(t => {
            const q = quantities[t.id] || 0;
            const ap = t.price * selectedSlot.price_multiplier;
            tp += q * ap;
        });
        currentAddons.forEach(a => {
            const q = addonSelections[a.id] || 0;
            tp += q * parseFloat(a.price);
        });
        
        return actions.order.create({
            purchase_units: [{
                amount: { value: tp.toFixed(2) }
            }]
        });
    },
    onApprove: function(data, actions) {
        document.getElementById('paymentSelection').style.display = 'none';
        document.getElementById('loadingOverlay').style.display = 'block';

        const payload = {
            attraction_id: attractionId,
            date: document.getElementById('visitDate').value,
            time_slot_id: selectedSlot.id,
            visitor_name: document.getElementById('visitorName').value,
            visitor_email: document.getElementById('visitorEmail').value,
            visitor_phone: document.getElementById('visitorPhone').value,
            website_url: document.getElementById('website_url').value,
            payment_method: 'paypal',
            tickets: Object.entries(quantities).filter(([k,v]) => v>0).map(([k,v]) => ({type_id: parseInt(k), quantity: v})),
            addons: Object.entries(addonSelections).filter(([k,v]) => v>0).map(([k,v]) => ({id: parseInt(k), quantity: v}))
        };

        // 1. Reserve via backend
        fetch('backend/api_bookings.php?action=reserve', {
            method: 'POST',
            body: JSON.stringify(payload)
        }).then(res => res.json()).then(reserveData => {
            if(reserveData.success) {
                // 2. Confirm payment
                fetch('backend/api_bookings.php?action=confirm', {
                    method: 'POST',
                    body: JSON.stringify({ booking_id: reserveData.booking_id, paypal_order_id: data.orderID })
                }).then(res => res.json()).then(confirmData => {
                    if(confirmData.success) {
                        window.location.href = `success.php?booking_id=${reserveData.booking_id}`;
                    } else {
                        Swal.fire('Warning', 'Payment successful but confirmation failed: ' + confirmData.message, 'warning');
                        document.getElementById('loadingOverlay').style.display = 'none';
                    }
                });
            } else {
                Swal.fire('Error', 'Reservation failed: ' + reserveData.message, 'error');
                document.getElementById('loadingOverlay').style.display = 'none';
            }
        });
    }
}).render('#paypal-button-container');

async function confirmGcashPayment() {
    const ref = document.getElementById('gcashRef').value;
    if (ref.length < 8) {
        Swal.fire('Error', 'Please enter a valid GCash reference number.', 'error');
        return;
    }

    document.getElementById('paymentSelection').style.display = 'none';
    document.getElementById('loadingOverlay').style.display = 'block';

    const payload = {
        attraction_id: attractionId,
        date: document.getElementById('visitDate').value,
        time_slot_id: selectedSlot.id,
        visitor_name: document.getElementById('visitorName').value,
        visitor_email: document.getElementById('visitorEmail').value,
        visitor_phone: document.getElementById('visitorPhone').value,
        website_url: document.getElementById('website_url').value,
        payment_method: 'gcash',
        tickets: Object.entries(quantities).filter(([k,v]) => v>0).map(([k,v]) => ({type_id: parseInt(k), quantity: v})),
        addons: Object.entries(addonSelections).filter(([k,v]) => v>0).map(([k,v]) => ({id: parseInt(k), quantity: v}))
    };

    const reserveRes = await fetch('backend/api_bookings.php?action=reserve', {
        method: 'POST',
        body: JSON.stringify(payload)
    });
    const reserveData = await reserveRes.json();

    if (reserveData.success) {
        const confirmRes = await fetch('backend/api_bookings.php?action=confirm', {
            method: 'POST',
            body: JSON.stringify({ booking_id: reserveData.booking_id, gcash_reference: ref })
        });
        const confirmData = await confirmRes.json();
        
        if (confirmData.success) {
             window.location.href = `success.php?booking_id=${reserveData.booking_id}`;
        } else {
            Swal.fire('Error', 'Failed to confirm GCash payment: ' + confirmData.message, 'error');
            document.getElementById('loadingOverlay').style.display = 'none';
            document.getElementById('paymentSelection').style.display = 'block';
        }
    } else {
        Swal.fire('Error', 'Reservation failed: ' + reserveData.message, 'error');
        document.getElementById('loadingOverlay').style.display = 'none';
        document.getElementById('paymentSelection').style.display = 'block';
    }
}

</script>

<?php require 'includes/footer.php'; ?>
