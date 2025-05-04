-- Create database
CREATE DATABASE IF NOT EXISTS agricultural_data;
USE agricultural_data;

-- Create tables
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    isAdmin BOOLEAN DEFAULT FALSE,
    region VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE lands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    region VARCHAR(50) NOT NULL,
    land_size DECIMAL(10,2),
    irrigation_source VARCHAR(50),
    primary_crop VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE user_wells (
    id INT AUTO_INCREMENT PRIMARY KEY,
    land_id INT,
    water_depth DECIMAL(10,2),
    pump_type VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (land_id) REFERENCES lands(id) ON DELETE CASCADE
);

CREATE TABLE user_queries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    query_text TEXT NOT NULL,
    status ENUM('pending', 'resolved') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    region VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE regions (
    region_id INT AUTO_INCREMENT PRIMARY KEY,
    region_name VARCHAR(50) NOT NULL UNIQUE,
    state VARCHAR(50),
    district VARCHAR(50),
    agro_climatic_zone VARCHAR(50)
);

CREATE TABLE land_holdings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    region_id INT,
    holding_size VARCHAR(50),
    count INT,
    FOREIGN KEY (region_id) REFERENCES regions(region_id) ON DELETE CASCADE
);

CREATE TABLE irrigation_sources (
    source_id INT AUTO_INCREMENT PRIMARY KEY,
    source_name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE region_irrigation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    region_id INT,
    source_id INT,
    area_irrigated DECIMAL(10,2),
    FOREIGN KEY (region_id) REFERENCES regions(region_id) ON DELETE CASCADE,
    FOREIGN KEY (source_id) REFERENCES irrigation_sources(source_id) ON DELETE CASCADE
);

CREATE TABLE crops (
    crop_id INT AUTO_INCREMENT PRIMARY KEY,
    crop_name VARCHAR(50) NOT NULL UNIQUE,
    crop_type VARCHAR(50),
    season VARCHAR(50)
);

CREATE TABLE cropping_patterns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    region_id INT,
    crop_id INT,
    area_covered DECIMAL(10,2),
    season VARCHAR(50),
    FOREIGN KEY (region_id) REFERENCES regions(region_id) ON DELETE CASCADE,
    FOREIGN KEY (crop_id) REFERENCES crops(crop_id) ON DELETE CASCADE
);

CREATE TABLE regional_wells (
    id INT AUTO_INCREMENT PRIMARY KEY,
    region_id INT,
    well_type VARCHAR(50),
    count INT,
    average_depth DECIMAL(10,2),
    FOREIGN KEY (region_id) REFERENCES regions(region_id) ON DELETE CASCADE
);

CREATE TABLE beneficiary_schemes (
    scheme_id INT AUTO_INCREMENT PRIMARY KEY,
    scheme_name VARCHAR(255) NOT NULL,
    description TEXT,
    eligibility_criteria TEXT,
    benefits TEXT,
    official_link VARCHAR(255)
);

CREATE TABLE region_schemes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    region_id INT,
    scheme_id INT,
    implementation_status ENUM('planned', 'ongoing', 'completed') NOT NULL,
    beneficiaries_count INT,
    FOREIGN KEY (region_id) REFERENCES regions(region_id) ON DELETE CASCADE,
    FOREIGN KEY (scheme_id) REFERENCES beneficiary_schemes(scheme_id) ON DELETE CASCADE
);

CREATE TABLE initial_schemes_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scheme_name VARCHAR(255) NOT NULL,
    region VARCHAR(50),
    beneficiaries_count INT,
    implementation_status ENUM('planned', 'ongoing', 'completed')
);

-- Truncate Queries: Clear data while preserving users 5, 6, 7 and handling foreign key constraints
SET FOREIGN_KEY_CHECKS = 0;

-- Delete user-related data for users other than 5, 6, 7
DELETE FROM user_wells WHERE land_id IN (SELECT id FROM lands WHERE user_id NOT IN (5, 6, 7));
DELETE FROM lands WHERE user_id NOT IN (5, 6, 7);
DELETE FROM activity_log WHERE user_id NOT IN (5, 6, 7);
DELETE FROM user_queries WHERE user_id NOT IN (5, 6, 7);
DELETE FROM users WHERE id NOT IN (5, 6, 7);

-- Delete non-user-specific data
DELETE FROM cropping_patterns;
DELETE FROM land_holdings;
DELETE FROM region_irrigation;
DELETE FROM region_schemes;
DELETE FROM regions;
DELETE FROM irrigation_sources;
DELETE FROM beneficiary_schemes;

-- Optional: Uncomment to clear these
-- DELETE FROM crops;
-- DELETE FROM regional_wells;
-- DELETE FROM initial_schemes_data;

SET FOREIGN_KEY_CHECKS = 1;

-- Insert Sample Data for Testing Graphs (Last 8 Days: April 17–25, 2025)

