-- Create Database
CREATE DATABASE IF NOT EXISTS employee_management;
USE employee_management;

-- Users Table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('employee', 'manager', 'admin') DEFAULT 'employee',
    department VARCHAR(100),
    position VARCHAR(100),
    holiday_balance INT DEFAULT 30,
    leave_balance INT DEFAULT 20,
    overtime_hours DECIMAL(8, 2) DEFAULT 0,
    total_leave_days INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Holidays Table
CREATE TABLE holidays (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    date DATE NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Leave Requests Table
CREATE TABLE requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    type ENUM('annual', 'sick', 'emergency', 'unpaid') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by INT,
    reviewed_at DATETIME,
    reviewer_comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Overtime Table
CREATE TABLE overtime (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    hours DECIMAL(5, 2) NOT NULL,
    description TEXT NOT NULL,
    approved_by VARCHAR(100),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert Demo Users
INSERT INTO users (name, email, password, role, department, position, holiday_balance, leave_balance) VALUES
('Admin User', 'admin@example.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/1Ae', 'admin', 'Management', 'Administrator', 30, 20),
('John Employee', 'employee@example.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/1Ae', 'employee', 'Engineering', 'Developer', 30, 20),
('Manager User', 'manager@example.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/1Ae', 'manager', 'Engineering', 'Team Lead', 30, 20);

-- Insert Demo Holidays
INSERT INTO holidays (name, date, description) VALUES
('New Year Day', '2025-01-01', 'National Holiday'),
('Easter', '2025-04-20', 'Religious Holiday'),
('Independence Day', '2025-07-04', 'National Holiday'),
('Christmas', '2025-12-25', 'Religious Holiday'),
('Boxing Day', '2025-12-26', 'National Holiday');

-- Create Indexes
CREATE INDEX idx_requests_employee ON requests(employee_id);
CREATE INDEX idx_requests_status ON requests(status);
CREATE INDEX idx_overtime_employee ON overtime(employee_id);
CREATE INDEX idx_holidays_date ON holidays(date);