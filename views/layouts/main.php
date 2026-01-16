<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Alvorada Property Research System'; ?></title>
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
            padding: 0;
        }
        
        .header-nav {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-nav-content {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            justify-content: center;
            gap: 15px;
            padding: 0 20px;
        }
        
        .header-nav a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .header-nav a:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .page-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .main-content {
            flex: 1;
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
            max-width: <?php echo $maxWidth ?? '700px'; ?>;
            width: 100%;
        }
    </style>
    <?php if (isset($additionalStyles)): ?>
        <style><?php echo $additionalStyles; ?></style>
    <?php endif; ?>
</head>
<body>
    <div class="page-wrapper">
        <div class="header-nav">
            <div class="header-nav-content">
                <a href="/">🏠 Add Property</a>
                <a href="/score.html">🎯 Score Properties</a>
                <a href="/map.html">🗺️ View Map</a>
            </div>
        </div>
        <div class="main-content">
            <div class="container">
                <?php echo $content; ?>
            </div>
        </div>
    </div>
</body>
</html>
