-- =====================================================
-- Alvorada Property Research System - Database Schema
-- PostgreSQL + PostGIS for Advanced Geospatial Features
-- =====================================================

-- Enable PostGIS extension
CREATE EXTENSION IF NOT EXISTS postgis;

-- Drop tables if they exist (for fresh setup)
DROP TABLE IF EXISTS notes CASCADE;
DROP TABLE IF EXISTS properties CASCADE;

-- =====================================================
-- Properties Table with PostGIS Geometry
-- =====================================================
CREATE TABLE properties (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(500) NOT NULL,
    location GEOGRAPHY(Point, 4326) NOT NULL, -- PostGIS point type (WGS 84)
    extra_field JSONB, -- JSON for additional data from geolocation API
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create spatial index on location for fast geo queries
CREATE INDEX idx_properties_location ON properties USING GIST(location);

-- Create regular indexes
CREATE INDEX idx_properties_created_at ON properties(created_at DESC);
CREATE INDEX idx_properties_name ON properties(name);

-- Create GIN index for JSONB extra_field
CREATE INDEX idx_properties_extra_field ON properties USING GIN(extra_field);

-- =====================================================
-- Notes Table
-- =====================================================
CREATE TABLE notes (
    id SERIAL PRIMARY KEY,
    property_id INTEGER NOT NULL REFERENCES properties(id) ON DELETE CASCADE,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for notes
CREATE INDEX idx_notes_property_id ON notes(property_id);
CREATE INDEX idx_notes_created_at ON notes(created_at DESC);

-- =====================================================
-- Helper Functions
-- =====================================================

-- Function to automatically update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Triggers to auto-update updated_at
CREATE TRIGGER update_properties_updated_at BEFORE UPDATE ON properties
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_notes_updated_at BEFORE UPDATE ON notes
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- =====================================================
-- Useful Spatial Functions (Examples)
-- =====================================================

-- Function to find properties within radius (in meters)
CREATE OR REPLACE FUNCTION find_properties_within_radius(
    lat DOUBLE PRECISION,
    lon DOUBLE PRECISION,
    radius_meters INTEGER
)
RETURNS TABLE (
    id INTEGER,
    name VARCHAR(255),
    address VARCHAR(500),
    distance_meters DOUBLE PRECISION
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        p.id,
        p.name,
        p.address,
        ST_Distance(p.location, ST_MakePoint(lon, lat)::geography) as distance_meters
    FROM properties p
    WHERE ST_DWithin(
        p.location,
        ST_MakePoint(lon, lat)::geography,
        radius_meters
    )
    ORDER BY distance_meters;
END;
$$ LANGUAGE plpgsql;

-- =====================================================
-- Sample Data (Optional - for testing)
-- =====================================================
-- INSERT INTO properties (name, address, location, extra_field) 
-- VALUES 
--     (
--         'Sample Property NYC', 
--         '123 Main St, New York, NY',
--         ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326)::geography,
--         '{"type": "residential", "confidence": 0.95, "importance": 0.8}'::jsonb
--     ),
--     (
--         'Tech Office Cupertino', 
--         '1 Infinite Loop, Cupertino, CA',
--         ST_SetSRID(ST_MakePoint(-122.0312, 37.3318), 4326)::geography,
--         '{"type": "commercial", "confidence": 1.0, "importance": 0.95}'::jsonb
--     );

-- INSERT INTO notes (property_id, note) 
-- VALUES 
--     (1, 'Great location in downtown area'),
--     (1, 'Needs renovation'),
--     (2, 'Prime tech hub location');

-- =====================================================
-- Useful Queries for Property Research
-- =====================================================

-- Find properties within 5km of a point
-- SELECT * FROM find_properties_within_radius(40.7128, -74.0060, 5000);

-- Get distance between two properties
-- SELECT 
--     ST_Distance(
--         (SELECT location FROM properties WHERE id = 1),
--         (SELECT location FROM properties WHERE id = 2)
--     ) / 1000 as distance_km;

-- Find nearest 10 properties to a location
-- SELECT 
--     id, name, address,
--     ST_Distance(location, ST_MakePoint(-74.0060, 40.7128)::geography) / 1000 as distance_km
-- FROM properties
-- ORDER BY location <-> ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326)::geography
-- LIMIT 10;
