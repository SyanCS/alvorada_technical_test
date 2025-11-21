<style>
.success-icon {
    text-align: center;
    font-size: 64px;
    margin-bottom: 20px;
}

h1 {
    color: #10b981;
    margin-bottom: 10px;
    font-size: 32px;
    text-align: center;
}

.subtitle {
    color: #666;
    text-align: center;
    margin-bottom: 30px;
}

.property-card {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 20px;
}

.property-field {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e0e0e0;
}

.property-field:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.field-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 5px;
}

.field-value {
    font-size: 16px;
    color: #333;
    font-weight: 500;
}

.coordinates {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    background: white;
    padding: 15px;
    border-radius: 8px;
}

.btn {
    display: inline-block;
    padding: 14px 30px;
    margin: 10px 5px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    text-align: center;
    transition: transform 0.2s;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-secondary {
    background: #e0e0e0;
    color: #333;
}

.btn:hover {
    transform: translateY(-2px);
}

.actions {
    text-align: center;
    margin-top: 30px;
}
</style>

<div class="success-icon">✅</div>
<h1>Property Created Successfully!</h1>
<p class="subtitle">Your property has been saved and enriched with location data</p>

<div class="property-card">
    <div class="property-field">
        <div class="field-label">Property Name</div>
        <div class="field-value"><?php echo htmlspecialchars($property['name']); ?></div>
    </div>
    
    <div class="property-field">
        <div class="field-label">Address</div>
        <div class="field-value"><?php echo htmlspecialchars($property['address']); ?></div>
    </div>
    
    <div class="property-field">
        <div class="field-label">Location Coordinates</div>
        <div class="coordinates">
            <div>
                <div class="field-label">Latitude</div>
                <div class="field-value"><?php echo number_format($property['latitude'], 6); ?>°</div>
            </div>
            <div>
                <div class="field-label">Longitude</div>
                <div class="field-value"><?php echo number_format($property['longitude'], 6); ?>°</div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($property['extra_field'])): 
        $extra = is_array($property['extra_field']) ? $property['extra_field'] : json_decode($property['extra_field'], true);
        if ($extra && isset($extra['importance'])):
    ?>
    <div class="property-field">
        <div class="field-label">Location Confidence</div>
        <div class="field-value"><?php echo round($extra['importance'] * 100); ?>%</div>
    </div>
    <?php endif; endif; ?>
</div>

<div class="actions">
    <a href="<?php echo htmlspecialchars($mapUrl); ?>" class="btn btn-primary">
        📍 View on Map
    </a>
    <a href="/" class="btn btn-secondary">
        ➕ Add Another Property
    </a>
</div>

