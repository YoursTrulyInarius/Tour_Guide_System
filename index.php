<?php
require_once 'includes/db.php';

// Fetch attractions with prices
$stmt = $pdo->query("
    SELECT a.*, 
        MAX(CASE WHEN tt.name = 'Adult' THEN tt.base_price END) as price_adult,
        MAX(CASE WHEN tt.name = 'Child' THEN tt.base_price END) as price_child,
        MAX(CASE WHEN tt.name = 'Senior' THEN tt.base_price END) as price_senior
    FROM attractions a 
    LEFT JOIN ticket_types tt ON a.id = tt.attraction_id 
    WHERE a.is_active = 1
    GROUP BY a.id
    ORDER BY a.name ASC
");
$attractions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch time slots for display
$stmt = $pdo->query("SELECT attraction_id, slot_name, start_time, end_time FROM time_slots ORDER BY start_time ASC");
$all_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
$slots_map = [];
foreach($all_slots as $s) {
    $slots_map[$s['attraction_id']][] = $s;
}

// Fetch images for gallery
$attraction_ids = array_column($attractions, 'id');
$gallery = [];
if (!empty($attraction_ids)) {
    $in = str_repeat('?,', count($attraction_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT attraction_id, image_url FROM attraction_images WHERE attraction_id IN ($in)");
    $stmt->execute($attraction_ids);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $gallery[$row['attraction_id']][] = $row['image_url'];
    }
}

$enable_paypal = true;
require 'includes/header.php';
?>

<div class="hero hero-landing">
    <div class="hero-content animate-fade-in">
        <h1>Explore Your Next Adventure</h1>
        <p>Discover breathtaking tourist spots and book your experience in under 60 seconds.</p>
    </div>
</div>

<main class="container main-content-wrapper section-overlap">
    <div class="section-header">
        <h2 class="page-title">Top Rated Spots</h2>
    </div>

    <div class="spots-grid">
        <?php foreach ($attractions as $spot): 
            $spot_images = $gallery[$spot['id']] ?? [$spot['image_url']];
        ?>
            <div class="spot-card-modern animate-fade-in" id="spot-<?php echo $spot['id']; ?>">
                <div class="spot-image-wrapper">
                    <img src="<?php echo htmlspecialchars($spot['image_url']); ?>" alt="<?php echo htmlspecialchars($spot['name']); ?>">
                    <div class="spot-badge">
                        <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($spot['location']); ?>
                    </div>
                </div>
                <div class="spot-content">
                    <h3><?php echo htmlspecialchars($spot['name']); ?></h3>
                    <div style="color: var(--primary); font-weight: 700; margin-bottom: 0.5rem;">
                        ₱<?php echo number_format($spot['price_adult'] ?? 0, 2); ?> <small style="color:var(--text-muted); font-weight:400;">Adult</small>
                    </div>
                    <p><?php echo htmlspecialchars($spot['description']); ?></p>
                    <div class="spot-card-footer">
                        <button onclick='openViewModal(<?php echo json_encode($spot); ?>, <?php echo json_encode($spot_images); ?>, <?php echo json_encode($slots_map[$spot['id']] ?? []); ?>)' class="btn btn-ghost btn-sm">
                            <i class="fa-solid fa-circle-info"></i> Details
                        </button>
                        <button onclick='openBookingModal(<?php echo json_encode($spot); ?>)' class="btn btn-primary btn-sm">
                            Book Now
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if(empty($attractions)): ?>
        <div class="card text-center" style="padding: 4rem;">
            <p style="color: var(--text-muted); font-size: 1.2rem;">No spots currently available. Please check back later.</p>
        </div>
    <?php endif; ?>
</main>

<!-- Detailed View Modal -->
<div id="viewModal" class="modal-overlay" onclick="if(event.target==this) closeModals()">
    <div class="modal-box" style="max-width: 900px;">
        <div class="modal-header">
            <h2 id="viewTitle" style="margin:0;">Spot Details</h2>
            <button class="modal-close-btn" onclick="closeModals()">&times;</button>
        </div>
        <div class="modal-body" style="padding:0;">
            <div class="view-modal-layout">
                <div class="view-gallery">
                    <div class="view-carousel">
                        <div id="viewCarouselSlides" class="carousel-slides"></div>
                        <div id="viewCarouselDots" class="carousel-dots"></div>
                        <button class="carousel-btn carousel-prev" id="viewCarouselPrev" onclick="moveViewCarousel(-1)"><i class="fa-solid fa-chevron-left"></i></button>
                        <button class="carousel-btn carousel-next" id="viewCarouselNext" onclick="moveViewCarousel(1)"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>
                <div class="view-info" style="padding: 2rem;">
                    <div id="viewLocation" class="spot-badge" style="position:static; display:inline-flex; margin-bottom: 1.5rem; background: var(--bg-light); padding: 0.5rem 1rem; border-radius: 2rem;"></div>
                    
                    <div class="info-group">
                        <label style="color: var(--text-muted); text-transform:uppercase; font-size: 0.75rem; font-weight: 700; letter-spacing: 1px; display:block; margin-bottom: 0.75rem;">About this spot</label>
                        <p id="viewDescription" style="font-size: 1rem; line-height: 1.6; color: var(--text-main); margin-bottom: 2rem;"></p>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                        <div class="info-group">
                            <label style="color: var(--text-muted); text-transform:uppercase; font-size: 0.75rem; font-weight: 700; display:block; margin-bottom: 0.5rem;">Pricing</label>
                            <div id="viewPrices" style="font-family: 'Inter', sans-serif;"></div>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-muted); text-transform:uppercase; font-size: 0.75rem; font-weight: 700; display:block; margin-bottom: 0.5rem;">Time Slots</label>
                            <div id="viewSlots" style="font-size: 0.9rem; color: var(--text-main);"></div>
                        </div>
                    </div>

                    <div id="viewActions" style="border-top: 1px solid var(--border); padding-top: 1.5rem;">
                         <button id="viewBookBtn" class="btn btn-primary" style="width:100%; padding: 1.25rem; font-weight: 700; border-radius: 1rem; box-shadow: 0 10px 20px rgba(79, 70, 229, 0.25);">
                            Reserve My Experience Now
                         </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Booking Modal (Single-Page Unified Form) -->
<div id="bookingModal" class="modal-overlay" onclick="if(event.target==this) closeModals()">
    <div class="modal-box" style="max-width: 600px; overflow: hidden; border-radius: 1.5rem; display: flex; flex-direction: column; max-height: 90vh;">
        <!-- Premium Header -->
        <div class="booking-modal-header" style="flex-shrink: 0;">
            <h2 id="bookingTitle">Book Your Adventure</h2>
            <p id="bookingSubTitle" style="font-size: 0.85rem; opacity: 0.8; margin-top: 0.25rem;">Complete the form below to secure your spot</p>
            <button class="modal-close-btn" onclick="closeModals()">&times;</button>
        </div>

        <div class="modal-body" style="padding: 2rem; overflow-y: auto;">
            <div id="bookingFormContent">
                <!-- Section 1: Guest Details -->
                <div class="booking-section" style="margin-bottom: 2.5rem;">
                    <label style="color: var(--primary); text-transform:uppercase; font-size: 0.75rem; font-weight: 800; letter-spacing: 1px; display:flex; align-items:center; gap:0.5rem; margin-bottom: 1.25rem;">
                        <i class="fa-solid fa-user-tag"></i> 1. Guest Information
                    </label>
                    <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div style="grid-column: span 2;">
                            <input type="text" id="visitorName" placeholder="What is your full name?" style="padding: 0.85rem; border-radius: 0.75rem;" required>
                        </div>
                        <div>
                            <input type="email" id="visitorEmail" placeholder="Email Address" style="padding: 0.85rem; border-radius: 0.75rem;" required>
                        </div>
                        <div>
                            <input type="text" id="visitorPhone" placeholder="Contact number (09XXXXXXXXX)" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)" style="padding: 0.85rem; border-radius: 0.75rem;" required>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Experience (Tickets) -->
                <div class="booking-section" style="margin-bottom: 2.5rem;">
                    <label style="color: var(--primary); text-transform:uppercase; font-size: 0.75rem; font-weight: 800; letter-spacing: 1px; display:flex; align-items:center; gap:0.5rem; margin-bottom: 1.25rem;">
                        <i class="fa-solid fa-ticket"></i> 2. Choose Tickets
                    </label>
                    <div id="ticketsContainer"></div>
                </div>

                <!-- Section 3: Schedule -->
                <div style="margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid #f1f5f9;">
                    <div style="color: var(--primary); text-transform:uppercase; font-size: 0.75rem; font-weight: 800; letter-spacing: 1px; display:flex; align-items:center; gap:0.5rem; margin-bottom: 1rem;">
                        <i class="fa-solid fa-calendar-days"></i> 3. Schedule
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <small style="display:block; color: var(--text-muted); margin-bottom: 0.5rem; font-weight: 600;">Select Visit Date</small>
                        <input type="date" id="bookDate" min="<?php echo date('Y-m-d'); ?>" onchange="fetchSlots()" style="padding: 0.85rem; font-weight: 600; border-radius: 0.75rem; width:100%; box-sizing:border-box;">
                    </div>
                    <div id="slotsSection" style="display: none;">
                        <small style="display:block; color: var(--text-muted); margin-bottom: 0.5rem; font-weight: 600;">Select Available Time Slot</small>
                        <div id="slotsContainer" class="slot-grid"></div>
                    </div>
                </div>

                <!-- Booking Summary -->
                <div style="margin-bottom: 2rem;">
                    <div style="color: var(--primary); text-transform:uppercase; font-size: 0.75rem; font-weight: 800; letter-spacing: 1px; display:flex; align-items:center; gap:0.5rem; margin-bottom: 1.25rem;">
                        <i class="fa-solid fa-receipt"></i> Booking Summary
                    </div>
                    <div id="summaryCard" style="background: #f8fafc; padding: 1.5rem; border-radius: 1rem; border: 1px solid #e2e8f0; margin-bottom: 1.5rem;">
                        <div id="summaryContent"></div>
                    </div>
                </div>

                <!-- Section 4: Payment -->
                <div style="margin-bottom: 2rem;">
                    <div style="color: var(--primary); text-transform:uppercase; font-size: 0.75rem; font-weight: 800; letter-spacing: 1px; display:flex; align-items:center; gap:0.5rem; margin-bottom: 1.25rem;">
                        <i class="fa-solid fa-credit-card"></i> 4. Payment Method
                    </div>

                    <div id="paymentSelection">
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                            <div class="payment-opt" id="modalOptGcash" onclick="selectPayment('gcash')" style="border: 2px solid var(--border); padding: 1.25rem 1rem; border-radius: 1rem; text-align: center; cursor: pointer; transition: all 0.2s; background: white;">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/cb/GCash_logo.svg/1200px-GCash_logo.svg.png" style="height: 18px; margin-bottom: 0.5rem; object-fit: contain;">
                                <div style="font-size: 0.8rem; font-weight: 700;">GCash</div>
                            </div>
                            <div class="payment-opt" id="modalOptPaypal" onclick="selectPayment('paypal')" style="border: 2px solid var(--border); padding: 1.25rem 1rem; border-radius: 1rem; text-align: center; cursor: pointer; transition: all 0.2s; background: white;">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b5/PayPal.svg/1200px-PayPal.svg.png" style="height: 18px; margin-bottom: 0.5rem; object-fit: contain;">
                                <div style="font-size: 0.8rem; font-weight: 700;">PayPal</div>
                            </div>
                            <div class="payment-opt" id="modalOptPayLater" onclick="selectPayment('paylater')" style="border: 2px solid var(--border); padding: 1.25rem 1rem; border-radius: 1rem; text-align: center; cursor: pointer; transition: all 0.2s; background: white;">
                                <i class="fa-solid fa-clock" style="font-size: 18px; color: var(--text-main); margin-bottom: 0.5rem; display: block;"></i>
                                <div style="font-size: 0.8rem; font-weight: 700;">Pay Later</div>
                            </div>
                        </div>

                        <!-- GCash UI -->
                        <div id="modalGcashUI" style="display: none; animation: fadeIn 0.3s ease;">
                            <div style="background: #eff6ff; padding: 1.25rem; border-radius: 1rem; border: 1px solid #bfdbfe; margin-bottom: 1.25rem; display: flex; gap: 1rem; align-items: center;">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=GCash_Payment_Placeholder" style="width: 100px; height: 100px; background: white; padding: 0.5rem; border-radius: 0.5rem;">
                                <div style="font-size: 0.8rem; color: #1e40af; line-height: 1.4;">
                                    <strong>Scan to Pay</strong><br>
                                    or send to 0912-345-6789 (Admin)<br>
                                    <span style="font-size: 0.7rem; opacity: 0.8;">Input reference # below</span>
                                </div>
                            </div>
                            <input type="text" id="modalGcashRef" placeholder="12-digit Reference Number" maxlength="12" style="margin-bottom: 1rem; padding: 0.85rem; border-radius: 0.75rem;">
                            <button onclick="confirmGcashPayment()" class="btn btn-primary" style="width: 100%; padding: 1rem; border-radius: 0.75rem; font-weight: 700;">Complete Payment</button>
                        </div>

                        <!-- PayPal UI -->
                        <div id="modalPaypalUI" style="display: none; animation: fadeIn 0.3s ease;">
                            <div id="paypal-button-container"></div>
                        </div>

                        <!-- Pay Later UI -->
                        <div id="modalPayLaterUI" style="display: none; animation: fadeIn 0.3s ease;">
                            <div style="background: #fdfae6; padding: 1.25rem; border-radius: 1rem; border: 1px solid #fef08a; margin-bottom: 1.25rem; display: flex; gap: 1rem; align-items: center;">
                                <i class="fa-solid fa-circle-info" style="font-size: 2rem; color: #ca8a04;"></i>
                                <div style="font-size: 0.85rem; color: #a16207; line-height: 1.4;">
                                    <strong>Pay Later Selected</strong><br>
                                    Your booking will be marked as pending. You can pay over the counter at the ticketing booth on your date of visit.
                                </div>
                            </div>
                            <button onclick="confirmPayLaterPayment(event)" class="btn btn-primary" style="width: 100%; padding: 1rem; border-radius: 0.75rem; font-weight: 700; background: #ca8a04;">Confirm Booking</button>
                        </div>
                    </div> <!-- end paymentSelection -->
                </div> <!-- end Section 4 booking-section -->
            </div> <!-- end bookingFormContent -->

            <!-- Loading State -->
            <div id="bookingLoading" style="display:none; text-align:center; padding: 3rem 0;">
                <div class="spinner" style="border: 4px solid #f3f3f3; border-top: 4px solid var(--primary); border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 0 auto 1.5rem;"></div>
                <h3 style="margin:0;">Finalizing Booking...</h3>
            </div>
        </div>
    </div>
