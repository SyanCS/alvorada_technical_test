<?php
// Load autoloader
require_once __DIR__ . '/src/Config/Autoloader.php';

use App\Config\Database;

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test database connection
$dbStatus = 'Not Connected';
$dbColor = 'red';
try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    $dbStatus = 'Connected Successfully!';
    $dbColor = 'green';
} catch (Exception $e) {
    $dbStatus = 'Connection Failed: ' . $e->getMessage();
    $dbColor = 'red';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alvorada Property Research System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 600px;
            width: 100%;
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
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            background: #667eea;
            color: white;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏢 Alvorada Property System</h1>
        <p class="subtitle">Property Research & Management Platform</p>
        
        <div class="status-card">
            <div class="status-label">Database Status</div>
            <div class="status-value"><?php echo $dbStatus; ?></div>
        </div>
        
        <div class="form-card">
            <h2 style="margin-bottom: 20px; color: #333; font-size: 20px;">Add New Property</h2>
            <form method="POST" action="/submit_property.php">
                <div class="form-group">
                    <label for="name">Property Name</label>
                    <input type="text" id="name" name="name" placeholder="e.g., Downtown Office Building" required>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" placeholder="e.g., 123 Main St, New York, NY" required>
                </div>
                
                <button type="submit" class="btn">Add Property & Enrich Data</button>
            </form>
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-item-label">PHP Version</div>
                <div class="info-item-value"><?php echo phpversion(); ?></div>
            </div>
            <div class="info-item">
                <div class="info-item-label">Server</div>
                <div class="info-item-value">Apache</div>
            </div>
            <div class="info-item">
                <div class="info-item-label">Database</div>
                <div class="info-item-value">MySQL 8.0</div>
            </div>
            <div class="info-item">
                <div class="info-item-label">Environment</div>
                <div class="info-item-value">Docker</div>
            </div>
        </div>
    </div>
</body>
</html>


