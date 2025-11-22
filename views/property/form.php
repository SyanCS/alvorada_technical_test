<style>
.status-card {
    background: #f8f9fa;
    border-left: 4px solid <?php echo $dbColor; ?>;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.status-label {
    font-size: 12px;
    text-transform: uppercase;
    color: #666;
    letter-spacing: 1px;
    margin-bottom: 8px;
}

.status-value {
    font-size: 18px;
    font-weight: 600;
    color: <?php echo $dbColor; ?>;
}

.form-card {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 500;
    font-size: 14px;
}

input[type="text"] {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s;
}

input[type="text"]:focus {
    outline: none;
    border-color: #667eea;
}

small {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 12px;
}

.btn {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
}

h1 {
    color: #333;
    margin-bottom: 10px;
    font-size: 32px;
    text-align: center;
}

.subtitle {
    color: #666;
    text-align: center;
    margin-bottom: 30px;
    font-size: 14px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-top: 20px;
}

.info-item {
    background: white;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
}

.info-item-label {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}

.info-item-value {
    font-size: 16px;
    font-weight: 600;
    color: #333;
}
</style>

<h1>🏢 Alvorada Property System</h1>
<p class="subtitle">Property Research & Management Platform</p>

<div class="status-card">
    <div class="status-label">Database Status</div>
    <div class="status-value"><?php echo htmlspecialchars($dbStatus); ?></div>
</div>

<div class="form-card">
    <h2 style="margin-bottom: 20px; color: #333; font-size: 20px;">Add New Property</h2>
    <form method="POST" action="/index.php">
        <div class="form-group">
            <label for="name">Property Name</label>
            <input type="text" id="name" name="name" placeholder="e.g., Downtown Office Building" required minlength="2" maxlength="255">
        </div>
        
        <div class="form-group">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" placeholder="e.g., 123 Main St, New York, NY 10001" required minlength="5" maxlength="500">
            <small>💡 Tip: Be as specific as possible for better geocoding results</small>
        </div>
        
        <button type="submit" class="btn">🚀 Add Property & Enrich Data</button>
    </form>
</div>

<div class="info-grid">
    <div class="info-item">
        <div class="info-item-label">PHP Version</div>
        <div class="info-item-value"><?php echo $phpVersion; ?></div>
    </div>
    <div class="info-item">
        <div class="info-item-label">Server</div>
        <div class="info-item-value">Apache</div>
    </div>
    <div class="info-item">
        <div class="info-item-label">Database</div>
        <div class="info-item-value">PostgreSQL + PostGIS</div>
    </div>
    <div class="info-item">
        <div class="info-item-label">Environment</div>
        <div class="info-item-value">Docker</div>
    </div>
</div>

