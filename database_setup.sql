-- Database Setup Script for Online Examination System
-- Run this script in phpMyAdmin or MySQL console to create the database and tables

-- Create database (if it doesn't exist)
CREATE DATABASE IF NOT EXISTS exam_system;
USE exam_system;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert a default admin user (password: admin123)
-- This will be hashed by the registration system, but for initial setup:
INSERT INTO users (name, email, password, role) VALUES 
('Administrator', 'admin@exam.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE email = email;

-- Create indexes for better performance
CREATE INDEX idx_email ON users(email);
CREATE INDEX idx_role ON users(role);
CREATE INDEX idx_created_at ON users(created_at);

-- Display success message
SELECT 'Database and tables created successfully!' as message;
