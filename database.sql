-- Create the database
DROP DATABASE IF EXISTS agricultural_data;
CREATE DATABASE agricultural_data;
USE agricultural_data;

-- Users table (with isAdmin column)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    isAdmin TINYINT(1) NOT NULL DEFAULT 0,
    region VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Lands table (replacing land_data, with AUTO_INCREMENT)
CREATE TABLE lands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    region VARCHAR(50) NOT NULL,
    land_size DECIMAL(10,2) NOT NULL,
    irrigation_source VARCHAR(50) NOT NULL,
    primary_crop VARCHAR(50) NOT NULL,
    secondary_crop VARCHAR(50),
    water_depth DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- User wells (related to lands)
CREATE TABLE user_wells (
    id INT AUTO_INCREMENT PRIMARY KEY,
    land_id INT NOT NULL,
    depth DECIMAL(10,2) NOT NULL,
    water_level DECIMAL(10,2),
    quality VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (land_id) REFERENCES lands(id)
);

-- Activity log
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    region VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Initial schemes (temporary table for seeding beneficiary_schemes)
CREATE TABLE initial_schemes_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    eligibility TEXT,
    region_coverage VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Regions table
CREATE TABLE regions (
    region_id INT AUTO_INCREMENT PRIMARY KEY,
    region_name VARCHAR(50) NOT NULL,
    state VARCHAR(50) NOT NULL,
    district VARCHAR(50) NOT NULL,
    agro_climatic_zone VARCHAR(100)
);

-- Land holdings by region
CREATE TABLE land_holdings (
    holding_id INT AUTO_INCREMENT PRIMARY KEY,
    region_id INT NOT NULL,
    size_category ENUM('small', 'medium', 'large') NOT NULL,
    average_size DECIMAL(10,2) NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    FOREIGN KEY (region_id) REFERENCES regions(region_id)
);

-- Irrigation sources
CREATE TABLE irrigation_sources (
    source_id INT AUTO_INCREMENT PRIMARY KEY,
    source_name VARCHAR(50) NOT NULL,
    description TEXT
);

-- Region-wise irrigation source distribution
CREATE TABLE region_irrigation (
    region_id INT NOT NULL,
    source_id INT NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    PRIMARY KEY (region_id, source_id),
    FOREIGN KEY (region_id) REFERENCES regions(region_id),
    FOREIGN KEY (source_id) REFERENCES irrigation_sources(source_id)
);

-- Crops table
CREATE TABLE crops (
    crop_id INT AUTO_INCREMENT PRIMARY KEY,
    crop_name VARCHAR(50) NOT NULL,
    crop_type ENUM('food', 'cash', 'horticulture', 'plantation') NOT NULL,
    season ENUM('kharif', 'rabi', 'zaid', 'perennial') NOT NULL
);

-- Cropping patterns in regions
CREATE TABLE cropping_patterns (
    pattern_id INT AUTO_INCREMENT PRIMARY KEY,
    region_id INT NOT NULL,
    crop_id INT NOT NULL,
    area_percentage DECIMAL(5,2) NOT NULL,
    yield_per_hectare DECIMAL(10,2),
    FOREIGN KEY (region_id) REFERENCES regions(region_id),
    FOREIGN KEY (crop_id) REFERENCES crops(crop_id)
);

-- Regional wells (aggregated well data)
CREATE TABLE regional_wells (
    well_id INT AUTO_INCREMENT PRIMARY KEY,
    region_id INT NOT NULL,
    average_depth DECIMAL(10,2) NOT NULL,
    water_level DECIMAL(10,2),
    quality_index DECIMAL(5,2),
    last_measured_date DATE,
    FOREIGN KEY (region_id) REFERENCES regions(region_id)
);

-- Beneficiary schemes (central repository)
CREATE TABLE beneficiary_schemes (
    scheme_id INT AUTO_INCREMENT PRIMARY KEY,
    scheme_name VARCHAR(100) NOT NULL,
    description TEXT,
    eligibility_criteria TEXT,
    benefits TEXT,
    official_link VARCHAR(255)
);

-- Scheme implementation details per region
CREATE TABLE region_schemes (
    region_id INT NOT NULL,
    scheme_id INT NOT NULL,
    implementation_status ENUM('planned', 'ongoing', 'completed') DEFAULT 'ongoing',
    start_date DATE,
    end_date DATE,
    beneficiaries_count INT,
    PRIMARY KEY (region_id, scheme_id),
    FOREIGN KEY (region_id) REFERENCES regions(region_id),
    FOREIGN KEY (scheme_id) REFERENCES beneficiary_schemes(scheme_id)
);

-- Insert sample data
-- Users
INSERT INTO users (username, password, email, full_name, role, isAdmin, region) VALUES
('admin1', 'hashed_pwd_admin', 'admin1@example.com', 'Admin User', 'admin', 1, NULL),
('farmer1', 'hashed_pwd1', 'farmer1@example.com', 'John Doe', 'user', 0, 'North Region'),
('farmer2', 'hashed_pwd2', 'farmer2@example.com', 'Jane Smith', 'user', 0, 'South Region'),
('farmer3', 'hashed_pwd3', 'farmer3@example.com', 'Raj Patel', 'user', 0, 'West Region');

-- Regions
INSERT INTO regions (region_name, state, district, agro_climatic_zone) VALUES
('North Region', 'Punjab', 'Amritsar', 'Trans-Gangetic Plains'),
('South Region', 'Tamil Nadu', 'Coimbatore', 'Southern Plateau'),
('West Region', 'Gujarat', 'Ahmedabad', 'Western Dry');

