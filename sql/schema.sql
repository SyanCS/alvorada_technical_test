-- =====================================================
-- Alvorada Property Research System - Database Schema
-- =====================================================

-- Drop tables if they exist (for fresh setup)
DROP TABLE IF EXISTS notes;
DROP TABLE IF EXISTS properties;

-- =====================================================
-- Properties Table
-- =====================================================
CREATE TABLE properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(500) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    extra_field TEXT COMMENT 'Additional data from geolocation API (e.g., confidence score, display_name, type)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_latitude_longitude (latitude, longitude),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Notes Table
-- =====================================================
CREATE TABLE notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    INDEX idx_property_id (property_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Sample Data (Optional - for testing)
-- =====================================================
-- INSERT INTO properties (name, address, latitude, longitude, extra_field) 
-- VALUES 
--     ('Sample Property', '123 Main St, New York, NY', 40.7128, -74.0060, '{"type": "residential", "confidence": 0.95}'),
--     ('Tech Office', '1 Infinite Loop, Cupertino, CA', 37.3318, -122.0312, '{"type": "commercial", "confidence": 1.0}');

-- INSERT INTO notes (property_id, note) 
-- VALUES 
--     (1, 'Great location in downtown area'),
--     (1, 'Needs renovation'),
--     (2, 'Prime tech hub location');