</div>

<script>
let currentSpot = null;
let currentImages = [];
let carouselIndex = 0;
let selectedSlot = null;
let attractionTickets = [];
let ticketQuantities = {};
let selectedPaymentMethod = null;

function formatTime12h(timeStr) {
    if (!timeStr) return '';
    const [hours, minutes] = timeStr.split(':');
    let h = parseInt(hours);
    const m = minutes;
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12;
    h = h ? h : 12;
    return `${h}:${m} ${ampm}`;
}

function closeModals() {
    document.getElementById('viewModal').style.display = 'none';
    document.getElementById('bookingModal').style.display = 'none';
    // Deep clear carousel
    const slides = document.getElementById('viewCarouselSlides');
    if (slides) {
        slides.style.transform = `translateX(0)`;
        slides.innerHTML = '';
    }
    carouselIndex = 0;
    // Clear state
    selectedSlot = null;
    ticketQuantities = {};
}

function openViewModal(spot, images, slots) {
    currentSpot = spot;
    currentImages = images;
    carouselIndex = 0;
    
    document.getElementById('viewTitle').innerText = spot.name;
    document.getElementById('viewDescription').innerText = spot.description;
    document.getElementById('viewLocation').innerHTML = `<i class="fa-solid fa-location-dot"></i> ${spot.location}`;
    
    // Render Prices
    const pricesHtml = `
        <div style="display:flex; justify-content:space-between; margin-bottom: 0.25rem;"><span>Adult:</span> <strong style="color:var(--primary);">₱${parseFloat(spot.price_adult || 0).toFixed(2)}</strong></div>
        <div style="display:flex; justify-content:space-between; margin-bottom: 0.25rem;"><span>Child:</span> <strong style="color:var(--primary);">₱${parseFloat(spot.price_child || 0).toFixed(2)}</strong></div>
        <div style="display:flex; justify-content:space-between;"><span>Senior:</span> <strong style="color:var(--primary);">₱${parseFloat(spot.price_senior || 0).toFixed(2)}</strong></div>
    `;
    document.getElementById('viewPrices').innerHTML = pricesHtml;

    // Render Slots
    const slotsHtml = slots.length > 0 
        ? slots.map(s => `<div style="margin-bottom:0.25rem;"><i class="fa-regular fa-clock" style="color:var(--primary); font-size:0.8rem;"></i> ${s.slot_name} (${formatTime12h(s.start_time)})</div>`).join('')
        : '<p>No set schedule</p>';
    document.getElementById('viewSlots').innerHTML = slotsHtml;

    const slides = document.getElementById('viewCarouselSlides');
    slides.innerHTML = images.map(img => `<img src="${img}">`).join('');
    
    // Generate Dots
    const dotsContainer = document.getElementById('viewCarouselDots');
    dotsContainer.innerHTML = images.length > 1 
        ? images.map((_, i) => `<span class="dot" onclick="setViewCarouselIndex(${i})"></span>`).join('')
        : '';
        
    // Toggle Navigation Buttons
    const prevBtn = document.getElementById('viewCarouselPrev');
    const nextBtn = document.getElementById('viewCarouselNext');
    if (images.length <= 1) {
        prevBtn.style.display = 'none';
        nextBtn.style.display = 'none';
    } else {
        prevBtn.style.display = 'flex';
        nextBtn.style.display = 'flex';
    }

    document.getElementById('viewModal').style.display = 'flex';

    // Wait for layout to be ready before first update
    requestAnimationFrame(() => {
        updateCarousel();
    });
    
    document.getElementById('viewBookBtn').onclick = () => openBookingModal(spot);
}

