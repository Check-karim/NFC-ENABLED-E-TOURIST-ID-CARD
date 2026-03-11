-- NFC-Enabled E-Tourist ID Card System
-- Database: nfc_tourist_db

CREATE DATABASE IF NOT EXISTS nfc_tourist_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nfc_tourist_db;

-- Admin users table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tourists table
CREATE TABLE IF NOT EXISTS tourists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_uid VARCHAR(50) DEFAULT NULL COMMENT 'NFC card UID',
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) DEFAULT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    nationality VARCHAR(100) NOT NULL,
    passport_number VARCHAR(50) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('Male','Female','Other') NOT NULL DEFAULT 'Male',
    address TEXT DEFAULT NULL,
    emergency_contact VARCHAR(100) DEFAULT NULL,
    emergency_phone VARCHAR(30) DEFAULT NULL,
    photo VARCHAR(255) DEFAULT NULL COMMENT 'Path to uploaded photo',
    visa_type VARCHAR(50) DEFAULT NULL,
    entry_date DATE DEFAULT NULL,
    exit_date DATE DEFAULT NULL,
    status ENUM('Active','Expired','Revoked') NOT NULL DEFAULT 'Active',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- NFC card activity log
CREATE TABLE IF NOT EXISTS nfc_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tourist_id INT DEFAULT NULL,
    card_uid VARCHAR(50) NOT NULL,
    action ENUM('READ','WRITE') NOT NULL,
    details TEXT DEFAULT NULL,
    performed_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tourist_id) REFERENCES tourists(id) ON DELETE SET NULL,
    FOREIGN KEY (performed_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Insert default admin
INSERT INTO admins (username, password, full_name) VALUES
('admin', 'admin', 'System Administrator');
