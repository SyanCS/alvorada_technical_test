<style>
.error-icon {
    text-align: center;
    font-size: 64px;
    margin-bottom: 20px;
}

h1 {
    color: #ef4444;
    margin-bottom: 10px;
    font-size: 32px;
    text-align: center;
}

.error-message {
    background: #fee2e2;
    border-left: 4px solid #ef4444;
    padding: 15px;
    border-radius: 8px;
    margin: 20px 0;
    color: #991b1b;
}

.error-list {
    list-style: none;
    margin-top: 10px;
}

.error-list li {
    padding: 5px 0;
}

.btn {
    display: inline-block;
    padding: 14px 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    margin-top: 20px;
}

.actions {
    text-align: center;
}
</style>

<div class="error-icon">❌</div>
<h1>Error Creating Property</h1>

<div class="error-message">
    <strong><?php echo htmlspecialchars($message); ?></strong>
    
    <?php if (!empty($errors)): ?>
    <ul class="error-list">
        <?php foreach ($errors as $field => $error): ?>
        <li>• <?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>

<div class="actions">
    <a href="/" class="btn">← Go Back</a>
</div>

