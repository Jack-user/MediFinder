-- Simple migration: Add role column to users table
-- Run this in phpMyAdmin or MySQL command line

USE medifinder;

-- Add role column to users table
ALTER TABLE users 
ADD COLUMN role ENUM('patient', 'pharmacy_owner', 'admin') DEFAULT 'patient' 
AFTER password_hash;


