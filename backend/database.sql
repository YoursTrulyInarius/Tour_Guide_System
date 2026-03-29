CREATE DATABASE IF NOT EXISTS tour_guide_db;
USE tour_guide_db;

-- Core Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin') NOT NULL DEFAULT 'admin',
    profile_pic VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'banned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Attractions / Tourist Spots
CREATE TABLE IF NOT EXISTS attractions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    lat DECIMAL(10, 8),
    lng DECIMAL(11, 8),
    image_url VARCHAR(255),
    video_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Ticket Types (Tiered Pricing)
CREATE TABLE IF NOT EXISTS ticket_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attraction_id INT NOT NULL,
    name VARCHAR(50) NOT NULL, -- Adult, Child, Senior, Student
    base_price DECIMAL(10, 2) NOT NULL,
    description VARCHAR(255),
    FOREIGN KEY (attraction_id) REFERENCES attractions(id) ON DELETE CASCADE
);

-- Time Slots & Capacity Management
CREATE TABLE IF NOT EXISTS time_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attraction_id INT NOT NULL,
    slot_name VARCHAR(50) NOT NULL, -- Morning, Afternoon, Evening
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_capacity INT NOT NULL,
    FOREIGN KEY (attraction_id) REFERENCES attractions(id) ON DELETE CASCADE
);

-- Bookings (Inventory Lock / Payment)
CREATE TABLE IF NOT EXISTS bookings (
    id VARCHAR(36) PRIMARY KEY, -- Using UUID for unique identifier
    user_id INT NULL, -- NULL for Guest Checkout
    visitor_email VARCHAR(255) NOT NULL,
    visitor_name VARCHAR(255) NOT NULL,
    visitor_phone VARCHAR(20),
    visit_date DATE NOT NULL,
    time_slot_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'paid', 'cancelled', 'refunded') DEFAULT 'pending',
    paypal_order_id VARCHAR(100) NULL,
    is_reminder_sent BOOLEAN DEFAULT FALSE,
    is_survey_sent BOOLEAN DEFAULT FALSE,
    expires_at TIMESTAMP NULL, -- 15 mins for inventory lock
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (time_slot_id) REFERENCES time_slots(id)
);

-- Individual Tickets for QR Validation
CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id VARCHAR(36) NOT NULL,
    ticket_type_id INT NOT NULL,
    qr_code VARCHAR(255) UNIQUE NOT NULL,
    is_scanned BOOLEAN DEFAULT FALSE,
    scanned_at TIMESTAMP NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id)
);

-- Seasonal Pricing Factor
CREATE TABLE IF NOT EXISTS seasonal_pricing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attraction_id INT NOT NULL,
    name VARCHAR(50) NOT NULL, -- Holiday, Peak, Off-Peak
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    price_multiplier DECIMAL(3, 2) NOT NULL DEFAULT 1.00,
    FOREIGN KEY (attraction_id) REFERENCES attractions(id) ON DELETE CASCADE
);

-- Blackout Dates (Closed for specific days / Holidays)
CREATE TABLE IF NOT EXISTS blackout_dates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attraction_id INT NOT NULL,
    blackout_date DATE NOT NULL,
    reason VARCHAR(255),
    FOREIGN KEY (attraction_id) REFERENCES attractions(id) ON DELETE CASCADE
);

-- Default Admin accounts
-- Passwords are: admin123
INSERT IGNORE INTO users (username, email, password_hash, role) VALUES 
('admin_user', 'admin@yourstruly.com', '$2y$10$kv3JOmyV4NKp3dtb.0YFzOHpW/z/fiiA9I0GgPYoPtMVvNJAnpf6m', 'admin');


