-- Create database (optional)
CREATE DATABASE IF NOT EXISTS campusconnectdb;
USE campusconnectdb;

-- Admin Table
CREATE TABLE admin (
  adminID INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL
);

-- Student Table
CREATE TABLE student (
  studentID INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  name VARCHAR(100) NOT NULL,
  department VARCHAR(100)
);

-- Officer Table
CREATE TABLE officer (
  officerID INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  name VARCHAR(100) NOT NULL,
  department VARCHAR(100),
  isRepresentative BOOLEAN DEFAULT FALSE
);

-- Announcement Table
CREATE TABLE announcement (
  announcementID INT AUTO_INCREMENT PRIMARY KEY,
  officerID INT NOT NULL,
  date DATE NOT NULL,
  time TIME NOT NULL,
  subject VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  department VARCHAR(100),
  FOREIGN KEY (officerID) REFERENCES officer(officerID)
    ON DELETE CASCADE
);

-- Chat Table
CREATE TABLE chat (
  chatID INT AUTO_INCREMENT PRIMARY KEY,
  officerID INT NOT NULL,
  studentID INT NOT NULL,
  date DATE NOT NULL,
  time TIME NOT NULL,
  message TEXT NOT NULL,
  FOREIGN KEY (officerID) REFERENCES officer(officerID)
    ON DELETE CASCADE,
  FOREIGN KEY (studentID) REFERENCES student(studentID)
    ON DELETE CASCADE
);

-- Add sender_type column to the existing chat table
ALTER TABLE chat ADD COLUMN sender_type ENUM('student', 'officer') NOT NULL DEFAULT 'student';