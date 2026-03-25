-- ================================================
-- Voyager Travel Booking — Full Database Setup
-- Run this in phpMyAdmin or MySQL CLI
-- ================================================

CREATE DATABASE IF NOT EXISTS travel_db;
USE travel_db;

-- ── USERS ──
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    reward_points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── ADMIN ──
CREATE TABLE IF NOT EXISTS admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_approved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── DESTINATION ──
CREATE TABLE IF NOT EXISTS destination (
    destination_id INT AUTO_INCREMENT PRIMARY KEY,
    destination_name VARCHAR(150) NOT NULL,
    city VARCHAR(100),
    country VARCHAR(100),
    description TEXT
) ENGINE=InnoDB;

-- ── TRAVEL PACKAGE ──
CREATE TABLE IF NOT EXISTS travel_package (
    package_id INT AUTO_INCREMENT PRIMARY KEY,
    package_name VARCHAR(150) NOT NULL,
    duration VARCHAR(50),
    start_date DATE,
    end_date DATE,
    total_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount DECIMAL(5,2) DEFAULT 0,
    available_slots INT DEFAULT 20,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── TRIP (Bookings) ──
CREATE TABLE IF NOT EXISTS trip (
    trip_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    trip_name VARCHAR(150) NOT NULL,
    start_date DATE,
    end_date DATE,
    total_budget DECIMAL(12,2) DEFAULT 0,
    passengers INT DEFAULT 1,
    total_expense DECIMAL(12,2) DEFAULT 0,
    trip_status ENUM('Pending','Ongoing','Approved','Completed','Cancelled') DEFAULT 'Pending',
    package_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES travel_package(package_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── EXPENSE ──
CREATE TABLE IF NOT EXISTS expense (
    expense_id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    expense_type VARCHAR(100),
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    expense_date DATE,
    FOREIGN KEY (trip_id) REFERENCES trip(trip_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── REVIEW (Destination Reviews) ──
CREATE TABLE IF NOT EXISTS review (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    destination_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    review_date DATE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (destination_id) REFERENCES destination(destination_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── PACKAGE REVIEW ──
CREATE TABLE IF NOT EXISTS package_review (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    rating DECIMAL(3,1) CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    review_date DATE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES travel_package(package_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── HOTEL ──
CREATE TABLE IF NOT EXISTS hotel (
    hotel_id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_name VARCHAR(150) NOT NULL,
    star_rating INT DEFAULT 3,
    price_per_day DECIMAL(10,2) NOT NULL DEFAULT 0,
    availability_status TINYINT DEFAULT 1,
    location VARCHAR(150)
) ENGINE=InnoDB;

-- ── TRANSPORT OPTIONS ──
CREATE TABLE IF NOT EXISTS transport_options (
    transport_id INT AUTO_INCREMENT PRIMARY KEY,
    transport_type VARCHAR(100) NOT NULL,
    provider_name VARCHAR(100),
    cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    availability TINYINT DEFAULT 1
) ENGINE=InnoDB;

-- ── ACTIVITIES ──
CREATE TABLE IF NOT EXISTS activities (
    activity_id INT AUTO_INCREMENT PRIMARY KEY,
    activity_name VARCHAR(150) NOT NULL,
    activity_type VARCHAR(100),
    cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    destination_id INT DEFAULT NULL,
    FOREIGN KEY (destination_id) REFERENCES destination(destination_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── CUSTOM BOOKING ──
CREATE TABLE IF NOT EXISTS custom_booking (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    destination_id INT,
    hotel_id INT,
    transport_id INT,
    activity_id INT,
    total_cost DECIMAL(12,2) DEFAULT 0,
    booking_date DATE,
    trip_status ENUM('Pending','Approved','Completed','Cancelled') DEFAULT 'Pending',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (destination_id) REFERENCES destination(destination_id) ON DELETE SET NULL,
    FOREIGN KEY (hotel_id) REFERENCES hotel(hotel_id) ON DELETE SET NULL,
    FOREIGN KEY (transport_id) REFERENCES transport_options(transport_id) ON DELETE SET NULL,
    FOREIGN KEY (activity_id) REFERENCES activities(activity_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── JOURNAL ENTRY ──
CREATE TABLE IF NOT EXISTS journal_entry (
    entry_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    trip_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    mood VARCHAR(50),
    highlights VARCHAR(255),
    is_public TINYINT(1) DEFAULT 0,
    share_token VARCHAR(32) DEFAULT NULL,
    entry_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (trip_id) REFERENCES trip(trip_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── SEASONAL PRICING ──
CREATE TABLE IF NOT EXISTS seasonal_pricing (
    season_id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    season_name VARCHAR(100) NOT NULL,
    month_start INT NOT NULL,
    month_end INT NOT NULL,
    price_multiplier DECIMAL(4,2) NOT NULL DEFAULT 1.00,
    FOREIGN KEY (package_id) REFERENCES travel_package(package_id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- ================================================
-- SEED DATA
-- ================================================

-- Admin account (password: admin123)
INSERT IGNORE INTO admin (name, email, password, is_approved) VALUES
('Admin', 'admin@voyager.com', '$2y$10$qTaQsjH5oTgC8B.Bp7oBV./lM7S52ZHyVQAVz1wOFG.geJ/itBSHi', 1);

-- Destinations
INSERT IGNORE INTO destination (destination_name, city, country, description) VALUES
('Manali Valley',      'Manali',     'India', 'Snow-capped mountains and serene valleys'),
('Goa Beaches',        'Goa',        'India', 'Sun, sand, and vibrant nightlife'),
('Kerala Backwaters',  'Alleppey',   'India', 'Houseboat cruises through tranquil backwaters'),
('Jaipur Heritage',    'Jaipur',     'India', 'The Pink City — forts, palaces, culture'),
('Andaman Islands',    'Port Blair', 'India', 'Crystal-clear waters and coral reefs'),
('Rishikesh Adventure','Rishikesh',  'India', 'White-water rafting and yoga capital'),
('Leh Ladakh',         'Leh',        'India', 'High-altitude desert and mountain passes'),
('Ooty Hills',         'Ooty',       'India', 'Tea plantations and misty hills');

-- Travel Packages
INSERT IGNORE INTO travel_package (package_name, duration, start_date, end_date, total_cost, discount, available_slots) VALUES
('Manali Snow Escape',       '5 Days / 4 Nights',  '2026-05-10', '2026-05-15', 25000.00, 10, 15),
('Goa Beach Bliss',          '4 Days / 3 Nights',  '2026-06-01', '2026-06-05', 18000.00,  5, 20),
('Kerala Houseboat Cruise',  '6 Days / 5 Nights',  '2026-07-15', '2026-07-21', 35000.00, 15, 10),
('Royal Rajasthan Tour',     '7 Days / 6 Nights',  '2026-08-10', '2026-08-17', 42000.00,  0, 12),
('Andaman Diving Adventure', '5 Days / 4 Nights',  '2026-09-05', '2026-09-10', 48000.00, 20,  8),
('Rishikesh Thrill Camp',    '3 Days / 2 Nights',  '2026-04-20', '2026-04-23', 12000.00,  0, 25),
('Leh Ladakh Road Trip',     '8 Days / 7 Nights',  '2026-10-12', '2026-10-20', 55000.00, 10,  6),
('Ooty Tea Trail',           '4 Days / 3 Nights',  '2026-03-28', '2026-04-01', 15000.00,  5, 18);

-- Hotels
INSERT IGNORE INTO hotel (hotel_name, star_rating, price_per_day, availability_status, location) VALUES
('Budget Inn',         2, 1200.00, 1, 'Various cities'),
('Comfort Stay',       3, 2500.00, 1, 'Various cities'),
('Grand Heritage',     4, 5000.00, 1, 'Various cities'),
('Luxury Palace',      5, 9500.00, 1, 'Various cities'),
('Backpacker Hostel',  1,  600.00, 1, 'Various cities');

-- Transport
INSERT IGNORE INTO transport_options (transport_type, provider_name, cost, availability) VALUES
('Bus (AC Sleeper)',    'RedBus',       1500.00, 1),
('Train (2A)',          'IRCTC',        2200.00, 1),
('Flight (Economy)',    'IndiGo',       5500.00, 1),
('Flight (Business)',   'Air India',   12000.00, 1),
('Self Drive (Car)',    'Zoomcar',      3500.00, 1),
('Cab (Round Trip)',    'Ola Outstation',4000.00, 1);

-- Activities
INSERT IGNORE INTO activities (activity_name, activity_type, cost, destination_id) VALUES
('Paragliding',         'Adventure',   3500.00, 1),
('Scuba Diving',        'Adventure',   5000.00, 5),
('White Water Rafting',  'Adventure',   2000.00, 6),
('Heritage Walking Tour','Cultural',    800.00, 4),
('Houseboat Stay',       'Luxury',     6000.00, 3),
('Beach Party Night',    'Entertainment',1500.00, 2),
('Tea Factory Visit',    'Cultural',    500.00, 8),
('Monastery Trek',       'Adventure',  1200.00, 7);

-- Seasonal Pricing Seed Data (Feature 10)
-- Manali Snow Escape: peak in winter (Dec-Feb), off in monsoon (Jul-Sep)
INSERT IGNORE INTO seasonal_pricing (package_id, season_name, month_start, month_end, price_multiplier) VALUES
(1, 'Peak Season',     12, 2,  1.30),
(1, 'Off Season',       7, 9,  0.80),
-- Goa Beach Bliss: peak in winter (Nov-Feb), off in monsoon (Jun-Sep)
(2, 'Peak Season',     11, 2,  1.25),
(2, 'Off Season',       6, 9,  0.75),
-- Kerala Houseboat: peak in winter (Oct-Feb), off in monsoon (Jun-Aug)
(3, 'Peak Season',     10, 2,  1.20),
(3, 'Off Season',       6, 8,  0.85),
-- Royal Rajasthan: peak in Oct-Mar (winter), off in May-Jul (summer)
(4, 'Peak Season',     10, 3,  1.15),
(4, 'Off Season',       5, 7,  0.80),
-- Andaman Diving: peak in Dec-Apr, off in Jun-Sep (rough seas)
(5, 'Peak Season',     12, 4,  1.35),
(5, 'Off Season',       6, 9,  0.70),
-- Rishikesh: peak in Mar-Jun (rafting), off in Jul-Sep (flooding)
(6, 'Peak Season',      3, 6,  1.20),
(6, 'Off Season',       7, 9,  0.85),
-- Leh Ladakh: peak in Jun-Sep (only access), off in Nov-Mar (closed roads)
(7, 'Peak Season',      6, 9,  1.40),
(7, 'Off Season',      11, 3,  0.65),
-- Ooty Tea Trail: peak in Apr-Jun (summer), off in Nov-Jan
(8, 'Peak Season',      4, 6,  1.20),
(8, 'Off Season',      11, 1,  0.85);

SELECT 'Setup complete! All tables created and seed data inserted.' AS status;