-- Irrigation Sources
INSERT INTO irrigation_sources (source_name, description) VALUES
('Canal', 'Surface water canal irrigation'),
('Tube Well', 'Groundwater extraction via tube well'),
('Rainfed', 'Rain-dependent agriculture'),
('Drip Irrigation', 'Water-efficient drip system');

-- Lands
INSERT INTO lands (user_id, region, land_size, irrigation_source, primary_crop, secondary_crop, water_depth) VALUES
(2, 'North Region', 1.50, 'Canal', 'Wheat', 'Barley', 10.25),
(2, 'North Region', 3.75, 'Tube Well', 'Rice', NULL, 15.50),
(3, 'South Region', 2.20, 'Rainfed', 'Cotton', 'Sorghum', NULL),
(3, 'South Region', 6.00, 'Canal', 'Sugarcane', NULL, 12.75),
(4, 'West Region', 4.30, 'Drip Irrigation', 'Maize', 'Soybean', 8.90),
(4, 'West Region', 0.80, 'Rainfed', 'Millets', NULL, NULL);

-- User Wells
INSERT INTO user_wells (land_id, depth, water_level, quality) VALUES
(1, 50.00, 20.00, 'Good'),
(2, 60.00, 25.00, 'Moderate'),
(4, 55.00, 22.50, 'Good'),
(5, 45.00, 18.00, 'Excellent');

-- Activity Log
INSERT INTO activity_log (user_id, action, region) VALUES
(1, 'Created user account', NULL),
(2, 'Added land data', 'North Region'),
(3, 'Added land data', 'South Region'),
(4, 'Updated well data', 'West Region');

-- Initial Schemes Data
INSERT INTO initial_schemes_data (name, description, eligibility, region_coverage) VALUES
('PMKSY', 'Pradhan Mantri Krishi Sinchayee Yojana - Improving farm productivity through better irrigation', 'Farmers with land holdings, Water user associations', 'Nationwide'),
('Soil Health Card', 'Provides information to farmers on nutrient status of their soil with recommendation', 'All farmers', 'Nationwide'),
('National Mission on Sustainable Agriculture', 'Promotes sustainable agriculture practices', 'Farmers practicing sustainable agriculture', 'Nationwide'),
('Micro Irrigation Fund', 'Provides funds for micro-irrigation systems', 'Farmers with small land holdings', 'Nationwide'),
('Rainfed Area Development', 'Development of rainfed areas for sustainable farming', 'Farmers in rainfed areas', 'Selected regions');

-- Beneficiary Schemes (populated from initial_schemes_data)
INSERT INTO beneficiary_schemes (scheme_name, description, eligibility_criteria, benefits)
SELECT name, description, eligibility, region_coverage
FROM initial_schemes_data;

-- Region Schemes
INSERT INTO region_schemes (region_id, scheme_id, implementation_status, start_date, end_date, beneficiaries_count) VALUES
(1, 1, 'ongoing', '2025-01-01', '2025-12-31', 1000),
(1, 2, 'completed', '2024-06-01', '2024-12-31', 800),
(2, 1, 'ongoing', '2025-01-01', '2025-12-31', 1200),
(2, 3, 'planned', '2025-06-01', '2026-06-30', 0),
(3, 2, 'ongoing', '2025-01-01', '2025-12-31', 900);

-- Land Holdings
INSERT INTO land_holdings (region_id, size_category, average_size, percentage) VALUES
(1, 'small', 1.5, 50.00),
(1, 'medium', 3.5, 30.00),
(1, 'large', 7.0, 20.00),
(2, 'small', 1.2, 60.00),
(2, 'medium', 3.0, 25.00),
(2, 'large', 6.5, 15.00),
(3, 'small', 1.8, 45.00),
(3, 'medium', 4.0, 35.00),
(3, 'large', 8.0, 20.00);

-- Region Irrigation
INSERT INTO region_irrigation (region_id, source_id, percentage) VALUES
(1, 1, 40.00),
(1, 2, 30.00),
(1, 3, 20.00),
(1, 4, 10.00),
(2, 1, 20.00),
(2, 2, 25.00),
(2, 3, 35.00),
(2, 4, 20.00),
(3, 1, 15.00),
(3, 2, 35.00),
(3, 3, 30.00),
(3, 4, 20.00);

-- Crops
INSERT INTO crops (crop_name, crop_type, season) VALUES
('Wheat', 'food', 'rabi'),
('Rice', 'food', 'kharif'),
('Cotton', 'cash', 'kharif'),
('Sugarcane', 'cash', 'perennial'),
('Maize', 'food', 'kharif'),
('Millets', 'food', 'kharif'),
('Barley', 'food', 'rabi'),
('Sorghum', 'food', 'kharif'),
('Soybean', 'cash', 'kharif');

-- Cropping Patterns
INSERT INTO cropping_patterns (region_id, crop_id, area_percentage, yield_per_hectare) VALUES
(1, 1, 40.00, 3000.00),
(1, 2, 30.00, 4000.00),
(1, 7, 20.00, 2500.00),
(2, 3, 35.00, 1500.00),
(2, 4, 25.00, 60000.00),
(2, 8, 20.00, 2000.00),
(3, 5, 30.00, 3500.00),
(3, 6, 25.00, 1800.00),
(3, 9, 20.00, 2200.00);

-- Regional Wells
INSERT INTO regional_wells (region_id, average_depth, water_level, quality_index, last_measured_date) VALUES
(1, 50.25, 20.50, 85.00, '2025-04-01'),
(2, 60.75, 25.75, 80.00, '2025-04-01'),
(3, 45.90, 18.90, 90.00, '2025-04-01');


CREATE TABLE user_queries (
    query_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);