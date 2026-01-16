-- =====================================================
-- Migration: Add property_features table for AI-extracted data
-- Purpose: Store structured features extracted from property notes
-- =====================================================

-- Create property_features table
CREATE TABLE IF NOT EXISTS property_features (
    id SERIAL PRIMARY KEY,
    property_id INTEGER NOT NULL REFERENCES properties(id) ON DELETE CASCADE,
    
    -- Extracted boolean features
    near_subway BOOLEAN DEFAULT NULL,
    needs_renovation BOOLEAN DEFAULT NULL,
    parking_available BOOLEAN DEFAULT NULL,
    has_elevator BOOLEAN DEFAULT NULL,
    
    -- Extracted numeric features
    estimated_capacity_people INTEGER DEFAULT NULL,
    floor_level INTEGER DEFAULT NULL,
    condition_rating INTEGER DEFAULT NULL CHECK (condition_rating >= 1 AND condition_rating <= 5),
    
    -- Extracted text features
    recommended_use VARCHAR(100) DEFAULT NULL, -- office, retail, logistics, warehouse, etc.
    
    -- Flexible additional features
    amenities JSONB DEFAULT NULL,
    
    -- AI metadata
    confidence_score DECIMAL(3, 2) DEFAULT NULL CHECK (confidence_score >= 0.00 AND confidence_score <= 1.00),
    source_notes_count INTEGER DEFAULT 0,
    raw_ai_response JSONB DEFAULT NULL,
    
    -- Timestamps
    extracted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Constraints
    CONSTRAINT unique_property_features UNIQUE (property_id)
);

-- Create indexes for common queries
CREATE INDEX idx_property_features_property_id ON property_features(property_id);
CREATE INDEX idx_property_features_near_subway ON property_features(near_subway);
CREATE INDEX idx_property_features_recommended_use ON property_features(recommended_use);
CREATE INDEX idx_property_features_confidence_score ON property_features(confidence_score DESC);
CREATE INDEX idx_property_features_extracted_at ON property_features(extracted_at DESC);

-- Create GIN index for JSONB columns
CREATE INDEX idx_property_features_amenities ON property_features USING GIN(amenities);
CREATE INDEX idx_property_features_raw_ai_response ON property_features USING GIN(raw_ai_response);

-- Auto-update updated_at timestamp
CREATE TRIGGER update_property_features_updated_at 
    BEFORE UPDATE ON property_features
    FOR EACH ROW 
    EXECUTE FUNCTION update_updated_at_column();

-- Add helpful comments
COMMENT ON TABLE property_features IS 'AI-extracted structured features from property notes';
COMMENT ON COLUMN property_features.near_subway IS 'Whether property is near subway/public transit';
COMMENT ON COLUMN property_features.needs_renovation IS 'Whether property needs renovation work';
COMMENT ON COLUMN property_features.estimated_capacity_people IS 'Estimated number of people property can accommodate';
COMMENT ON COLUMN property_features.recommended_use IS 'AI-recommended property use type';
COMMENT ON COLUMN property_features.condition_rating IS 'Property condition rating (1=poor, 5=excellent)';
COMMENT ON COLUMN property_features.confidence_score IS 'AI confidence in extracted features (0.00-1.00)';
COMMENT ON COLUMN property_features.raw_ai_response IS 'Full AI API response for debugging';
