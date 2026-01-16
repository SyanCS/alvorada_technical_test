<?php
$propertyData = $property;
$notes = $propertyData['notes'] ?? [];
$createdDate = new DateTime($propertyData['created_at']);
?>

<style>
.property-header {
    border-bottom: 2px solid #e0e0e0;
    padding-bottom: 20px;
    margin-bottom: 25px;
}

.property-name {
    font-size: 28px;
    color: #333;
    margin-bottom: 10px;
    font-weight: 600;
}

.property-address {
    color: #666;
    font-size: 15px;
    line-height: 1.5;
}

.property-meta {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-bottom: 30px;
}

.meta-item {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
}

.meta-label {
    font-size: 11px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 5px;
    font-weight: 600;
}

.meta-value {
    font-size: 16px;
    color: #333;
    font-weight: 600;
}

.section-title {
    font-size: 20px;
    color: #333;
    margin-bottom: 20px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.add-note-form {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 25px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 500;
    font-size: 14px;
}

.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-family: inherit;
    font-size: 14px;
    resize: vertical;
    min-height: 100px;
    transition: border-color 0.3s;
}

.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    width: 100%;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.notes-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.note-item {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

.note-content {
    color: #333;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 8px;
}

.note-meta {
    color: #999;
    font-size: 11px;
}

.no-notes {
    text-align: center;
    padding: 30px;
    color: #999;
    font-size: 14px;
}

.success-message {
    background: #d4edda;
    border: 2px solid #c3e6cb;
    color: #155724;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-size: 14px;
}

.error-message {
    background: #f8d7da;
    border: 2px solid #f5c6cb;
    color: #721c24;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-size: 14px;
}

/* AI Features Section */
.ai-features-section {
    background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
    border: 2px solid #667eea40;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
}

.ai-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.ai-section-title {
    font-size: 20px;
    color: #333;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-extract {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-extract:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-extract:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.feature-card {
    background: white;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #667eea;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.amenities-card {
    margin-bottom: 25px;
}

.feature-label {
    font-size: 11px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
    font-weight: 600;
}

.feature-value {
    font-size: 16px;
    color: #333;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}

.feature-value.boolean-true {
    color: #28a745;
}

.feature-value.boolean-false {
    color: #dc3545;
}

.feature-value.unknown {
    color: #999;
}

.confidence-badge {
    display: inline-block;
    background: #667eea;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.confidence-high {
    background: #28a745;
}

.confidence-medium {
    background: #ffc107;
    color: #333;
}

.confidence-low {
    background: #dc3545;
}

.amenities-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}

.amenity-tag {
    background: #667eea20;
    color: #667eea;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.no-features {
    text-align: center;
    padding: 30px;
    color: #666;
    font-size: 14px;
}

.no-features-icon {
    font-size: 48px;
    margin-bottom: 10px;
}

.loading-spinner {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid #ffffff40;
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.ai-note {
    background: #fff3cd;
    border: 1px solid #ffc107;
    color: #856404;
    padding: 12px;
    border-radius: 8px;
    font-size: 13px;
    margin-top: 15px;
}
</style>

<div class="property-header">
    <div class="property-name"><?php echo htmlspecialchars($propertyData['name']); ?></div>
    <div class="property-address">📍 <?php echo htmlspecialchars($propertyData['address']); ?></div>
</div>

<div class="property-meta">
    <div class="meta-item">
        <div class="meta-label">Latitude</div>
        <div class="meta-value"><?php echo number_format($propertyData['latitude'], 6); ?>°</div>
    </div>
    <div class="meta-item">
        <div class="meta-label">Longitude</div>
        <div class="meta-value"><?php echo number_format($propertyData['longitude'], 6); ?>°</div>
    </div>
    <div class="meta-item">
        <div class="meta-label">Created</div>
        <div class="meta-value"><?php echo $createdDate->format('M j, Y'); ?></div>
    </div>
    <div class="meta-item">
        <div class="meta-label">Total Notes</div>
        <div class="meta-value"><?php echo count($notes); ?></div>
    </div>
</div>

<!-- AI Features Section -->
<div class="ai-features-section" id="aiFeaturesSection">
    <div class="ai-section-header">
        <div class="ai-section-title">🤖 AI-Extracted Features</div>
        <button class="btn-extract" id="extractBtn" onclick="extractFeatures()">
            🔄 Extract Features
        </button>
    </div>
    
    <div id="featuresContainer">
        <div class="no-features">
            <div class="no-features-icon">🤖</div>
            <div>Click "Extract Features" to analyze notes and extract structured data</div>
        </div>
    </div>
</div>

<div class="section-title">📝 Notes</div>

<div id="messageContainer"></div>

<div class="add-note-form">
    <form id="addNoteForm">
        <div class="form-group">
            <label for="noteText">Add a Note</label>
            <textarea 
                id="noteText" 
                name="note" 
                placeholder="Enter your note here... (minimum 3 characters)"
                required
                minlength="3"
            ></textarea>
        </div>
        <button type="submit" class="btn btn-primary" id="submitBtn">
            ✍️ Add Note
        </button>
    </form>
</div>

<div id="notesListContainer">
    <div class="notes-list" id="notesList">
        <?php if (empty($notes)): ?>
            <div class="no-notes">No notes yet. Add your first note above!</div>
        <?php else: ?>
            <?php foreach ($notes as $note): ?>
                <?php $noteDate = new DateTime($note['created_at']); ?>
                <div class="note-item">
                    <div class="note-content"><?php echo htmlspecialchars($note['note']); ?></div>
                    <div class="note-meta">
                        Added on <?php echo $noteDate->format('M j, Y \a\t g:i A'); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
const propertyId = <?php echo $propertyData['id']; ?>;

// Load features on page load
window.addEventListener('DOMContentLoaded', () => {
    loadFeatures();
});

// Load existing features
async function loadFeatures() {
    try {
        const response = await fetch(`/api/property_features.php?property_id=${propertyId}`);
        const data = await response.json();
        
        if (data.success && data.has_features) {
            displayFeatures(data.features);
        }
    } catch (error) {
        console.error('Error loading features:', error);
    }
}

// Extract features from notes
async function extractFeatures() {
    const btn = document.getElementById('extractBtn');
    const container = document.getElementById('featuresContainer');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="loading-spinner"></span> Extracting...';
    
    container.innerHTML = `
        <div class="no-features">
            <div class="no-features-icon">⏳</div>
            <div>Analyzing notes with AI... This may take a few seconds.</div>
        </div>
    `;
    
    try {
        const response = await fetch('/api/extract_features.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                property_id: propertyId,
                force_refresh: true
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            displayFeatures(data.features);
            showMessage('✅ Features extracted successfully!', 'success');
        } else {
            container.innerHTML = `
                <div class="no-features">
                    <div class="no-features-icon">⚠️</div>
                    <div>${data.message || 'Failed to extract features'}</div>
                </div>
            `;
            showMessage('❌ ' + (data.message || 'Failed to extract features'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        container.innerHTML = `
            <div class="no-features">
                <div class="no-features-icon">❌</div>
                <div>Error extracting features. Please try again.</div>
            </div>
        `;
        showMessage('❌ Error extracting features', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '🔄 Extract Features';
    }
}

// Display extracted features
function displayFeatures(features) {
    const container = document.getElementById('featuresContainer');
    
    let html = '<div class="features-grid">';
    
    // Near Subway
    if (features.near_subway !== null && features.near_subway !== undefined) {
        const value = features.near_subway ? 
            '<span class="boolean-true">✓ Yes</span>' : 
            '<span class="boolean-false">✗ No</span>';
        html += `
            <div class="feature-card">
                <div class="feature-label">Near Subway</div>
                <div class="feature-value">${value}</div>
            </div>
        `;
    }
    
    // Needs Renovation
    if (features.needs_renovation !== null && features.needs_renovation !== undefined) {
        const value = features.needs_renovation ? 
            '<span class="boolean-true">✓ Yes</span>' : 
            '<span class="boolean-false">✗ No</span>';
        html += `
            <div class="feature-card">
                <div class="feature-label">Needs Renovation</div>
                <div class="feature-value">${value}</div>
            </div>
        `;
    }
    
    // Parking Available
    if (features.parking_available !== null && features.parking_available !== undefined) {
        const value = features.parking_available ? 
            '<span class="boolean-true">✓ Available</span>' : 
            '<span class="boolean-false">✗ Not Available</span>';
        html += `
            <div class="feature-card">
                <div class="feature-label">Parking</div>
                <div class="feature-value">${value}</div>
            </div>
        `;
    }
    
    // Has Elevator
    if (features.has_elevator !== null && features.has_elevator !== undefined) {
        const value = features.has_elevator ? 
            '<span class="boolean-true">✓ Yes</span>' : 
            '<span class="boolean-false">✗ No</span>';
        html += `
            <div class="feature-card">
                <div class="feature-label">Elevator</div>
                <div class="feature-value">${value}</div>
            </div>
        `;
    }
    
    // Estimated Capacity
    if (features.estimated_capacity_people) {
        html += `
            <div class="feature-card">
                <div class="feature-label">Capacity</div>
                <div class="feature-value">👥 ${features.estimated_capacity_people} people</div>
            </div>
        `;
    }
    
    // Floor Level
    if (features.floor_level) {
        html += `
            <div class="feature-card">
                <div class="feature-label">Floor Level</div>
                <div class="feature-value">🏢 Floor ${features.floor_level}</div>
            </div>
        `;
    }
    
    // Condition Rating
    if (features.condition_rating) {
        html += `
            <div class="feature-card">
                <div class="feature-label">Condition</div>
                <div class="feature-value">⭐ ${features.condition_rating}/5</div>
            </div>
        `;
    }
    
    // Recommended Use
    if (features.recommended_use) {
        html += `
            <div class="feature-card">
                <div class="feature-label">Best For</div>
                <div class="feature-value">🏢 ${capitalize(features.recommended_use)}</div>
            </div>
        `;
    }
    
    html += '</div>';
    
    // Amenities
    if (features.amenities && features.amenities.length > 0) {
        html += `
            <div class="feature-card amenities-card">
                <div class="feature-label">Amenities</div>
                <div class="amenities-list">
                    ${features.amenities.map(a => `<span class="amenity-tag">${capitalize(a)}</span>`).join('')}
                </div>
            </div>
        `;
    }
    
    // Confidence Score
    if (features.confidence_score !== null && features.confidence_score !== undefined) {
        const score = parseFloat(features.confidence_score);
        const percentage = Math.round(score * 100);
        let badgeClass = 'confidence-low';
        if (score >= 0.8) badgeClass = 'confidence-high';
        else if (score >= 0.6) badgeClass = 'confidence-medium';
        
        html += `
            <div class="feature-card">
                <div class="feature-label">AI Confidence</div>
                <div class="feature-value">
                    <span class="confidence-badge ${badgeClass}">${percentage}%</span>
                </div>
            </div>
        `;
    }
    
    container.innerHTML = html;
}

// Helper function
function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// Add note via AJAX
async function addNote(noteText) {
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = '⏳ Adding...';

    try {
        const response = await fetch('/api/add_note.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                property_id: propertyId,
                note: noteText
            })
        });

        const data = await response.json();

        if (data.success) {
            showMessage('✅ Note added successfully!', 'success');
            document.getElementById('noteText').value = '';
            
            // Reload page to show new note
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showMessage('❌ ' + (data.message || 'Failed to add note'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('❌ Failed to add note. Please try again.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = '✍️ Add Note';
    }
}

// Show message
function showMessage(message, type) {
    const container = document.getElementById('messageContainer');
    const className = type === 'success' ? 'success-message' : 'error-message';
    
    container.innerHTML = `<div class="${className}">${message}</div>`;
    
    setTimeout(() => {
        container.innerHTML = '';
    }, 5000);
}

// Form submit handler
document.getElementById('addNoteForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const noteText = document.getElementById('noteText').value.trim();
    
    if (noteText.length < 3) {
        showMessage('❌ Note must be at least 3 characters long', 'error');
        return;
    }

    await addNote(noteText);
});
</script>
