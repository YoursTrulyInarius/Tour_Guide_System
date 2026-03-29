CREATE DATABASE IF NOT EXISTS tour_guide_db;
USE tour_guide_db;

-- Core Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('tourist', 'guide', 'admin') NOT NULL DEFAULT 'tourist',
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

-- Add some initial data
INSERT INTO attractions (name, description, location, image_url) VALUES 
('Enchanted Gardens', 'A beautiful botanical garden with rare tropical flowers and light shows at night.', 'Green Valley', 'https://images.unsplash.com/photo-1585320806297-9794b3e4eeae'),
('Crystal Cove', 'Pristine turquoise waters and hidden sea caves perfect for snorkeling and relaxation.', 'Coastal Bay', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e'),
('Sky Peak', 'A breathtaking mountain view accessible via cable car or a challenging 3-hour hike.', 'Mist Mountain', 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b');

INSERT INTO ticket_types (attraction_id, name, base_price) VALUES 
(1, 'Adult', 25.00),
(1, 'Child', 15.00),
(1, 'Senior', 20.00);

INSERT INTO time_slots (attraction_id, slot_name, start_time, end_time, max_capacity) VALUES 
(1, 'Morning', '09:00:00', '12:00:00', 500),
(1, 'Afternoon', '13:00:00', '17:00:00', 500);

-- Revenue Add-ons (Upsells)
CREATE TABLE IF NOT EXISTS add_ons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attraction_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    description TEXT,
    FOREIGN KEY (attraction_id) REFERENCES attractions(id) ON DELETE CASCADE
);

-- Binding Add-ons to Bookings
CREATE TABLE IF NOT EXISTS booking_add_ons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id VARCHAR(36) NOT NULL,
    add_on_id INT NOT NULL,
    quantity INT NOT NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (add_on_id) REFERENCES add_ons(id) ON DELETE CASCADE
);

INSERT INTO add_ons (attraction_id, name, price, description) VALUES 
(1, 'Audio Guide', 5.00, 'Immersive storytelling in 5 languages'),
(1, 'Skip-the-line', 10.00, 'Priority entrance to the spot'),
(1, 'Parking Pass', 10.00, 'Reserved secure parking');