function setViewCarouselIndex(index) {
    carouselIndex = index;
    updateCarousel();
}

function moveViewCarousel(dir) {
    if (currentImages.length <= 1) return;
    carouselIndex = (carouselIndex + dir + currentImages.length) % currentImages.length;
    updateCarousel();
}

function updateCarousel() {
    const slides = document.getElementById('viewCarouselSlides');
    slides.style.transform = `translateX(-${carouselIndex * 100}%)`;
    
    // Update active dot
    const dots = document.querySelectorAll('#viewCarouselDots .dot');
    dots.forEach((dot, i) => {
        dot.classList.toggle('active', i === carouselIndex);
    });
}

async function openBookingModal(spot) {
    document.getElementById('viewModal').style.display = 'none';
    currentSpot = spot;
    document.getElementById('bookingTitle').innerText = spot.name;
    document.getElementById('bookingModal').style.display = 'flex';
    
    // Clear state
    selectedSlot = null;
    ticketQuantities = {};
    document.getElementById('slotsContainer').innerHTML = '';
    document.getElementById('slotsSection').style.display = 'none';
    document.getElementById('bookDate').value = '';
    document.getElementById('visitorName').value = '';
    document.getElementById('visitorEmail').value = '';
    document.getElementById('visitorPhone').value = '';
    document.getElementById('modalGcashRef').value = '';
    
    // Show loading state
    const ticketContainer = document.getElementById('ticketsContainer');
    ticketContainer.innerHTML = `<p style="text-align:center; color: var(--text-muted); padding: 1rem; font-size: 0.9rem;"><i class="fa-solid fa-circle-notch fa-spin" style="margin-right: 0.5rem;"></i>Loading tickets...</p>`;
    
    try {
        const res = await fetch(`backend/api_bookings.php?action=get_ticket_types&attraction_id=${currentSpot.id}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed to load tickets');
        attractionTickets = data.ticket_types || [];
        renderTickets();
    } catch(e) {
        ticketContainer.innerHTML = `<p style="text-align:center; color: #ef4444; padding: 1rem;">Could not load tickets. Please try again.<br><small>${e.message}</small></p>`;
    }
    updateSummary();
}

async function fetchSlots() {
    const date = document.getElementById('bookDate').value;
    if(!date) return;
    
    const container = document.getElementById('slotsContainer');
    document.getElementById('slotsSection').style.display = 'block';
    container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; padding: 1rem;">Checking...</p>';
    
    const res = await fetch(`backend/api_bookings.php?action=get_availability&attraction_id=${currentSpot.id}&date=${date}`);
    const data = await res.json();
    
    if(data.success) {
        if (data.slots.length === 0) {
            container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; padding: 1rem; color: #ef4444;">No slots.</p>';
        } else {
            container.innerHTML = data.slots.map(slot => `
                <div class="slot-item ${slot.available_capacity <= 0 ? 'disabled' : ''}" onclick="selectSlot(${JSON.stringify(slot).replace(/"/g, '&quot;')}, this)">
                    <div style="font-weight: 700;">${slot.slot_name}</div>
                    <div style="font-size: 0.7rem; opacity: 0.8; margin-top: 0.2rem;">
                        ${formatTime12h(slot.start_time)} - ${formatTime12h(slot.end_time)}
                    </div>
                </div>
            `).join('');
        }
    } else {
        container.innerHTML = `<p style="grid-column: 1/-1; color:red;">${data.message}</p>`;
    }
}

function selectSlot(slot, el) {
    if(slot.available_capacity <= 0) return;
    selectedSlot = slot;
    document.querySelectorAll('.slot-item').forEach(item => item.classList.remove('active'));
    el.classList.add('active');
    updateSummary();
}

function renderTickets() {
    const container = document.getElementById('ticketsContainer');
    const multiplier = selectedSlot ? selectedSlot.price_multiplier : 1;
    const icons = { 'Adult': '\u{1F9D1}', 'Child': '\u{1F9D2}', 'Senior': '\u{1F474}' };

    if (attractionTickets.length === 0) {
        container.innerHTML = '<p style="color: var(--text-muted); text-align: center; padding: 1rem;">No ticket types available.</p>';
        return;
    }

    container.innerHTML = attractionTickets.map(t => {
        const qty = ticketQuantities[t.id] || 0;
        const price = (t.price * multiplier).toFixed(2);
        const icon = icons[t.name] || '\u{1F3AB}';
        return `
            <div style="display:flex; justify-content:space-between; align-items:center; padding:1rem 1.25rem; background:white; border-radius:0.85rem; margin-bottom:0.6rem; border:1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <span style="font-size:1.5rem;">${icon}</span>
                    <div>
                        <div style="font-weight:700; color:var(--secondary); font-size:0.95rem;">${t.name} Ticket</div>
                        <div style="color:var(--primary); font-weight:700; font-size:0.85rem;">\u20B1${price} <span style="font-weight:400; color:var(--text-muted); font-size:0.75rem;">per person</span></div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:0.5rem; background:#f8fafc; padding:0.35rem 0.5rem; border-radius:2rem; border:1px solid #e2e8f0;">
                    <button type="button" onclick="changeQty(this, ${t.id}, -1)" style="width:30px; height:30px; border-radius:50%; border:none; background:#f1f5f9; color:var(--secondary); cursor:pointer; font-size:1.1rem; font-weight:800; line-height:1;">\u2212</button>
                    <input type="number" oninput="setQty(this, ${t.id}, this.value)" value="${qty}" min="0" max="20" style="width:44px; text-align:center; font-weight:700; font-size:1rem; border:none; background:transparent; outline:none; color:#1e293b; -moz-appearance: textfield;">
                    <button type="button" onclick="changeQty(this, ${t.id}, 1)" style="width:30px; height:30px; border-radius:50%; border:none; background:var(--primary); color:white; cursor:pointer; font-size:1.1rem; font-weight:800; line-height:1;">+</button>
                </div>
            </div>
        `;
    }).join('');

    updateSummary();
}

function setQty(inputElem, id, val) {
    let n = parseInt(val);
    if (isNaN(n) || n < 0) n = 0;
    if (n > 20) { n = 20; }
    
    ticketQuantities[id] = n;
    inputElem.value = n;
    
    updateSummary();
}

function changeQty(btnElem, id, dir) {
    let currentQty = ticketQuantities[id] || 0;
    let newQty = currentQty + dir;
    
    if (newQty < 0) newQty = 0;
    if (newQty > 20) newQty = 20;
    
    ticketQuantities[id] = newQty;
    
    const input = btnElem.parentElement.querySelector('input');
    if (input) {
        input.value = newQty;
    }
    
    updateSummary();
}

function updateSummary() {
    let total = 0;
    let count = 0;
    let summaryHtml = '';

    const date = document.getElementById('bookDate').value;
    const time = selectedSlot ? selectedSlot.slot_name : '<span style="color:#ef4444;">Not selected</span>';

    summaryHtml += `
    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.5rem;">
        <span style="color: var(--text-muted);">Schedule:</span>
        <span style="font-weight: 700;">${date || '---'} | ${time}</span>
    </div>`;

    attractionTickets.forEach(t => {
        const qty = ticketQuantities[t.id] || 0;
        if (qty > 0) {
            const multiplier = selectedSlot ? selectedSlot.price_multiplier : 1;
            const lineTotal = qty * (t.price * multiplier);
            total += lineTotal;
            count += qty;
            summaryHtml += `
            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.25rem;">
                <span style="color: var(--text-muted);">${qty}x ${t.name}</span>
                <span style="font-weight: 600;">₱${lineTotal.toFixed(2)}</span>
            </div>`;
        }
    });

    summaryHtml += `
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border);">
        <span style="font-weight: 800; color: var(--secondary);">Total to pay:</span>
        <strong style="color: var(--primary); font-size: 1.25rem;">₱${total.toFixed(2)}</strong>
    </div>`;

    document.getElementById('summaryContent').innerHTML = summaryHtml;
}


function selectPayment(method) {
    selectedPaymentMethod = method;
    document.querySelectorAll('.payment-opt').forEach(opt => {
        opt.style.borderColor = 'var(--border)';
        opt.style.background = 'white';
    });
    
    if (method === 'gcash') {
        document.getElementById('modalOptGcash').style.borderColor = 'var(--primary)';
        document.getElementById('modalOptGcash').style.background = 'rgba(79, 70, 229, 0.05)';
        document.getElementById('modalGcashUI').style.display = 'block';
        document.getElementById('modalPaypalUI').style.display = 'none';
        document.getElementById('modalPayLaterUI').style.display = 'none';
    } else if (method === 'paypal') {
        document.getElementById('modalOptPaypal').style.borderColor = 'var(--primary)';
        document.getElementById('modalOptPaypal').style.background = 'rgba(79, 70, 229, 0.05)';
        document.getElementById('modalGcashUI').style.display = 'none';
        document.getElementById('modalPaypalUI').style.display = 'block';
        document.getElementById('modalPayLaterUI').style.display = 'none';
        if(document.getElementById('paypal-button-container').innerHTML === "") initPayPal();
    } else if (method === 'paylater') {
        document.getElementById('modalOptPayLater').style.borderColor = '#ca8a04';
        document.getElementById('modalOptPayLater').style.background = '#fefce8';
        document.getElementById('modalGcashUI').style.display = 'none';
        document.getElementById('modalPaypalUI').style.display = 'none';
        document.getElementById('modalPayLaterUI').style.display = 'block';
    }
}

function initPayPal() {
    paypal.Buttons({
        createOrder: (data, actions) => {
            let total = 0;
            const multiplier = selectedSlot ? selectedSlot.price_multiplier : 1;
            attractionTickets.forEach(t => {
                total += (ticketQuantities[t.id] || 0) * (t.price * multiplier);
            });
            return actions.order.create({ purchase_units: [{ amount: { value: total.toFixed(2) } }] });
        },
        onApprove: (data, actions) => {
            processBooking(data.orderID);
        }
    }).render('#paypal-button-container');
}

async function processBooking(orderId = null, gcashRef = null, isPayLater = false) {
    const name = document.getElementById('visitorName').value;
    const email = document.getElementById('visitorEmail').value;
    const phone = document.getElementById('visitorPhone').value;
    const date = document.getElementById('bookDate').value;
    
    let ticketsChosen = false;
    Object.values(ticketQuantities).forEach(q => { if(q > 0) ticketsChosen = true; });

    if(!name || !email || !phone || !date || !selectedSlot) {
        Swal.fire('Required', 'Please fill in all guest details, select a date and time slot.', 'warning');
        return;
    }

    if(!ticketsChosen) {
        Swal.fire('Required', 'Please select at least one ticket.', 'warning');
        return;
    }

    // Immediate Loading Feedback
    Swal.fire({
        title: 'Processing Booking...',
        text: 'Please wait while we secure your spot.',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    document.getElementById('bookingFormContent').style.display = 'none';
    document.getElementById('bookingLoading').style.display = 'block';

    let pMethod = 'paypal';
    if(gcashRef) pMethod = 'gcash';
    if(isPayLater) pMethod = 'paylater';

    const payload = {
        attraction_id: currentSpot.id,
        date: document.getElementById('bookDate').value,
        time_slot_id: selectedSlot.id,
        visitor_name: name,
        visitor_email: email,
        visitor_phone: phone,
        payment_method: pMethod,
        tickets: Object.entries(ticketQuantities).filter(([k,v]) => v>0).map(([k,v]) => ({type_id: parseInt(k), quantity: v}))
    };

    const res = await fetch('backend/api_bookings.php?action=reserve', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    const result = await res.json();

    if(result.success) {
        if(isPayLater) {
            Swal.fire({
                title: 'Booking Approved!',
                text: 'Your booking has been saved. Please pay at the ticketing booth on your date of visit.',
                icon: 'success',
                confirmButtonColor: '#10b981',
                confirmButtonText: 'View Ticket'
            }).then(() => {
                window.location.href = `success.php?booking_id=${result.booking_id}`;
            });
            return;
        }

        const confirmPayload = { booking_id: result.booking_id };
        if (orderId) confirmPayload.paypal_order_id = orderId;
        if (gcashRef) confirmPayload.gcash_reference = gcashRef;

        const confirm = await fetch('backend/api_bookings.php?action=confirm', {
            method: 'POST',
            body: JSON.stringify(confirmPayload)
        });
        const confirmResult = await confirm.json();
        
        if(confirmResult.success) {
            Swal.fire({
                title: 'Payment Confirmed!',
                text: 'Your tickets are ready.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = `success.php?booking_id=${result.booking_id}`;
            });
        } else {
            Swal.fire('Error', confirmResult.message, 'error');
            document.getElementById('bookingFormContent').style.display = 'block';
            document.getElementById('bookingLoading').style.display = 'none';
        }
    } else {
        Swal.fire('Error', result.message, 'error');
        document.getElementById('bookingFormContent').style.display = 'block';
        document.getElementById('bookingLoading').style.display = 'none';
    }
}

async function confirmGcashPayment() {
    const ref = document.getElementById('modalGcashRef').value;
    if (ref.length < 8) {
        Swal.fire('Error', 'Invalid GCash reference.', 'error');
        return;
    }
    processBooking(null, ref, false);
}

async function confirmPayLaterPayment(event) {
    if(event) event.preventDefault();
    processBooking(null, null, true);
}
</script>

<?php require 'includes/footer.php'; ?>