-- Users (IDs 5, 6, 7)
INSERT INTO users (id, full_name, username, email, password, isAdmin, region, created_at) VALUES
(5, 'Akshaya', 'admin', 'bodduakshaya22@gmail.com', '$2y$10$WbuBTbc4.39bwqxRzv8Mgu9ITqXySvq0726N0d8.cTlrROhBJpn9i', 1, 'North Region', '2025-04-16 10:00:00'),
(6, 'Bhavesh', 'user', 'bhavesh@gmail.com', '$2y$10$IOJqSeYPLPxgNFZdckjU5u4boBBwaqrd0dXiJiLYct6PlNgrcB8X6', 0, 'South Region', '2025-04-16 11:00:00'),
(7, 'John Doe', 'user1', 'john@gmail.com', '$2y$10$examplehash1234567890abcdef', 0, 'West Region', '2025-04-16 12:00:00');

-- Regions
INSERT INTO regions (region_id, region_name, state, district, agro_climatic_zone) VALUES
(1, 'North Region', 'Punjab', 'Ludhiana', 'Tropical'),
(2, 'South Region', 'Tamil Nadu', 'Coimbatore', 'Sub-Tropical'),
(3, 'West Region', 'Gujarat', 'Ahmedabad', 'Arid');

-- Lands (Bar Chart Data: Land Size by Region)
INSERT INTO lands (user_id, region, land_size, irrigation_source, primary_crop, created_at) VALUES
(5, 'North Region', 2.50, 'Tube Well', 'Wheat', '2025-04-17 09:00:00'),
(5, 'North Region', 1.80, 'Canal', 'Rice', '2025-04-18 10:00:00'),
(6, 'South Region', 3.00, 'Rainfed', 'Cotton', '2025-04-19 11:00:00'),
(6, 'South Region', 2.20, 'Drip Irrigation', 'Sugarcane', '2025-04-20 12:00:00'),
(7, 'West Region', 4.00, 'Tube Well', 'Maize', '2025-04-21 13:00:00'),
(7, 'West Region', 3.50, 'Sprinkler', 'Groundnut', '2025-04-22 14:00:00');

-- Activity Log (Line Chart Data: Activity Trends Over 8 Days)
INSERT INTO activity_log (user_id, action, region, created_at) VALUES
(5, 'Logged in', 'North Region', '2025-04-17 08:00:00'),
(5, 'Added land data', 'North Region', '2025-04-17 09:00:00'),
(5, 'Submitted query', 'North Region', '2025-04-17 10:00:00'),
(6, 'Logged in', 'South Region', '2025-04-18 09:00:00'),
(6, 'Updated profile', 'South Region', '2025-04-18 10:00:00'),
(7, 'Logged in', 'West Region', '2025-04-19 10:00:00'),
(7, 'Updated scheme: Irrigation Subsidy', NULL, '2025-04-19 11:00:00'),
(5, 'Added land data', 'North Region', '2025-04-20 11:00:00'),
(6, 'Logged in', 'South Region', '2025-04-20 12:00:00'),
(7, 'Logged out', 'West Region', '2025-04-21 13:00:00'),
(5, 'Submitted query', 'North Region', '2025-04-22 14:00:00'),
(6, 'Added land data', 'South Region', '2025-04-23 15:00:00'),
(7, 'Logged in', 'West Region', '2025-04-24 16:00:00'),
(5, 'Logged in', 'North Region', '2025-04-25 08:00:00');

-- Beneficiary Schemes
INSERT INTO beneficiary_schemes (scheme_id, scheme_name, description, eligibility_criteria, benefits, official_link) VALUES
(1, 'Irrigation Subsidy', 'Subsidies for irrigation equipment', 'Farmers with <5ha', '50% cost coverage', 'http://irrigation.gov'),
(2, 'Crop Insurance', 'Insurance for crop losses', 'All farmers', 'Risk coverage', 'http://insurance.gov'),
(3, 'Soil Health Program', 'Soil testing and improvement', 'Farmers in arid regions', 'Free testing kits', 'http://soilhealth.gov');

-- Region Schemes (Pie Chart Data: Beneficiaries by Status)
INSERT INTO region_schemes (region_id, scheme_id, implementation_status, beneficiaries_count) VALUES
(1, 1, 'ongoing', 150),
(1, 2, 'planned', 80),
(2, 1, 'ongoing', 100),
(2, 3, 'completed', 120),
(3, 2, 'planned', 90),
(3, 3, 'completed', 60);


INSERT INTO irrigation_sources (source_name) VALUES
('Canal'),
('Tube Well'),
('Open Well'),
('Rainfed'),
('Drip Irrigation'),
('Sprinkler'),
('River'),
('Tank'),
('Bore Well'),
('Pond');


ALTER TABLE regions DROP INDEX region_name;

ALTER TABLE user_queries 
MODIFY COLUMN status TINYINT(1) DEFAULT 0 COMMENT '0=unread, 1=read',
ADD COLUMN admin_notes TEXT NULL;