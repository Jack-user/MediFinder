-- MediFinder minimal schema
CREATE DATABASE IF NOT EXISTS medifinder CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE medifinder;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('patient', 'pharmacy_owner', 'admin') DEFAULT 'patient',
  created_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS reminders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  remind_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS uploads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  original_name VARCHAR(255) NULL,
  extracted_text MEDIUMTEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Pharmacy registration requests (pending approval)
CREATE TABLE IF NOT EXISTS pharmacy_registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  pharmacy_name VARCHAR(200) NOT NULL,
  business_name VARCHAR(200) NULL,
  license_number VARCHAR(100) NOT NULL,
  license_type ENUM('pharmacy', 'drugstore', 'clinic', 'healthcare_network') NOT NULL,
  license_file_path VARCHAR(500) NULL,
  address TEXT NOT NULL,
  latitude DECIMAL(10, 8) NULL,
  longitude DECIMAL(11, 8) NULL,
  phone VARCHAR(50) NOT NULL,
  email VARCHAR(190) NULL,
  owner_name VARCHAR(200) NOT NULL,
  owner_contact VARCHAR(50) NOT NULL,
  status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  rejection_reason TEXT NULL,
  reviewed_by INT NULL,
  reviewed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_status (status),
  INDEX idx_user (user_id)
);

-- Pharmacies table (approved pharmacies only)
CREATE TABLE IF NOT EXISTS pharmacies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  registration_id INT NULL,
  owner_user_id INT NOT NULL,
  name VARCHAR(200) NOT NULL,
  business_name VARCHAR(200) NULL,
  license_number VARCHAR(100) NOT NULL UNIQUE,
  license_type ENUM('pharmacy', 'drugstore', 'clinic', 'healthcare_network') NOT NULL,
  address TEXT NOT NULL,
  latitude DECIMAL(10, 8) NOT NULL,
  longitude DECIMAL(11, 8) NOT NULL,
  phone VARCHAR(50) NOT NULL,
  email VARCHAR(190) NULL,
  is_active TINYINT(1) DEFAULT 1,
  verified_at DATETIME NULL,
  verified_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (registration_id) REFERENCES pharmacy_registrations(id) ON DELETE SET NULL,
  FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_location (latitude, longitude),
  INDEX idx_owner (owner_user_id),
  INDEX idx_active (is_active)
);

-- Medicines catalog
CREATE TABLE IF NOT EXISTS medicines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL UNIQUE,
  generic_name VARCHAR(200) NULL,
  description TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_name (name)
);

-- Pharmacy inventory
CREATE TABLE IF NOT EXISTS pharmacy_inventory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pharmacy_id INT NOT NULL,
  medicine_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 0,
  price DECIMAL(10, 2) NULL,
  unit VARCHAR(50) DEFAULT 'unit',
  expiry_date DATE NULL,
  last_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (pharmacy_id) REFERENCES pharmacies(id) ON DELETE CASCADE,
  FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
  UNIQUE KEY unique_pharmacy_medicine (pharmacy_id, medicine_id),
  INDEX idx_pharmacy (pharmacy_id),
  INDEX idx_medicine (medicine_id),
  INDEX idx_quantity (quantity)
);

-- Sample medicines
INSERT INTO medicines (name, generic_name) VALUES
('Paracetamol', 'Acetaminophen'),
('Ibuprofen', 'Ibuprofen'),
('Amoxicillin', 'Amoxicillin'),
('Cetirizine', 'Cetirizine'),
('Metformin', 'Metformin'),
('Omeprazole', 'Omeprazole'),
('Dextromethorphan', 'Dextromethorphan'),
('Loratadine', 'Loratadine')
ON DUPLICATE KEY UPDATE name=name;


