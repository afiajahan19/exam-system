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

-- Create exam_rooms table
CREATE TABLE IF NOT EXISTS exam_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subject VARCHAR(100) NOT NULL,
    room_code VARCHAR(20) NOT NULL UNIQUE,
    created_by INT NOT NULL,
    duration INT NOT NULL COMMENT 'Duration in minutes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_room_code (room_code),
    INDEX idx_created_by (created_by)
);

-- Create questions table
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_room_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('mcq', 'descriptive') NOT NULL DEFAULT 'mcq',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_room_id) REFERENCES exam_rooms(id) ON DELETE CASCADE,
    INDEX idx_exam_room_id (exam_room_id),
    INDEX idx_question_type (question_type)
);

-- Create options table for MCQ questions
CREATE TABLE IF NOT EXISTS options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    option_text TEXT NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    INDEX idx_question_id (question_id),
    INDEX idx_is_correct (is_correct)
);

-- Display success message
SELECT 'Database and tables created successfully!' as message;
