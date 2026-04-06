# 🗺️ Tour Guide System (Premium Digital Concierge)

> [!IMPORTANT]
> **🚀 PROJECT STATUS:** ACTIVE PRODUCTION
> This system is a high-performance, end-to-end digital solution for the Philippine Tourism industry. It features a modern multi-step booking engine, secure payment integrations, and a robust administrative backend.

<br>

## 🌟 Table of Contents

- [Architecture Overview](#-architecture-overview)
- [Technology Stack](#-technology-stack)
- [Core System Pillars](#-core-system-pillars)
  - [1. 📅 The Booking Engine](#1--the-booking-engine-multi-step-ui--atomic-validation)
  - [2. 💳 The Payment Ecosystem](#2--the-payment-ecosystem-paymongo--paypal)
  - [3. 🎫 The QR Passport System](#3--the-qr-passport-system-generation-storage--scanning)
- [Deep Dive: Core Implementation](#-deep-dive-core-implementation-codes)
- [Database Schema](#-database-schema)
- [Installation & Setup](#-installation--setup)

<br>

---

## 🏗️ Architecture Overview

The project follows a **Modular Monolith** pattern using PHP 8.x. It separates concerns across three primary layers:

1.  **Frontend (UI)**: Vanilla Javascript & CSS3 with a focus on "Premium Aesthetics" (glassmorphism, smooth transitions).
2.  **API Layer (`backend/api_*.php`)**: Stateless endpoints that handle logic for bookings, statistics, and communications.
3.  **Data Layer (`includes/db.php`)**: A persistent MySQL database accessed via PHP Data Objects (PDO) for SQL injection protection.

<br>

---

## 💻 Technology Stack

- **Backend**: PHP 8 (PDO, Sessions, CURL)
- **Frontend**: HTML5, Modern CSS, Vanilla JS
- **Database**: MySQL 8
- **Payments**: PayMongo API (GCash, Maya, GrabPay), PayPal SDK
- **Communication**: PHPMailer (SMTP Integration)
- **Utilities**: HTML5-QRCode (Scanner), Fingerprint.js (Fraud Prevention)

<br>

---

## 🗺️ Core System Pillars

The **Tour Guide System** is built on three high-performance pillars. Below is the technical breakdown of how each module operates.

<br>

### 1. 📅 The Booking Engine (Multi-Step UI & Atomic Validation)

The booking module provides an intuitive, step-by-step experience for tourists while enforcing strict capacity limits on the server.

**Frontend UI Logic (`booking.php`):**
The UI uses an asynchronous "Step-Load" pattern. As users select a date, the system fetches real-time availability via `fetch()`.

```javascript
async function fetchSlots() {
    const date = document.getElementById('visitDate').value;
    const res = await fetch(`backend/api_bookings.php?action=get_availability&date=${date}`);
    const data = await res.json();
    
    // Renders slots only if they have remaining capacity
    data.slots.forEach(slot => {
        const isSoldOut = slot.available_capacity <= 0;
        renderSlotUI(slot, isSoldOut);
    });
}
```

**Backend Integrity (`backend/api_bookings.php`):**
To prevent race conditions where two users book the last spot simultaneously, the backend re-validates capacity at the moment of reservation:

```php
// Final Capacity Check before INSERT
if (($booked_count + $requested_pax) > $max_capacity) {
    throw new Exception("Slot just filled up. Please select another time.");
}
```

<br>

---

### 2. 💳 The Payment Ecosystem (PayMongo & PayPal)

The system supports a "Hybrid Payment" model: **Instant Online Payment** (PayMongo) or **Manual Verification** (GCash Scan / Pay Later).

**Online Checkout Flow (`backend/paymongo_helper.php`):**
We utilize PayMongo's hosted checkout to remain PCI-compliant. The system maps internal `booking_ids` to PayMongo `reference_numbers`.

```php
public function createCheckoutSession($amount, $booking_id, $customer) {
    return $this->request('POST', '/checkout_sessions', [
        'attributes' => [
            'amount' => $amount * 100, // Converts PHP to Centavos
            'payment_method_types' => ['gcash', 'paymaya', 'card'],
            'success_url' => $this->getBaseUrl() . '/success.php?booking_id=' . $booking_id,
        ]
    ]);
}
```

**Pay Later / GCash Manual Workflow:**
For manual payments, the system records a `pending` status. Admin staff must manually verify the GCash reference number in the dashboard before the QR ticket is released via email.

<br>

---

### 3. 🎫 The QR Passport System (Generation, Storage, & Scanning)

This is the security heart of the project. It handles the lifecycle of a digital ticket from creation to entrance scanning.

**QR Generation & Persistence (`backend/save_qr.php`):**
Once a booking is paid, a high-resolution QR code is rendered in the browser and sent back to the server to be saved as a permanent `.png` file for the automated email.

```php
// Strip data URL prefix and save from Base64
$image_data = preg_replace('/^data:image\/png;base64,/', '', $base64_string);
$image_data = base64_decode($image_data);
file_put_contents('qr/' . $booking_id . '.png', $image_data);

// Link the image to the ticket record
$pdo->prepare("UPDATE tickets SET qr_image_path = ? WHERE booking_id = ?")
    ->execute(['qr/' . $booking_id . '.png', $booking_id]);
```

**Field Validation & Anti-Fraud (`admin.php`):**
Staff use the built-in scanner to verify the `SHA256` hash. The system is "Single-Use" by design:

*   **Scanning Logic**: `UPDATE tickets SET is_scanned = TRUE, scanned_at = NOW() WHERE qr_code = ? AND is_scanned = FALSE`
*   **Safety**: If `rowCount() == 0`, the staff is alerted that the ticket is either **Invalid** or **Already Used**.

<br>

---

## 🔍 Deep Dive: Core Implementation Codes

*For developers looking to extend the system, here are the primary technical implementations:*

### 🔐 Cryptographically Secure Hashing

```php
function generateQRCode($booking_id) {
    // SHA256 ensures hashes are 64 characters and impossible to guess
    return hash('sha256', $booking_id . bin2hex(random_bytes(8)) . 'SECRET_SALT');
}
```

<br>

### ✉️ Automated Communication (PHPMailer)

```php
// Embedded QR Images (CIDs) ensure tickets display offline in the email
$mail->addEmbeddedImage('qr/' . $booking_id . '.png', 'ticket_qr');
$mail->Body = "Show this at the gate: <img src='cid:ticket_qr'>";
```

<br>

---

## 🗄️ Database Schema

The core relational structure ensures data integrity:

- **`attractions`**: Master table for tourist spots.
- **`time_slots`**: Define custom windows (Morning, Afternoon, Full Day).
- **`bookings`**: Stores visitor metadata, total amount, and payment status.
- **`tickets`**: Individual QR entries linked to a booking.
- **`seasonal_pricing`**: Overrides base prices for specific date ranges.
- **`blackout_dates`**: Stores dates when the attraction is closed for maintenance.

<br>

---

## 🛠️ Installation & Setup

1.  **Clone to XAMPP**: Place project files in `C:/xampp/htdocs/Tour_Guide_System`.
2.  **Database Migration**:
    - Open phpMyAdmin.
    - Create `tour_guide_db`.
    - Import `backend/database.sql`.
3.  **Mail Configuration**:
    - Edit `backend/config_mail.php` with your SMTP details (Gmail App Password recommended).
4.  **Admin Setup**:
    - Visit `localhost/Tour_Guide_System/backend/setup_admin.php` to create the default administrator account.

<br>

---

### 🔑 Default Credentials

- **Admin Email**: `admin@yourstruly@gmail.com`
- **Password**: `admin123`

<br>

---

*Developed with ❤️ for the Philippine Tourism industry.*
