-- University ERP System — database schema (MySQL / TiDB compatible)
-- Created for the Vercel deploy: the original repo shipped no schema file.
-- Tables were inferred from the PHP code's queries. Canonical date column
-- for attendance is `attendance_date` (code using `att_date` was fixed).
-- Run this in TiDB Cloud SQL Editor, or open /setup.php once after deploy
-- (setup.php runs this file and seeds the demo accounts).

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','faculty','student','accountant') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  roll_no VARCHAR(20) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL DEFAULT '',
  phone VARCHAR(20) NOT NULL DEFAULT '',
  department VARCHAR(50) NOT NULL DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS faculty (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL DEFAULT '',
  phone VARCHAR(20) NOT NULL DEFAULT '',
  department VARCHAR(50) NOT NULL DEFAULT '',
  designation VARCHAR(50) NOT NULL DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS courses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_code VARCHAR(20) NOT NULL UNIQUE,
  course_name VARCHAR(100) NOT NULL,
  credits INT NOT NULL DEFAULT 3,
  department VARCHAR(50) NOT NULL DEFAULT '',
  faculty_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS enrollments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  course_id INT NOT NULL,
  enrolled_at DATE NULL,
  UNIQUE KEY uq_enrollment (student_id, course_id)
);

CREATE TABLE IF NOT EXISTS attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  course_id INT NOT NULL,
  attendance_date DATE NOT NULL,
  status ENUM('Present','Absent') NOT NULL DEFAULT 'Absent',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_attendance (student_id, course_id, attendance_date)
);

CREATE TABLE IF NOT EXISTS marks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  course_id INT NOT NULL,
  exam_type VARCHAR(20) NOT NULL,
  marks_obtained DECIMAL(6,2) NOT NULL DEFAULT 0,
  total_marks DECIMAL(6,2) NOT NULL DEFAULT 100,
  exam_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS fee_structure (
  id INT AUTO_INCREMENT PRIMARY KEY,
  class VARCHAR(50) NOT NULL,
  semester VARCHAR(20) NOT NULL DEFAULT '',
  amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  due_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS fee_payments (
  payment_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  fee_id INT NULL,
  amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0,
  payment_date DATE NULL,
  receipt_no VARCHAR(50) NOT NULL DEFAULT '',
  collected_by INT NULL,
  payment_method VARCHAR(20) NOT NULL DEFAULT 'Cash',
  remarks TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS backup_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  file_name VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Demo seed (non-user data only; demo users are created by setup.php
-- with password_hash() at runtime, so no password hash is stored here).
INSERT IGNORE INTO fee_structure (id, class, semester, amount, due_date) VALUES
  (1, 'BSCS', 'Semester 1', 50000.00, '2026-12-31'),
  (2, 'BBA', 'Semester 1', 45000.00, '2026-12-31');
